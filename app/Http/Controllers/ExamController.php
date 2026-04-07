<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Applicant;
use App\Models\EntranceExam;
use App\Http\Requests\ManageEntranceExamRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

       
        $exam = EntranceExam::where('applicant_id', $id)->where('status', 'scheduled')->latest()->first();

        if (!$exam) {
            return response()->json(['message' => 'Scheduled exam not found for this applicant'], 404);
        }

        $exam->update([
            'exam_score' => $validated['exam_score'],
            'status' => 'evaluated',
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
