<?php

use App\Http\Controllers\Api\AnalysisController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CoverLetterController;
use App\Http\Controllers\Api\CvController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\JobApplicationController;
use App\Http\Controllers\Api\JobDescriptionController;
use App\Http\Controllers\Api\TailoredCvController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Authentication Routes
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:5,1');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');


/*
|--------------------------------------------------------------------------
| Protected API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    Route::get('/user', function (Request $request) {
        return response()->json([
            'user' => $request->user(),
        ]);
    });

    Route::post('/logout', [AuthController::class, 'logout']);


    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [
        DashboardController::class,
        'index',
    ]);


    /*
    |--------------------------------------------------------------------------
    | CV Management
    |--------------------------------------------------------------------------
    */

    Route::get('/cvs', [CvController::class, 'index']);

    Route::post('/cvs', [CvController::class, 'store'])
        ->middleware('throttle:10,1');

    Route::post('/cvs/{id}/process', [CvController::class, 'process'])
        ->whereNumber('id')
        ->middleware('throttle:5,1');

    Route::delete('/cvs/{id}', [CvController::class, 'destroy'])
        ->whereNumber('id');


    /*
    |--------------------------------------------------------------------------
    | Job Description Management
    |--------------------------------------------------------------------------
    */

    Route::get('/job-descriptions', [
        JobDescriptionController::class,
        'index',
    ]);

    Route::post('/job-descriptions', [
        JobDescriptionController::class,
        'store',
    ])->middleware('throttle:10,1');

    Route::get('/job-descriptions/{id}', [
        JobDescriptionController::class,
        'show',
    ])->whereNumber('id');

    Route::match(['put', 'patch'], '/job-descriptions/{id}', [
        JobDescriptionController::class,
        'update',
    ])->whereNumber('id');

    Route::delete('/job-descriptions/{id}', [
        JobDescriptionController::class,
        'destroy',
    ])->whereNumber('id');


    /*
    |--------------------------------------------------------------------------
    | Match Analysis
    |--------------------------------------------------------------------------
    */

    Route::get('/analyses', [
        AnalysisController::class,
        'index',
    ]);

    Route::post('/analyses', [
        AnalysisController::class,
        'store',
    ])->middleware('throttle:5,1');

    Route::get('/analyses/{id}', [
        AnalysisController::class,
        'show',
    ])->whereNumber('id');

    Route::delete('/analyses/{id}', [
        AnalysisController::class,
        'destroy',
    ])->whereNumber('id');


    /*
    |--------------------------------------------------------------------------
    | Cover Letter Management
    |--------------------------------------------------------------------------
    */

    Route::get('/cover-letters', [
        CoverLetterController::class,
        'index',
    ]);

    Route::post('/cover-letters', [
        CoverLetterController::class,
        'store',
    ])->middleware('throttle:5,1');

    Route::get('/cover-letters/{id}/download', [
        CoverLetterController::class,
        'downloadPdf',
    ])->whereNumber('id');

    Route::get('/cover-letters/{id}', [
        CoverLetterController::class,
        'show',
    ])->whereNumber('id');

    Route::put('/cover-letters/{id}', [
        CoverLetterController::class,
        'update',
    ])->whereNumber('id');

    Route::delete('/cover-letters/{id}', [
        CoverLetterController::class,
        'destroy',
    ])->whereNumber('id');


    /*
    |--------------------------------------------------------------------------
    | Tailored CV Management
    |--------------------------------------------------------------------------
    */

    Route::get('/tailored-cvs', [
        TailoredCvController::class,
        'index',
    ]);

    Route::post('/tailored-cvs', [
        TailoredCvController::class,
        'store',
    ])->middleware('throttle:5,1');

    Route::get('/tailored-cvs/{id}/download', [
        TailoredCvController::class,
        'downloadPdf',
    ])->whereNumber('id');

    Route::get('/tailored-cvs/{id}', [
        TailoredCvController::class,
        'show',
    ])->whereNumber('id');

    Route::put('/tailored-cvs/{id}', [
        TailoredCvController::class,
        'update',
    ])->whereNumber('id');

    Route::delete('/tailored-cvs/{id}', [
        TailoredCvController::class,
        'destroy',
    ])->whereNumber('id');


    /*
    |--------------------------------------------------------------------------
    | Job Application Tracking
    |--------------------------------------------------------------------------
    */

    Route::get('/job-applications', [
        JobApplicationController::class,
        'index',
    ]);

    Route::post('/job-applications', [
        JobApplicationController::class,
        'store',
    ]);

    Route::put('/job-applications/{id}', [
        JobApplicationController::class,
        'update',
    ])->whereNumber('id');

    Route::delete('/job-applications/{id}', [
        JobApplicationController::class,
        'destroy',
    ])->whereNumber('id');
});