<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Applicant;
use App\Models\EntranceExam;
use App\Http\Requests\ManageEntranceExamRequest;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function manage(ManageEntranceExamRequest $request, int $id)
    {
        if (!$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $applicant = Applicant::find($id);
        if (!$applicant) {
            return response()->json(['message' => 'Applicant not found'], 404);
        }

        $validated = $request->validated();
        $action = $validated['action'];

        if ($applicant->status !== Applicant::STATUS_PENDING) {
            $message = $action === 'evaluate'
                ? 'Only pending applicants can be evaluated'
                : 'Only pending applicants can be scheduled for exam';
            return response()->json(['message' => $message], 409);
        }

        if ($action === 'schedule') {
            $exam = EntranceExam::create([
                'applicant_id' => $applicant->id,
                'exam_date' => $validated['exam_date'],
                'status' => 'scheduled',
            ]);

            $this->logAudit($request, 'entrance_exam_scheduled', 'entrance_exam', $exam->id, [
                'applicant_id' => $applicant->id,
                'exam_date' => $exam->exam_date,
            ]);

            return response()->json($exam, 201);
        }

        $exam = EntranceExam::where('applicant_id', $id)->latest('exam_date')->latest('id')->first();
        if (!$exam) {
            return response()->json(['message' => 'Exam not found'], 404);
        }

        if ($exam->status !== 'scheduled') {
            return response()->json(['message' => 'Only scheduled exams can be evaluated'], 409);
        }

        $exam->update([
            'exam_score' => $validated['exam_score'],
            'status' => 'evaluated',
        ]);

        $this->logAudit($request, 'entrance_exam_evaluated', 'entrance_exam', $exam->id, [
            'applicant_id' => $id,
            'exam_score' => $validated['exam_score'],
        ]);

        return response()->json($exam);
    }

    public function index(Request $request, int $id)
    {
        $applicant = Applicant::with('exams')->find($id);
        if (!$applicant) {
            return response()->json(['message' => 'Applicant not found'], 404);
        }

        $user = $request->user();
        $canAccess = $user->hasRole(UserRole::ADMIN)
            || ($user->hasRole(UserRole::APPLICANT) && $applicant->user_id === $user->id);
        if (!$canAccess) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($applicant->exams);
    }
}
