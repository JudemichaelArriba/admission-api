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

        $exam = EntranceExam::with(['schedule', 'applicant.course'])->find($examId);

        if (!$exam) {
            return response()->json(['message' => 'Exam record not found'], 404);
        }


        if ($exam->schedule->status !== 'completed') {
            return response()->json(['message' => 'Cannot grade an exam that is still upcoming'], 422);
        }

        $exam->update([
            'exam_score' => $request->validated()['exam_score'],
            'status'     => 'evaluated',
        ]);

        return response()->json([
            'message' => 'Exam evaluated successfully',
            'data' => $exam->fresh(['applicant.course', 'schedule']) 
        ]);
    }



public function evaluationQueue(Request $request)
    {
        if (!$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');
        $filter = $request->input('filter', 'all');

        $baseQuery = EntranceExam::with(['applicant.course', 'schedule'])
            ->whereHas('schedule', function ($q) {
                $q->where('status', 'completed');
            });

        $pendingCount = (clone $baseQuery)->whereNull('exam_score')->count();
        $evaluatedCount = (clone $baseQuery)->whereNotNull('exam_score')->count();

        if ($filter === 'ungraded') {
            $baseQuery->whereNull('exam_score');
        } elseif ($filter === 'graded') {
            $baseQuery->whereNotNull('exam_score');
        }

        if ($search) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('id', $search)
                  ->orWhereHas('applicant', function ($qApp) use ($search) {
                      $qApp->where('first_name', 'like', "%{$search}%")
                           ->orWhere('last_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('schedule', function ($qSched) use ($search) {
                      $qSched->where('room', 'like', "%{$search}%");
                  });
            });
        }

        $exams = $baseQuery
            ->orderByRaw('exam_score IS NOT NULL')
            ->latest('updated_at')
            ->paginate($perPage);

        return response()->json([
            'exams' => $exams,
            'pending_count' => $pendingCount,
            'evaluated_count' => $evaluatedCount
        ]);
    }
}
