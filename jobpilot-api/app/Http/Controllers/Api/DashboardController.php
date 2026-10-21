<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Retrieve dashboard statistics for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'message' => 'Dashboard statistics retrieved successfully.',

            'stats' => [
                'cvs' => $user->cvs()->count(),

                'job_descriptions' => $user->jobDescriptions()->count(),

                'analyses' => $user->analyses()
                    ->where('status', 'completed')
                    ->count(),

                'cover_letters' => $user->coverLetters()->count(),

                'tailored_cvs' => $user->tailoredCvs()->count(),

                'job_applications' => $user->jobApplications()->count(),
            ],
        ]);
    }
}