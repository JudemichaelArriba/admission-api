<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\CoursesController;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function () {

    // Applicant
    // Filter by status
    Route::get('/applicants/filter', [ApplicantController::class, 'filterByStatus']);
   
    Route::get('/applicants', [ApplicantController::class, 'index']);
    Route::get('/applicants/{id}', [ApplicantController::class, 'show']);
    Route::post('/applicants', [ApplicantController::class, 'store']);
    Route::put('/applicants/{id}', [ApplicantController::class, 'update']);
    Route::delete('/applicants/{id}', [ApplicantController::class, 'destroy']);

    // Documents
    Route::post('/applicants/{id}/documents', [DocumentController::class, 'upload']);
    Route::get('/applicants/{id}/documents', [DocumentController::class, 'index']);
    // Exams
    Route::post('/applicants/{id}/exams', [ExamController::class, 'schedule']);
    Route::post('/applicants/{id}/exams/evaluate', [ExamController::class, 'evaluate']);
    Route::get('/applicants/{id}/exams', [ExamController::class, 'index']);

    // Approval / Enrollment
    Route::post('/applicants/{id}/approve', [ApprovalController::class, 'approve']);
    Route::post('/applicants/{id}/reject', [ApprovalController::class, 'reject']);
    Route::post('/applicants/{id}/enroll', [ApprovalController::class, 'enroll']);

    // courses
      Route::get('/courses', [CoursesController::class, 'index']);
    Route::get('/courses/{id}', [CoursesController::class, 'show']);
    Route::post('/courses', [CoursesController::class, 'store']);
    Route::put('/courses/{id}', [CoursesController::class, 'update']);
    Route::delete('/courses/{id}', [CoursesController::class, 'destroy']);
});