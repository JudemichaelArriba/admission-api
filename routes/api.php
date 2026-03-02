<?php

use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CoursesController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\StudentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/applicant/signup', [AuthController::class, 'applicantSignup'])->middleware('throttle:8,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:12,1');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return response()->json($request->user());
    });

    Route::get('/me/applicant', [ApplicantController::class, 'me'])->middleware('role:applicant');

    Route::get('/applicants/filter', [ApplicantController::class, 'filterByStatus']);
    Route::get('/applicants', [ApplicantController::class, 'index']);
    Route::get('/applicants/{id}', [ApplicantController::class, 'show']);
    Route::post('/applicants', [ApplicantController::class, 'store'])->middleware('role:admin');
    Route::put('/applicants/{id}', [ApplicantController::class, 'update']);
    Route::delete('/applicants/{id}', [ApplicantController::class, 'destroy'])->middleware('role:admin');

    Route::post('/applicants/{id}/documents', [DocumentController::class, 'upload'])->middleware('throttle:20,1');
    Route::get('/applicants/{id}/documents', [DocumentController::class, 'index']);
    Route::get('/applicants/{id}/documents/{documentId}/download', [DocumentController::class, 'download']);

    Route::post('/applicants/{id}/exams', [ExamController::class, 'schedule'])->middleware('role:admin');
    Route::post('/applicants/{id}/exams/evaluate', [ExamController::class, 'evaluate'])->middleware('role:admin');
    Route::get('/applicants/{id}/exams', [ExamController::class, 'index']);

    Route::post('/applicants/{id}/approve', [ApprovalController::class, 'approve'])->middleware('role:admin');
    Route::post('/applicants/{id}/reject', [ApprovalController::class, 'reject'])->middleware('role:admin');
    Route::post('/applicants/{id}/enroll', [ApprovalController::class, 'enroll'])->middleware('role:admin');

    Route::get('/courses', [CoursesController::class, 'index']);
    Route::get('/courses/{id}', [CoursesController::class, 'show']);
    Route::post('/courses', [CoursesController::class, 'store'])->middleware('role:admin');
    Route::put('/courses/{id}', [CoursesController::class, 'update'])->middleware('role:admin');
    Route::delete('/courses/{id}', [CoursesController::class, 'destroy'])->middleware('role:admin');

    Route::get('/students', [StudentController::class, 'index'])->middleware('role:admin');
    Route::get('/students/{id}', [StudentController::class, 'show'])->middleware('role:admin');

    Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware('role:admin');
});
