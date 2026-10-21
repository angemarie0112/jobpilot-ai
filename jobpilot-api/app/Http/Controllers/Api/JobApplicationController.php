<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class JobApplicationController extends Controller
{
    /**
     * Create a new job application.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate(
            $this->validationRules($user->id, false)
        );

        // Validate all relationships before creating the application.
        $this->validateRelationships($user, $validated);

        $application = $user->jobApplications()->create($validated);

        // Include related records in the response.
        $application->load($this->applicationRelationships());

        return response()->json([
            'message' => 'Job application created successfully.',
            'job_application' => $application,
        ], 201);
    }

    /**
     * Retrieve all job applications belonging to the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $applications = $request->user()
            ->jobApplications()
            ->with($this->applicationRelationships())
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Job applications retrieved successfully.',
            'job_applications' => $applications,
        ]);
    }

    /**
     * Update an existing job application.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        // Only retrieve an application belonging to this user.
        $application = $user->jobApplications()
            ->findOrFail($id);

        $validated = $request->validate(
            $this->validationRules($user->id, true)
        );

        // Combine existing values with submitted changes.
        // This ensures relationship validation also works
        // when only some fields are updated.
        $finalData = array_merge(
            $application->only([
                'job_description_id',
                'analysis_id',
                'cv_id',
                'tailored_cv_id',
                'cover_letter_id',
            ]),
            $validated
        );

        // Validate relationships using their final values.
        $this->validateRelationships($user, $finalData);

        // Save the submitted changes.
        $application->update($validated);

        // Reload related records.
        $application->load($this->applicationRelationships());

        return response()->json([
            'message' => 'Job application updated successfully.',
            'job_application' => $application,
        ]);
    }

    /**
     * Delete a job application belonging to the authenticated user.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $application = $request->user()
            ->jobApplications()
            ->findOrFail($id);

        $application->delete();

        return response()->json([
            'message' => 'Job application deleted successfully.',
        ]);
    }

    /**
     * Validation rules for creating and updating applications.
     *
     * When updating, all fields are optional.
     * When creating, job title and company name are required.
     */
    private function validationRules(
        int $userId,
        bool $updating = false
    ): array {
        $requiredRule = $updating ? 'sometimes' : 'required';

        return [
            'job_title' => [
                $requiredRule,
                'required',
                'string',
                'max:255',
            ],

            'company_name' => [
                $requiredRule,
                'required',
                'string',
                'max:255',
            ],

            'job_url' => [
                'sometimes',
                'nullable',
                'url',
                'max:2048',
            ],

            'location' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'sometimes',
                'required',
                Rule::in([
                    'saved',
                    'applied',
                    'screening',
                    'interview',
                    'offer',
                    'rejected',
                    'withdrawn',
                ]),
            ],

            'applied_at' => [
                'sometimes',
                'nullable',
                'date',
            ],

            'next_follow_up_at' => [
                'sometimes',
                'nullable',
                'date',
            ],

            'notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:10000',
            ],

            'job_description_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('job_descriptions', 'id')
                    ->where('user_id', $userId),
            ],

            'analysis_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('analyses', 'id')
                    ->where('user_id', $userId),
            ],

            'cv_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('cvs', 'id')
                    ->where('user_id', $userId),
            ],

            'tailored_cv_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('tailored_cvs', 'id')
                    ->where('user_id', $userId),
            ],

            'cover_letter_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('cover_letters', 'id')
                    ->where('user_id', $userId),
            ],
        ];
    }

    /**
     * Validate that all linked records are consistent.
     *
     * Relationships:
     *
     * Job Description
     *       |
     * Match Analysis
     *       |
     *       +---- Original CV
     *       |
     *       +---- Tailored CV
     *       |
     *       +---- Cover Letter
     */
    private function validateRelationships(
        User $user,
        array $data
    ): void {
        $jobDescriptionId = $data['job_description_id'] ?? null;
        $analysisId = $data['analysis_id'] ?? null;
        $cvId = $data['cv_id'] ?? null;
        $tailoredCvId = $data['tailored_cv_id'] ?? null;
        $coverLetterId = $data['cover_letter_id'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | 1. Validate Match Analysis
        |--------------------------------------------------------------------------
        */

        if ($analysisId !== null) {
            $analysis = $user->analyses()
                ->findOrFail($analysisId);

            // Ensure the analysis belongs to the selected job description.
            if (
                $jobDescriptionId !== null &&
                $analysis->job_description_id !== (int) $jobDescriptionId
            ) {
                throw ValidationException::withMessages([
                    'analysis_id' =>
                        'The selected analysis does not match the job description.',
                ]);
            }

            // Ensure the analysis uses the selected original CV.
            if (
                $cvId !== null &&
                $analysis->cv_id !== (int) $cvId
            ) {
                throw ValidationException::withMessages([
                    'cv_id' =>
                        'The selected CV does not match the selected analysis.',
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Validate Tailored CV
        |--------------------------------------------------------------------------
        */

        if ($tailoredCvId !== null) {
            $tailoredCv = $user->tailoredCvs()
                ->findOrFail($tailoredCvId);

            // A tailored CV must be linked to a match analysis.
            if ($analysisId === null) {
                throw ValidationException::withMessages([
                    'tailored_cv_id' =>
                        'Please select a match analysis before linking a tailored CV.',
                ]);
            }

            // Ensure the tailored CV belongs to the selected analysis.
            if (
                $tailoredCv->analysis_id !== (int) $analysisId
            ) {
                throw ValidationException::withMessages([
                    'tailored_cv_id' =>
                        'The selected tailored CV does not belong to the selected analysis.',
                ]);
            }

            // Ensure the tailored CV uses the same original CV.
            if (
                $cvId !== null &&
                $tailoredCv->cv_id !== (int) $cvId
            ) {
                throw ValidationException::withMessages([
                    'tailored_cv_id' =>
                        'The selected tailored CV does not match the original CV.',
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Validate Cover Letter
        |--------------------------------------------------------------------------
        */

        if ($coverLetterId !== null) {
            $coverLetter = $user->coverLetters()
                ->findOrFail($coverLetterId);

            // A cover letter must be linked to a match analysis.
            if ($analysisId === null) {
                throw ValidationException::withMessages([
                    'cover_letter_id' =>
                        'Please select a match analysis before linking a cover letter.',
                ]);
            }

            // Ensure the cover letter belongs to the selected analysis.
            if (
                $coverLetter->analysis_id !== (int) $analysisId
            ) {
                throw ValidationException::withMessages([
                    'cover_letter_id' =>
                        'The selected cover letter does not belong to the selected analysis.',
                ]);
            }
        }
    }

    /**
     * Relationships loaded with job applications.
     */
    private function applicationRelationships(): array
    {
        return [
            'jobDescription:id,title,company_name',
            'cv:id,original_filename',
            'analysis:id,match_score',
            'tailoredCv:id,title',
            'coverLetter:id,title',
        ];
    }
}