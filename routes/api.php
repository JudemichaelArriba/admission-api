<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ApprovalController;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function () {


    Route::get('/applicants', [ApplicantController::class, 'index']);
    Route::get('/applicants/{id}', [ApplicantController::class, 'show']);
    Route::post('/applicants', [ApplicantController::class, 'store']);

    // Documents
    Route::post('/applicants/{id}/documents', [DocumentController::class, 'upload']);

    // Exams
    Route::post('/applicants/{id}/exams', [ExamController::class, 'schedule']);
    Route::post('/applicants/{id}/exams/evaluate', [ExamController::class, 'evaluate']);

    // Approval / Enrollment
    Route::post('/applicants/{id}/approve', [ApprovalController::class, 'approve']);
    Route::post('/applicants/{id}/reject', [ApprovalController::class, 'reject']);
    Route::post('/applicants/{id}/enroll', [ApprovalController::class, 'enroll']);


});