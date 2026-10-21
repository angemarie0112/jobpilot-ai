<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;

class CoverLetterController extends Controller
{

    /**
     * Retrieve all cover letters belonging to the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $coverLetters = $request->user()
            ->coverLetters()
            ->with([
                'analysis:id,cv_id,job_description_id,match_score',
                'analysis.jobDescription:id,title,company_name',
            ])
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' => 'Cover letters retrieved successfully.',
            'cover_letters' => $coverLetters,
        ]);
    }
    /**
     * Generate and save a cover letter for an existing analysis.
     */
    public function store(Request $request): JsonResponse
    {

        // Temporary: allow longer AI requests during local development.
        set_time_limit(180);
        // Step 1: Validate the request.
        $validated = $request->validate([
            'analysis_id' => ['required', 'integer', 'min:1'],
        ]);

        // Step 2: Retrieve the authenticated user's analysis.
        // This prevents users from accessing someone else's analysis.
        $analysis = $request->user()
            ->analyses()
            ->with(['cv', 'jobDescription'])
            ->findOrFail($validated['analysis_id']);

        // Step 3: Ensure the analysis was completed successfully.
        if ($analysis->status !== 'completed') {
            return response()->json([
                'message' => 'The analysis must be completed before generating a cover letter.',
            ], 422);
        }

        // Step 4: Retrieve the CV and job description.
        $cv = $analysis->cv;
        $jobDescription = $analysis->jobDescription;

        // Step 5: Ensure the CV contains extracted text.
        if (
            !$cv ||
            $cv->status !== 'processed' ||
            empty(trim($cv->extracted_text ?? ''))
        ) {
            return response()->json([
                'message' => 'The CV must be processed before generating a cover letter.',
            ], 422);
        }

        // Step 6: Ensure the job description is available.
        if (!$jobDescription) {
            return response()->json([
                'message' => 'The job description could not be found.',
            ], 422);
        }

        // Step 7: Send the CV and job description to FastAPI.
        try {
            $response = Http::timeout(120)
                ->acceptJson()
                ->post(
                    rtrim(config('services.ai_service.url'), '/') . '/generate-cover-letter',
                    [
                        'cv_text' => $cv->extracted_text,
                        'job_description' => $jobDescription->description,
                        'job_title' => $jobDescription->title,
                        'company_name' => $jobDescription->company_name,
                    ]
                );

        } catch (ConnectionException $exception) {
            return response()->json([
                'message' => 'The AI cover letter service is currently unavailable.',
            ], 502);
        }

        // Step 8: Handle unsuccessful FastAPI responses.
        if (!$response->successful()) {
            return response()->json([
                'message' => 'The AI service could not generate the cover letter.',
            ], 502);
        }

        $result = $response->json();

        // Step 9: Validate the FastAPI response.
        if (
            !is_array($result) ||
            ($result['status'] ?? null) !== 'completed' ||
            !is_string($result['title'] ?? null) ||
            trim($result['title']) === '' ||
            !is_string($result['content'] ?? null) ||
            trim($result['content']) === '' ||
            !is_string($result['model_used'] ?? null) ||
            trim($result['model_used']) === ''
        ) {
            return response()->json([
                'message' => 'The AI service returned an invalid cover letter.',
            ], 502);
        }

        // Step 10: Save the generated cover letter in PostgreSQL.
        $coverLetter = $request->user()
            ->coverLetters()
            ->create([
                'analysis_id' => $analysis->id,
                'title' => $result['title'],
                'content' => $result['content'],
                'model_used' => $result['model_used'],
            ]);

        // Step 11: Return the saved cover letter.
        return response()->json([
            'message' => 'Cover letter generated and saved successfully.',
            'cover_letter' => $coverLetter,
        ], 201);
    }

    /**
     * Retrieve a specific cover letter belonging to the authenticated user.
    */
    public function show(Request $request, int $id): JsonResponse
    {
        // Step 1: Retrieve the cover letter belonging to the current user.
        $coverLetter = $request->user()
            ->coverLetters()
            ->with([
                'analysis:id,cv_id,job_description_id,match_score',
                'analysis.jobDescription:id,title,company_name,location',
                'analysis.cv:id,original_filename',
            ])
            ->findOrFail($id);

        // Step 2: Return the cover letter.
        return response()->json([
            'message' => 'Cover letter retrieved successfully.',
            'cover_letter' => $coverLetter,
        ]);
    }

    /**
     * Update an existing cover letter belonging to the authenticated user.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // Step 1: Validate the submitted data.
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'required', 'string', 'max:50000'],
        ]);

        // Step 2: Ensure at least one editable field was submitted.
        if (empty($validated)) {
            return response()->json([
                'message' => 'Please provide a title or content to update.',
            ], 422);
        }

        // Step 3: Find the cover letter belonging to the authenticated user.
        $coverLetter = $request->user()
            ->coverLetters()
            ->findOrFail($id);

        // Step 4: Update the permitted fields.
        $coverLetter->update($validated);

        // Step 5: Return the updated cover letter.
        return response()->json([
            'message' => 'Cover letter updated successfully.',
            'cover_letter' => $coverLetter->fresh(),
        ]);
    }

    /**
     * Delete a cover letter belonging to the authenticated user.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        // Step 1: Find the cover letter belonging to the current user.
        $coverLetter = $request->user()
            ->coverLetters()
            ->findOrFail($id);

        // Step 2: Delete the cover letter from the database.
        $coverLetter->delete();

        // Step 3: Return a success response.
        return response()->json([
            'message' => 'Cover letter deleted successfully.',
        ], 200);
    }

    /**
     * Download a saved cover letter as a PDF.
     */
    public function downloadPdf(Request $request, int $id)
    {
        // Retrieve only a cover letter belonging to the authenticated user.
        $coverLetter = $request->user()
            ->coverLetters()
            ->findOrFail($id);

        // Generate the PDF using our Blade template.
        $pdf = Pdf::loadView('pdf.cover-letter', [
            'coverLetter' => $coverLetter,
        ]);

        // Use A4 paper in portrait orientation.
        $pdf->setPaper('a4', 'portrait');

        // Generate a safe filename using the cover letter ID.
        $filename = 'cover-letter-' . $coverLetter->id . '.pdf';

        // Return the PDF as a downloadable file.
        return $pdf->download($filename);
    }
}