<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Applicant;
use App\Models\EntranceExam;
use App\Http\Requests\EvaluateExamRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExamController extends Controller
{
    public function index(Request $request, ?int $id = null)
    {
        $user = $request->user();


        if ($id === null) {
            if ($user->hasRole(UserRole::ADMIN)) {
                $exams = EntranceExam::with(['schedule', 'applicant'])->latest()->get();
                return response()->json($exams);
            }
            
        
            return response()->json(['message' => 'Forbidden: ID required to view personal exams'], 403);
        }

        if ($user->hasRole(UserRole::ADMIN)) {
            $exams = EntranceExam::with(['schedule', 'applicant'])
                ->where('applicant_id', $id)
                ->latest()
                ->get();
                
            return response()->json($exams);
        }

        if ($user->hasRole(UserRole::APPLICANT)) {
            $applicant = Applicant::where('user_id', $user->id)->first();

            if (!$applicant) {
                return response()->json(['message' => 'Applicant profile not found'], 404);
            }

            // Trap: Applicant trying to view someone else's ID
            if ($applicant->id !== (int) $id) {
                return response()->json(['message' => 'Forbidden: You can only view your own exams'], 403);
            }

            $exams = EntranceExam::with('schedule')
                ->where('applicant_id', $id)
                ->latest()
                ->get();

            return response()->json($exams);
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }

    public function evaluate(EvaluateExamRequest $request, $examId)
    {
        if (!$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $exam = EntranceExam::with('schedule')->find($examId);

        if (!$exam) {
            return response()->json(['message' => 'Exam record not found'], 404);
        }

        $examEndDateTime = Carbon::parse($exam->schedule->exam_end_time, config('app.timezone'));

        if (Carbon::now(config('app.timezone'))->lessThanOrEqualTo($examEndDateTime)) {
            return response()->json([
                'message' => 'Exam cannot be evaluated until it concludes at ' . $examEndDateTime->toDateTimeString(),
            ], 422);
        }

        $exam->update([
            'exam_score' => $request->validated()['exam_score'],
            'status'     => 'evaluated',
        ]);

        return response()->json(['message' => 'Exam evaluated successfully', 'data' => $exam]);
    }
}