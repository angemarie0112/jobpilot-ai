<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CvController extends Controller
{
    /**
     * List all CVs belonging to the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $cvs = $request->user()
            ->cvs()
            ->select([
                'id',
                'original_filename',
                'mime_type',
                'file_size',
                'status',
                'created_at',
                'updated_at',
            ])
            ->latest()
            ->get();

        return response()->json([
            'message' => 'CVs retrieved successfully.',
            'cvs' => $cvs,
        ]);
    }

    /**
     * Upload a new PDF CV.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cv' => [
                'required',
                'file',
                'mimes:pdf',
                'max:10240',
            ],
        ]);

        $file = $validated['cv'];

        // Store the uploaded CV in private storage.
        $filePath = $file->store(
            'cvs/' . $request->user()->id,
            'local'
        );

        if ($filePath === false) {
            return response()->json([
                'message' => 'Unable to store the uploaded CV.',
            ], 500);
        }

        try {
            $cv = $request->user()->cvs()->create([
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'status' => 'uploaded',
            ]);
        } catch (Throwable $exception) {
            // Remove the uploaded file if database creation fails.
            Storage::disk('local')->delete($filePath);

            throw $exception;
        }

        return response()->json([
            'message' => 'CV uploaded successfully.',
            'cv' => [
                'id' => $cv->id,
                'original_filename' => $cv->original_filename,
                'mime_type' => $cv->mime_type,
                'file_size' => $cv->file_size,
                'status' => $cv->status,
                'created_at' => $cv->created_at,
                'updated_at' => $cv->updated_at,
            ],
        ], 201);
    }

    /**
     * Process a CV using the Python FastAPI service.
     */
    public function process(Request $request, int $id): JsonResponse
    {
        $cv = $request->user()
            ->cvs()
            ->findOrFail($id);

        // Avoid starting another request while processing.
        if ($cv->status === 'processing') {
            return response()->json([
                'message' => 'This CV is already being processed.',
            ], 409);
        }

        // A successfully processed CV does not need extraction again.
        if (
            $cv->status === 'processed' &&
            is_string($cv->extracted_text) &&
            trim($cv->extracted_text) !== ''
        ) {
            return response()->json([
                'message' => 'CV has already been processed.',
                'cv' => [
                    'id' => $cv->id,
                    'original_filename' => $cv->original_filename,
                    'status' => $cv->status,
                    'extracted_text' => $cv->extracted_text,
                ],
            ]);
        }

        // Check that the PDF still exists.
        if (! Storage::disk('local')->exists($cv->file_path)) {
            $cv->update([
                'status' => 'failed',
                'error_message' => 'The uploaded PDF could not be found.',
            ]);

            return response()->json([
                'message' => 'The uploaded PDF could not be found.',
            ], 404);
        }

        // Mark the CV as processing.
        $cv->update([
            'status' => 'processing',
            'error_message' => null,
        ]);

        try {
            $pdfContent = Storage::disk('local')->get(
                $cv->file_path
            );

            $response = Http::timeout(30)
                ->attach(
                    'file',
                    $pdfContent,
                    $cv->original_filename,
                    ['Content-Type' => 'application/pdf']
                )
                ->post(
                    rtrim(config('services.ai_service.url'), '/')
                    . '/extract-text'
                );

            // Handle unsuccessful responses from FastAPI.
            if (! $response->successful()) {
                $cv->update([
                    'status' => 'failed',
                    'error_message' =>
                        'PDF extraction service returned an error.',
                ]);

                return response()->json([
                    'message' => 'Unable to extract text from the CV.',
                ], 502);
            }

            $extractedText = $response->json('extracted_text');

            // Reject PDFs without readable text.
            if (
                ! is_string($extractedText) ||
                trim($extractedText) === ''
            ) {
                $cv->update([
                    'status' => 'failed',
                    'error_message' =>
                        'No readable text was found in the PDF.',
                ]);

                return response()->json([
                    'message' =>
                        'No readable text was found in the PDF.',
                ], 422);
            }

            // Save the extracted text.
            $cv->update([
                'extracted_text' => $extractedText,
                'status' => 'processed',
                'error_message' => null,
            ]);

            return response()->json([
                'message' => 'CV processed successfully.',
                'cv' => [
                    'id' => $cv->id,
                    'original_filename' => $cv->original_filename,
                    'status' => $cv->status,
                    'extracted_text' => $cv->extracted_text,
                ],
            ]);

        } catch (ConnectionException $exception) {
            $cv->update([
                'status' => 'failed',
                'error_message' =>
                    'Unable to communicate with the PDF extraction service.',
            ]);

            report($exception);

            return response()->json([
                'message' =>
                    'The CV processing service is temporarily unavailable.',
            ], 502);

        } catch (Throwable $exception) {
            $cv->update([
                'status' => 'failed',
                'error_message' =>
                    'An unexpected error occurred while processing the CV.',
            ]);

            report($exception);

            return response()->json([
                'message' =>
                    'An unexpected error occurred while processing the CV.',
            ], 500);
        }
    }

    /**
     * Delete a CV belonging to the authenticated user.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $cv = $request->user()
            ->cvs()
            ->findOrFail($id);

        // Prevent deletion when the CV is used in an analysis.
        if ($cv->analyses()->exists()) {
            return response()->json([
                'message' =>
                    'This CV cannot be deleted because it is used in an existing analysis.',
            ], 409);
        }

        $filePath = $cv->file_path;

        // Delete the database record.
        $cv->delete();

        // Remove the stored PDF.
        if (
            $filePath &&
            Storage::disk('local')->exists($filePath)
        ) {
            Storage::disk('local')->delete($filePath);
        }

        return response()->json([
            'message' => 'CV deleted successfully.',
        ]);
    }
}