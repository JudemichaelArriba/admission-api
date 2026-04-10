<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Applicant;
use App\Models\EntranceExam;
use App\Http\Requests\ManageEntranceExamRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ExamController extends Controller
{
    public function manage(ManageEntranceExamRequest $request, int $id)
    {
        if (!$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validated();
        $action = $validated['action'];

        $applicantIds = collect([$id])
            ->concat($request->input('additional_applicant_ids', []))
            ->unique();

        if ($action === 'schedule') {
            return DB::transaction(function () use ($applicantIds, $validated, $request) {
                $createdExams = [];

                foreach ($applicantIds as $appId) {
                    $applicant = Applicant::find($appId);

                    if (!$applicant || $applicant->status !== Applicant::STATUS_PENDING) {
                        continue;
                    }

                    $exam = EntranceExam::create([
                        'applicant_id'  => $appId,
                        'exam_date'     => $validated['exam_date'],
                        'exam_end_time' => $validated['exam_end_time'],
                        'room'          => $validated['room'],
                        'status'        => 'scheduled',
                    ]);

                    $this->logAudit($request, 'entrance_exam_scheduled', 'entrance_exam', $exam->id, [
                        'applicant_id' => $appId,
                        'room'         => $exam->room,
                    ]);

                    $createdExams[] = $exam;
                }

                return response()->json([
                    'message' => count($createdExams) . ' applicants scheduled successfully.',
                    'data'    => $createdExams
                ], 201);
            });
        }

        $applicant = Applicant::find($id);

        if (!$applicant) {
            return response()->json(['message' => 'Applicant not found'], 404);
        }

        $exam = EntranceExam::where('applicant_id', $id)
            ->where('status', 'scheduled')
            ->latest()
            ->first();

        if (!$exam) {
            return response()->json(['message' => 'Scheduled exam not found for this applicant'], 404);
        }

        $examEndDateTime = Carbon::parse($exam->exam_end_time, config('app.timezone'));

        if (Carbon::now(config('app.timezone'))->lessThanOrEqualTo($examEndDateTime)) {
            return response()->json([
                'message' => 'Exam cannot be evaluated until it has fully concluded. The exam ends at ' . $examEndDateTime->toDateTimeString() . '.',
            ], 422);
        }

        $exam->update([
            'exam_score' => $validated['exam_score'],
            'status'     => 'evaluated',
        ]);

        return response()->json($exam);
    }

    public function index(Request $request, ?int $id = null)
    {
        $user = $request->user();

        if ($user->hasRole(UserRole::ADMIN)) {
            $query = EntranceExam::with('applicant');

            if ($id) {
                $applicant = Applicant::find($id);

                if (!$applicant) {
                    return response()->json(['message' => 'Applicant not found'], 404);
                }

                $query->where('applicant_id', $id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->query('status'));
            }

            return response()->json($query->latest()->get());
        }

        if ($user->hasRole(UserRole::APPLICANT)) {
            $applicant = Applicant::where('user_id', $user->id)->first();

            if (!$applicant) {
                return response()->json(['message' => 'Applicant profile not found'], 404);
            }

            $exams = EntranceExam::where('applicant_id', $applicant->id)
                ->latest()
                ->get();

            return response()->json($exams);
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }
}