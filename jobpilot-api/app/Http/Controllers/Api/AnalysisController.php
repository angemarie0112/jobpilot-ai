<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AnalysisController extends Controller
{
    /**
     * Analyze a CV against a saved job description.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cv_id' => ['required', 'integer', 'min:1'],
            'job_description_id' => ['required', 'integer', 'min:1'],
        ]);

        // Retrieve only records belonging to the authenticated user.
        $cv = $request->user()
            ->cvs()
            ->findOrFail($validated['cv_id']);

        $jobDescription = $request->user()
            ->jobDescriptions()
            ->findOrFail($validated['job_description_id']);

        // Ensure the CV has already been processed.
        if (
            $cv->status !== 'processed' ||
            empty(trim($cv->extracted_text ?? ''))
        ) {
            return response()->json([
                'message' => 'The CV must be processed before analysis.',
            ], 422);
        }

        // Send the CV and job description to FastAPI.
        try {
            $response = Http::timeout(30)
                ->acceptJson()
                ->post(
                    rtrim(config('services.ai_service.url'), '/') . '/analyze-match',
                    [
                        'cv_text' => $cv->extracted_text,
                        'job_description' => $jobDescription->description,
                    ]
                );

        } catch (ConnectionException $exception) {
            return response()->json([
                'message' => 'The AI matching service is currently unavailable.',
            ], 502);
        }

        if (!$response->successful()) {
            return response()->json([
                'message' => 'The AI matching service could not complete the analysis.',
            ], 502);
        }

        $result = $response->json();

        // Validate the FastAPI response.
        if (
            !is_array($result) ||
            ($result['status'] ?? null) !== 'completed' ||

            !isset($result['matching_skills']) ||
            !is_array($result['matching_skills']) ||

            !isset($result['missing_skills']) ||
            !is_array($result['missing_skills']) ||

            !isset($result['recommendations']) ||
            !is_array($result['recommendations']) ||

            count(array_filter(
                $result['recommendations'] ?? [],
                fn ($recommendation) => !is_string($recommendation)
            )) > 0 ||

            !array_key_exists('match_score', $result) ||
            (
                $result['match_score'] !== null &&
                (
                    !is_int($result['match_score']) ||
                    $result['match_score'] < 0 ||
                    $result['match_score'] > 100
                )
            )
        ) {
            return response()->json([
                'message' => 'The AI matching service returned an invalid response.',
            ], 502);
        }

        // Generate the analysis summary.
        $matchScore = $result['match_score'];

        $summary = $matchScore === null
            ? 'The analysis completed, but there were not enough recognized job skills to calculate a match score.'
            : "The CV matches {$matchScore}% of the job's weighted skill requirements.";

        // Save the analysis.
        $analysis = $request->user()
            ->analyses()
            ->create([
                'cv_id' => $cv->id,
                'job_description_id' => $jobDescription->id,
                'status' => 'completed',
                'match_score' => $matchScore,
                'matching_skills' => $result['matching_skills'],
                'missing_skills' => $result['missing_skills'],
                'recommendations' => $result['recommendations'],
                'summary' => $summary,
                'model_used' => 'rule-based-v1',
                'error_message' => null,
            ]);

        return response()->json([
            'message' => 'CV analysis completed and saved successfully.',
            'analysis' => $analysis,
        ], 201);
    }

    /**
     * Retrieve all analyses belonging to the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $analyses = $request->user()
            ->analyses()
            ->with([
                'cv:id,original_filename,status',
                'jobDescription:id,title,company_name,location',
            ])
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' => 'Analyses retrieved successfully.',
            'analyses' => $analyses,
        ]);
    }

    /**
     * Retrieve a specific analysis belonging to the authenticated user.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $analysis = $request->user()
            ->analyses()
            ->with([
                'cv:id,original_filename,status',
                'jobDescription:id,title,company_name,location',
            ])
            ->findOrFail($id);

        return response()->json([
            'message' => 'Analysis retrieved successfully.',
            'analysis' => $analysis,
        ]);
    }

    /**
     * Delete an analysis belonging to the authenticated user.
     *
     * An analysis cannot be deleted if it is linked to
     * a cover letter, tailored CV, or job application.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        // Step 1: Find the authenticated user's analysis.
        $analysis = $request->user()
            ->analyses()
            ->findOrFail($id);

        // Step 2: Check whether cover letters reference this analysis.
        $hasCoverLetters = $request->user()
            ->coverLetters()
            ->where('analysis_id', $analysis->id)
            ->exists();

        if ($hasCoverLetters) {
            return response()->json([
                'message' => 'This analysis cannot be deleted because it is used by an existing cover letter. Delete the linked cover letter first.',
            ], 409);
        }

        // Step 3: Check whether tailored CVs reference this analysis.
        $hasTailoredCvs = $request->user()
            ->tailoredCvs()
            ->where('analysis_id', $analysis->id)
            ->exists();

        if ($hasTailoredCvs) {
            return response()->json([
                'message' => 'This analysis cannot be deleted because it is used by an existing tailored CV. Delete the linked tailored CV first.',
            ], 409);
        }

        // Step 4: Check whether job applications reference this analysis.
        $hasJobApplications = $request->user()
            ->jobApplications()
            ->where('analysis_id', $analysis->id)
            ->exists();

        if ($hasJobApplications) {
            return response()->json([
                'message' => 'This analysis cannot be deleted because it is linked to a tracked job application. Remove the analysis from that application first.',
            ], 409);
        }

        // Step 5: Delete the analysis.
        $analysis->delete();

        return response()->json([
            'message' => 'Analysis deleted successfully.',
        ], 200);
    }
}