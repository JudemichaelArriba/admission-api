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

Route::get('/courses', [CoursesController::class, 'index']);

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('/applicant/signup', 'applicantSignup')->middleware('throttle:8,1');
    Route::post('/login', 'login')->middleware('throttle:12,1');
    Route::post('/forgot-password', 'forgotPassword')->middleware('throttle:3,1');
    Route::post('/reset-password', 'resetPassword')->middleware('throttle:3,1');    
    Route::post('/admin/register', 'registerAdmin')->middleware(['auth:sanctum', 'role:admin']);
});

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        Route::post('/logout', 'logout');
    });

    Route::get('/user', function (Request $request) {
        return response()->json($request->user());
    });

    Route::prefix('me')->controller(ApplicantController::class)->group(function () {
        Route::get('/applicant', 'me')->middleware('role:applicant');
    });

    Route::prefix('applicants')->group(function () {
        Route::controller(ApplicantController::class)->group(function () {
            Route::get('/', 'index')->middleware('role:admin');
            Route::post('/', 'store')->middleware('role:admin');
            Route::get('/unscheduled', 'getUnscheduledApplicants')->middleware('role:admin');
            Route::get('/{id}', 'show')->middleware('role:admin');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'destroy')->middleware('role:admin');
        });

        Route::prefix('{id}/documents')->controller(DocumentController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'upload')->middleware('throttle:20,1');
            Route::get('/{documentId}/download', 'download');
        });

        Route::prefix('{id}')->controller(ApprovalController::class)->group(function () {
            Route::post('/status', 'updateStatus')->middleware('role:admin');
        });
    });

    Route::prefix('audit-logs')->controller(AuditLogController::class)->group(function () {
        Route::get('/', 'index')->middleware('role:admin');
    });

    Route::prefix('courses')->controller(CoursesController::class)->group(function () {
        Route::post('/', 'store')->middleware('role:admin');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update')->middleware('role:admin');
        Route::delete('/{id}', 'destroy')->middleware('role:admin');
    });

    Route::prefix('exam-schedules')->controller(ExamScheduleController::class)->group(function () {
        Route::get('/', 'index')->middleware('role:admin');
        Route::post('/', 'store')->middleware('role:admin');
        Route::put('/{schedule}', 'update')->middleware('role:admin');
        Route::delete('/{schedule}', 'destroy')->middleware('role:admin');
        Route::post('/{schedule}/applicants', 'addApplicants')->middleware('role:admin');
        Route::delete('/{schedule}/applicants/{applicantId}', 'removeApplicant')->middleware('role:admin');
    });

    Route::prefix('exams')->controller(ExamController::class)->group(function () {
        Route::get('/evaluation-queue', 'evaluationQueue');
        Route::get('/{id?}', 'index');
        Route::put('/{examId}/evaluate', 'evaluate')->middleware('role:admin');
    });

    Route::prefix('students')->controller(StudentController::class)->group(function () {
        Route::get('/', 'index')->middleware('role:admin');
        Route::get('/{id}', 'show')->middleware('role:admin');
    });
});