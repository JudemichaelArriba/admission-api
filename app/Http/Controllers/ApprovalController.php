<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Applicant;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{
    private const REQUIRED_DOCUMENT_TYPES = [
        'birth_certificate',
        'report_card',
    ];

    public function approve(Request $request, int $id)
    {
        if (!$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $applicant = Applicant::with('documents', 'exams')->find($id);
        if (!$applicant) {
            return response()->json(['message' => 'Applicant not found'], 404);
        }

        if ($applicant->status !== Applicant::STATUS_PENDING) {
            return response()->json(['message' => 'Only pending applicants can be approved'], 409);
        }

        $missing = $this->missingRequiredDocuments($applicant);
        if (!empty($missing)) {
            return response()->json([
                'message' => 'Required documents are missing',
                'missing_documents' => array_values($missing),
            ], 422);
        }

        $latestExam = $applicant->exams()->latest('exam_date')->latest('id')->first();
        if (!$latestExam || $latestExam->status !== 'evaluated') {
            return response()->json(['message' => 'Applicant must have an evaluated exam before approval'], 422);
        }

        DB::transaction(function () use ($request, $applicant, $latestExam) {
            $applicant->forceFill(['status' => Applicant::STATUS_APPROVED])->save();
            $this->logAudit($request, 'applicant_approved', 'applicant', $applicant->id, [
                'exam_id' => $latestExam->id,
                'exam_score' => $latestExam->exam_score,
            ]);
        });

        return response()->json([
            'message' => 'Applicant approved',
            'applicant' => $applicant->fresh('course', 'documents', 'exams'),
        ]);
    }

    public function reject(Request $request, int $id)
    {
        if (!$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $applicant = Applicant::find($id);
        if (!$applicant) {
            return response()->json(['message' => 'Applicant not found'], 404);
        }

        if (!in_array($applicant->status, [Applicant::STATUS_PENDING, Applicant::STATUS_APPROVED], true)) {
            return response()->json(['message' => 'This applicant cannot be rejected from the current state'], 409);
        }

        DB::transaction(function () use ($request, $applicant, $validated) {
            $applicant->forceFill(['status' => Applicant::STATUS_REJECTED])->save();
            $this->logAudit($request, 'applicant_rejected', 'applicant', $applicant->id, [
                'reason' => $validated['reason'],
            ]);
        });

        return response()->json([
            'message' => 'Applicant rejected',
            'applicant' => $applicant->fresh(),
        ]);
    }

    public function enroll(Request $request, int $id)
    {
        if (!$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'enrolled_at' => 'nullable|date',
        ]);

        $applicant = Applicant::with('student')->find($id);
        if (!$applicant) {
            return response()->json(['message' => 'Applicant not found'], 404);
        }

        if ($applicant->status !== Applicant::STATUS_APPROVED) {
            return response()->json(['message' => 'Only approved applicants can be enrolled'], 409);
        }

        if ($applicant->student) {
            return response()->json(['message' => 'Applicant is already linked to a student record'], 409);
        }

        $student = DB::transaction(function () use ($request, $applicant, $validated) {
            $enrolledAt = $validated['enrolled_at'] ?? now()->toDateTimeString();
            $student = Student::create([
                'applicant_id' => $applicant->id,
                'student_number' => $this->generateStudentNumber($applicant->id),
                'enrolled_at' => $enrolledAt,
            ]);

            $applicant->forceFill(['status' => Applicant::STATUS_ENROLLED])->save();

            $this->logAudit($request, 'applicant_enrolled', 'applicant', $applicant->id, [
                'student_id' => $student->id,
                'student_number' => $student->student_number,
            ]);

            return $student;
        });

        return response()->json([
            'message' => 'Applicant enrolled',
            'applicant' => $applicant->fresh('student'),
            'student' => $student,
        ]);
    }

    private function missingRequiredDocuments(Applicant $applicant): array
    {
        $existingTypes = $applicant->documents
            ->pluck('document_type')
            ->map(static fn (string $type) => strtolower($type))
            ->unique()
            ->toArray();

        return array_diff(self::REQUIRED_DOCUMENT_TYPES, $existingTypes);
    }

    private function generateStudentNumber(int $applicantId): string
    {
        return 'STU-' . now()->format('Y') . '-' . str_pad((string) $applicantId, 6, '0', STR_PAD_LEFT);
    }
}
