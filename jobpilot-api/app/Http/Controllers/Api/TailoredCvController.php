<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;

class TailoredCvController extends Controller
{
    /**
     * Generate and save a tailored CV from an existing analysis.
     */
    public function store(Request $request): JsonResponse
    {
        // Step 1: Validate the request.
        $validated = $request->validate([
            'analysis_id' => ['required', 'integer', 'min:1'],
        ]);

        // Step 2: Retrieve an analysis belonging to the authenticated user.
        $analysis = $request->user()
            ->analyses()
            ->with(['cv', 'jobDescription'])
            ->findOrFail($validated['analysis_id']);

        // Step 3: Ensure the analysis is complete.
        if ($analysis->status !== 'completed') {
            return response()->json([
                'message' => 'The analysis must be completed before tailoring the CV.',
            ], 422);
        }

        $cv = $analysis->cv;
        $jobDescription = $analysis->jobDescription;

        // Step 4: Verify the original CV has extracted text.
        if (
            !$cv ||
            $cv->status !== 'processed' ||
            empty(trim($cv->extracted_text ?? ''))
        ) {
            return response()->json([
                'message' => 'The original CV must be processed before tailoring.',
            ], 422);
        }

        // Step 5: Verify the job description is available.
        if (
            !$jobDescription ||
            empty(trim($jobDescription->description ?? ''))
        ) {
            return response()->json([
                'message' => 'The job description is missing or empty.',
            ], 422);
        }

        // Step 6: Send the CV, job description and analysis to FastAPI.
        try {
            $response = Http::timeout(180)
                ->acceptJson()
                ->post(
                    rtrim(config('services.ai_service.url'), '/') . '/tailor-cv',
                    [
                        'cv_text' => $cv->extracted_text,
                        'job_description' => $jobDescription->description,
                        'job_title' => $jobDescription->title,
                        'company_name' => $jobDescription->company_name,
                        'match_analysis' => [
                            'match_score' => $analysis->match_score,
                            'matching_skills' => $analysis->matching_skills ?? [],
                            'missing_skills' => $analysis->missing_skills ?? [],
                            'recommendations' => $analysis->recommendations ?? [],
                        ],
                    ]
                );

        } catch (ConnectionException $exception) {
            return response()->json([
                'message' => 'The AI CV tailoring service is currently unavailable.',
            ], 502);
        }

        // Step 7: Handle unsuccessful FastAPI responses.
        if (!$response->successful()) {
            return response()->json([
                'message' => 'The AI service could not generate the tailored CV.',
            ], 502);
        }

        $result = $response->json();

        // Step 8: Validate the AI response before saving.
        if (
            !is_array($result) ||
            ($result['status'] ?? null) !== 'completed' ||
            !isset($result['title']) ||
            !is_string($result['title']) ||
            trim($result['title']) === '' ||
            !isset($result['content']) ||
            !is_array($result['content']) ||
            !isset($result['content']['contact']) ||
            !is_array($result['content']['contact']) ||
            !isset($result['content']['professional_summary']) ||
            !is_string($result['content']['professional_summary']) ||
            !isset($result['content']['skills']) ||
            !is_array($result['content']['skills']) ||
            !isset($result['content']['experience']) ||
            !is_array($result['content']['experience']) ||
            !isset($result['content']['education']) ||
            !is_array($result['content']['education']) ||
            !isset($result['content']['projects']) ||
            !is_array($result['content']['projects']) ||
            !isset($result['content']['certifications']) ||
            !is_array($result['content']['certifications'])
        ) {
            return response()->json([
                'message' => 'The AI service returned an invalid CV structure.',
            ], 502);
        }

        // Step 9: Save the tailored CV in PostgreSQL.
        $tailoredCv = $request->user()
            ->tailoredCvs()
            ->create([
                'cv_id' => $cv->id,
                'analysis_id' => $analysis->id,
                'title' => $result['title'],
                'content' => $result['content'],
                'model_used' => $result['model_used'] ?? null,
            ]);

        // Step 10: Return the saved tailored CV.
        return response()->json([
            'message' => 'Tailored CV generated and saved successfully.',
            'tailored_cv' => $tailoredCv,
        ], 201);
    }


    /**
     * Retrieve all tailored CVs belonging to the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $tailoredCvs = $request->user()
            ->tailoredCvs()
            ->with([
                'cv:id,original_filename',
                'analysis.jobDescription:id,title,company_name,location',
            ])
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' => 'Tailored CVs retrieved successfully.',
            'tailored_cvs' => $tailoredCvs,
        ]);
    }

    /**
     * Retrieve a specific tailored CV belonging to the authenticated user.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tailoredCv = $request->user()
            ->tailoredCvs()
            ->with([
                'cv:id,original_filename',
                'analysis.jobDescription:id,title,company_name,location',
            ])
            ->findOrFail($id);

        return response()->json([
            'message' => 'Tailored CV retrieved successfully.',
            'tailored_cv' => $tailoredCv,
        ]);
    }

    /**
     * Update a tailored CV belonging to the authenticated user.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // Retrieve only a CV owned by the authenticated user.
        $tailoredCv = $request->user()
            ->tailoredCvs()
            ->findOrFail($id);

        // Validate the updated CV structure.
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],

            'content' => ['required', 'array'],

            'content.contact' => ['required', 'array'],
            'content.contact.full_name' => ['nullable', 'string', 'max:255'],
            'content.contact.email' => ['nullable', 'email', 'max:255'],
            'content.contact.phone' => ['nullable', 'string', 'max:100'],
            'content.contact.location' => ['nullable', 'string', 'max:255'],
            'content.contact.linkedin' => ['nullable', 'string', 'max:500'],
            'content.contact.github' => ['nullable', 'string', 'max:500'],

            'content.professional_summary' => ['required', 'string'],

            'content.skills' => ['required', 'array'],
            'content.skills.*' => ['required', 'string', 'max:255'],

            'content.experience' => ['present', 'array'],
            'content.experience.*' => ['present', 'array'],
            'content.experience.*.job_title' => ['nullable', 'string', 'max:255'],
            'content.experience.*.company' => ['nullable', 'string', 'max:255'],
            'content.experience.*.location' => ['nullable', 'string', 'max:255'],
            'content.experience.*.start_date' => ['nullable', 'string', 'max:100'],
            'content.experience.*.end_date' => ['nullable', 'string', 'max:100'],
            'content.experience.*.responsibilities' => ['required', 'array'],
            'content.experience.*.responsibilities.*' => ['required', 'string'],

            'content.education' => ['present', 'array'],
            'content.education.*' => ['present', 'array'],
            'content.education.*.degree' => ['nullable', 'string', 'max:255'],
            'content.education.*.institution' => ['nullable', 'string', 'max:255'],
            'content.education.*.location' => ['nullable', 'string', 'max:255'],
            'content.education.*.start_date' => ['nullable', 'string', 'max:100'],
            'content.education.*.end_date' => ['nullable', 'string', 'max:100'],

            'content.projects' => ['present', 'array'],
            'content.projects.*' => ['present', 'array'],
            'content.projects.*.name' => ['nullable', 'string', 'max:255'],
            'content.projects.*.description' => ['nullable', 'string'],
            'content.projects.*.technologies' => ['required', 'array'],
            'content.projects.*.technologies.*' => ['required', 'string', 'max:255'],

            'content.certifications' => ['present', 'array'],
            'content.certifications.*' => ['present', 'string', 'max:255'],
        ]);

        // Update only editable fields.
        $tailoredCv->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
        ]);

        return response()->json([
            'message' => 'Tailored CV updated successfully.',
            'tailored_cv' => $tailoredCv->fresh(),
        ]);
    }

    /**
     * Download a saved tailored CV as a PDF.
     */
    public function downloadPdf(Request $request, int $id)
    {
        // Only allow users to download their own tailored CVs.
        $tailoredCv = $request->user()
            ->tailoredCvs()
            ->findOrFail($id);

        // Generate the PDF from the saved CV content.
        $pdf = Pdf::loadView('pdf.tailored-cv', [
            'tailoredCv' => $tailoredCv,
        ]);

        // Use A4 portrait format.
        $pdf->setPaper('a4', 'portrait');

        // Generate a safe filename.
        $filename = 'tailored-cv-' . $tailoredCv->id . '.pdf';

        // Return the generated PDF as a download.
        return $pdf->download($filename);
    }

        /**
     * Delete a tailored CV belonging to the authenticated user.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        // Step 1: Find the tailored CV belonging to the current user.
        $tailoredCv = $request->user()
            ->tailoredCvs()
            ->findOrFail($id);

        // Step 2: Check whether this tailored CV is linked
        // to an existing job application.
        $isUsedByApplication = $request->user()
            ->jobApplications()
            ->where('tailored_cv_id', $tailoredCv->id)
            ->exists();

        if ($isUsedByApplication) {
            return response()->json([
                'message' => 'This tailored CV cannot be deleted because it is linked to a tracked job application. Remove it from the application first.',
            ], 409);
        }

        // Step 3: Delete the tailored CV.
        $tailoredCv->delete();

        // Step 4: Return a success response.
        return response()->json([
            'message' => 'Tailored CV deleted successfully.',
        ], 200);
    }
}