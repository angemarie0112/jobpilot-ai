<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobDescriptionController extends Controller
{
    /**
     * List the authenticated user's saved job descriptions.
     */
    public function index(Request $request): JsonResponse
    {
        $jobDescriptions = $request->user()
            ->jobDescriptions()
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Job descriptions retrieved successfully.',
            'job_descriptions' => $jobDescriptions,
        ], 200);
    }

    /**
     * Save a new job description.
     */
    public function store(Request $request): JsonResponse
    {
        // Step 1: Validate the submitted job information
        $validated = $request->validate([
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'company_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'location' => [
                'nullable',
                'string',
                'max:255',
            ],

            'description' => [
                'required',
                'string',
                'min:20',
                'max:50000',
            ],

            'source_url' => [
                'nullable',
                'url',
                'max:2048',
            ],
        ]);

        // Step 2: Create the job description for the authenticated user
        $jobDescription = $request->user()
            ->jobDescriptions()
            ->create($validated);

        // Step 3: Return the newly saved job description
        return response()->json([
            'message' => 'Job description saved successfully.',
            'job_description' => $jobDescription,
        ], 201);
    }

    /**
     * Display a specific job description.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $jobDescription = $request->user()
            ->jobDescriptions()
            ->findOrFail($id);

        return response()->json([
            'message' => 'Job description retrieved successfully.',
            'job_description' => $jobDescription,
        ], 200);
    }

    /**
     * Update an existing job description.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $jobDescription = $request->user()
            ->jobDescriptions()
            ->findOrFail($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'min:20', 'max:50000'],
            'source_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
        ]);

        $jobDescription->update($validated);

        return response()->json([
            'message' => 'Job description updated successfully.',
            'job_description' => $jobDescription,
        ], 200);
    }

    /**
     * Delete a saved job description.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $jobDescription = $request->user()
            ->jobDescriptions()
            ->findOrFail($id);

        // Prevent deletion if an existing analysis references this job.
        if ($jobDescription->analyses()->exists()) {
            return response()->json([
                'message' => 'This job description cannot be deleted because it is used in an existing analysis.',
            ], 409);
        }

        $jobDescription->delete();

        return response()->json([
            'message' => 'Job description deleted successfully.',
        ], 200);
    }
}