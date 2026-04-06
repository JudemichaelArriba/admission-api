<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Applicant;
use App\Models\Student;
use App\Http\Requests\UpdateApplicantStatusRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApprovalController extends Controller
{

    public function updateStatus(UpdateApplicantStatusRequest $request, int $id)
    {

        $validated = $request->validated();

        switch ($validated['action']) {
            case 'approve':
                return $this->approveApplicant($request, $id);
            case 'reject':
                return $this->rejectApplicant($request, $id, $validated['reason'] ?? null);
            case 'enroll':
                return $this->enrollApplicant($request, $id, $validated['enrolled_at'] ?? null);
            default:
                return response()->json(['message' => 'Invalid action'], 422);
        }
    }


    public function approveApplicant(Request $request, int $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $applicant = Applicant::findOrFail($id);

            if ($applicant->status === Applicant::STATUS_APPROVED) {
                return response()->json(['message' => 'Applicant is already approved'], 400);
            }

            if (!$this->hasAtLeastOneDocument($applicant)) {
                return response()->json([
                    'message' => 'Cannot approve applicant. No documents have been uploaded yet.',
                ], 422);
            }

            $applicant->update(['status' => Applicant::STATUS_APPROVED]);

            $student = Student::create([
                'applicant_id' => $applicant->id,
                'student_number' => $this->generateStudentNumber($applicant->id),
                'enrolled_at' => now(),
            ]);

            $this->logAudit($request, 'applicant_approved', 'applicant', $applicant->id, [
                'student_number' => $student->student_number
            ]);

            return response()->json([
                'message' => 'Applicant approved and student record created',
                'student_number' => $student->student_number,
                'applicant' => $applicant->fresh()
            ]);
        });
    }

    private function rejectApplicant(Request $request, int $id, ?string $reason)
    {
        $applicant = Applicant::findOrFail($id);

        if (!in_array($applicant->status, [Applicant::STATUS_PENDING, Applicant::STATUS_APPROVED], true)) {
            return response()->json(['message' => 'This applicant cannot be rejected from the current state'], 409);
        }

        DB::transaction(function () use ($request, $applicant, $reason) {
            $applicant->update(['status' => Applicant::STATUS_REJECTED]);

            $this->logAudit($request, 'applicant_rejected', 'applicant', $applicant->id, [
                'reason' => $reason
            ]);
        });

        return response()->json([
            'message' => 'Applicant rejected',
            'applicant' => $applicant->fresh(),
        ]);
    }


    private function enrollApplicant(Request $request, int $id, ?string $enrolledAt)
    {
        $applicant = Applicant::with('student')->findOrFail($id);

        if ($applicant->status !== Applicant::STATUS_APPROVED) {
            return response()->json(['message' => 'Only approved applicants can be enrolled'], 409);
        }

        $student = DB::transaction(function () use ($request, $applicant, $enrolledAt) {
            $effectiveDate = $enrolledAt ?? now();


            $student = Student::firstOrCreate(
                ['applicant_id' => $applicant->id],
                [
                    'student_number' => $this->generateStudentNumber($applicant->id),
                    'enrolled_at' => $effectiveDate
                ]
            );

            $applicant->update(['status' => Applicant::STATUS_ENROLLED]);
            $this->logAudit($request, 'applicant_enrolled', 'applicant', $applicant->id, [
                'student_id' => $student->student_number,
                'enrolled_at' => $effectiveDate,
            ]);

            return $student;
        });

        return response()->json([
            'message' => 'Applicant enrolled successfully',
            'applicant' => $applicant->fresh('student'),
            'student' => $student,
        ]);
    }


    private function hasAtLeastOneDocument(Applicant $applicant): bool
    {
        return $applicant->documents()->exists();
    }


    private function generateStudentNumber(int $applicantId): string
    {
        return 'STU-' . now()->format('Y') . '-' . str_pad((string) $applicantId, 6, '0', STR_PAD_LEFT);
    }
}
