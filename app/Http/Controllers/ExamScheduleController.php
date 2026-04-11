<?php

namespace App\Http\Controllers;

use App\Models\ExamSchedule;
use App\Models\EntranceExam;
use App\Models\Applicant;
use App\Http\Requests\ManageScheduleRequest;
use App\Http\Requests\AttachApplicantsRequest;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamScheduleController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $schedules = ExamSchedule::with(['exams.applicant'])->latest()->get();
        return response()->json($schedules);
    }

    public function store(ManageScheduleRequest $request)
    {
        if (!$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $schedule = ExamSchedule::create($request->validated());
        return response()->json(['message' => 'Schedule created', 'data' => $schedule], 201);
    }

    public function update(ManageScheduleRequest $request, ExamSchedule $schedule)
    {
        if (!$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validated();
        if ($request->has('status') && in_array($request->status, ['upcoming', 'completed'])) {
            $data['status'] = $request->status;
        }

        $schedule->update($data);
        return response()->json(['message' => 'Schedule updated', 'data' => $schedule]);
    }

    public function destroy(Request $request, ExamSchedule $schedule)
    {
        if (!$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $schedule->delete();
        return response()->json(['message' => 'Schedule deleted successfully']);
    }

    public function addApplicants(AttachApplicantsRequest $request, ExamSchedule $schedule)
    {
        if (!$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if ($schedule->status === 'completed') {
            return response()->json([
                'message' => 'Action denied.',
                'errors' => ['Cannot add applicants to a completed exam schedule.']
            ], 422);
        }

        $applicantIds = $request->validated()['applicant_ids'];
        $createdExams = [];
        $errors = [];

        DB::transaction(function () use ($applicantIds, $schedule, &$createdExams, &$errors) {
            foreach ($applicantIds as $appId) {
                $applicant = Applicant::find($appId);

                if (!$applicant) {
                    $errors[] = "Applicant ID {$appId} cannot be found or is not on the system.";
                    continue;
                }
      
                if ($applicant->status !== Applicant::STATUS_PENDING) {
                    $errors[] = "Applicant ID {$appId} cannot be scheduled (Status is {$applicant->status}).";
                    continue;
                }

                $exists = EntranceExam::where('applicant_id', $appId)
                    ->where('exam_schedule_id', $schedule->id)
                    ->exists();

                if (!$exists) {
                    $createdExams[] = EntranceExam::create([
                        'applicant_id' => $appId,
                        'exam_schedule_id' => $schedule->id,
                        'status' => 'scheduled',
                    ]);
                } else {
                    $errors[] = "Applicant ID {$appId} is already assigned to this schedule.";
                }
            }
        });

        return response()->json([
            'message' => count($createdExams) . ' applicants processed.',
            'added'   => $createdExams,
            'errors'  => $errors 
        ], count($errors) > 0 && count($createdExams) === 0 ? 404 : 200);
    }

    public function removeApplicant(Request $request, ExamSchedule $schedule, $applicantId)
    {
        if (!$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if ($schedule->status === 'completed') {
            return response()->json([
                'message' => 'Cannot remove applicants from a completed exam schedule.'
            ], 422);
        }

        $exam = EntranceExam::where('exam_schedule_id', $schedule->id)
            ->where('applicant_id', $applicantId)
            ->first();

        if ($exam) {
            $exam->delete();
            return response()->json(['message' => 'Applicant removed from schedule.']);
        }

        return response()->json(['message' => 'Applicant not found in this schedule.'], 404);
    }
}