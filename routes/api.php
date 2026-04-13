<?php

use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CoursesController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamScheduleController;
use App\Http\Controllers\StudentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/courses', [CoursesController::class, 'index'])->middleware('throttle:60,1');


Route::prefix('external')
    ->middleware(['throttle:60,1', 'api.key'])
    ->group(function () {

        Route::prefix('students')
            ->controller(StudentController::class)
            ->group(function () {
                Route::get('/', 'index');
                Route::get('/{id}', 'show');
            });
    });


Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('/applicant/signup', 'applicantSignup')->middleware('throttle:8,1');
    Route::post('/login', 'login')->middleware('throttle:12,1');
    Route::post('/forgot-password', 'forgotPassword')->middleware('throttle:3,1');
    Route::post('/reset-password', 'resetPassword')->middleware('throttle:3,1');
    Route::post('/admin/register', 'registerAdmin')->middleware(['auth:sanctum', 'role:admin', 'throttle:10,1']);
});

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        Route::post('/logout', 'logout')->middleware('throttle:10,1');
    });

    Route::get('/user', function (Request $request) {
        return response()->json($request->user());
    })->middleware('throttle:60,1');

    Route::prefix('me')->controller(ApplicantController::class)->group(function () {
        Route::get('/applicant', 'me')->middleware(['role:applicant', 'throttle:60,1']);
    });

    Route::prefix('applicants')->group(function () {
        Route::controller(ApplicantController::class)->group(function () {
            Route::get('/', 'index')->middleware(['role:admin', 'throttle:60,1']);
            Route::post('/', 'store')->middleware(['role:admin', 'throttle:20,1']);
            Route::get('/unscheduled', 'getUnscheduledApplicants')->middleware(['role:admin', 'throttle:30,1']); // fixed typo
            Route::get('/{id}', 'show')->middleware(['role:admin', 'throttle:60,1']);
            Route::put('/{id}', 'update')->middleware('throttle:20,1');
            Route::delete('/{id}', 'destroy')->middleware(['role:admin', 'throttle:10,1']);
        });

        Route::prefix('{id}/documents')->controller(DocumentController::class)->group(function () {
            Route::get('/', 'index')->middleware('throttle:60,1');
            Route::post('/', 'upload')->middleware('throttle:20,1');
            Route::get('/{documentId}/download', 'download')->middleware('throttle:30,1');
        });

        Route::prefix('{id}')->controller(ApprovalController::class)->group(function () {
            Route::post('/status', 'updateStatus')->middleware(['role:admin', 'throttle:20,1']);
        });
    });

    Route::prefix('audit-logs')->controller(AuditLogController::class)->group(function () {
        Route::get('/', 'index')->middleware(['role:admin', 'throttle:30,1']);
    });

    Route::prefix('courses')->controller(CoursesController::class)->group(function () {
        Route::post('/', 'store')->middleware(['role:admin', 'throttle:20,1']);
        Route::get('/{id}', 'show')->middleware('throttle:60,1');
        Route::put('/{id}', 'update')->middleware(['role:admin', 'throttle:20,1']);
        Route::delete('/{id}', 'destroy')->middleware(['role:admin', 'throttle:10,1']);
    });

    Route::prefix('exam-schedules')->controller(ExamScheduleController::class)->group(function () {
        Route::get('/', 'index')->middleware(['role:admin', 'throttle:60,1']);
        Route::post('/', 'store')->middleware(['role:admin', 'throttle:20,1']);
        Route::put('/{schedule}', 'update')->middleware(['role:admin', 'throttle:20,1']);
        Route::delete('/{schedule}', 'destroy')->middleware(['role:admin', 'throttle:10,1']);
        Route::post('/{schedule}/applicants', 'addApplicants')->middleware(['role:admin', 'throttle:20,1']);
        Route::delete('/{schedule}/applicants/{applicantId}', 'removeApplicant')->middleware(['role:admin', 'throttle:10,1']);
    });

    Route::prefix('exams')->controller(ExamController::class)->group(function () {
        Route::get('/evaluation-queue', 'evaluationQueue')->middleware('throttle:30,1');
        Route::get('/{id?}', 'index')->middleware('throttle:60,1');
        Route::put('/{examId}/evaluate', 'evaluate')->middleware(['role:admin', 'throttle:20,1']);
    });
});