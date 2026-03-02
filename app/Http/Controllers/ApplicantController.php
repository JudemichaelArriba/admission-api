<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Applicant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApplicantController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole(UserRole::APPLICANT)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $applicants = Applicant::with('course')->latest('id')->get();

        return response()->json($applicants);
    }

    public function show(Request $request, int $id)
    {
        $applicant = Applicant::with('course', 'documents', 'exams', 'student')->find($id);
        if (!$applicant) {
            return response()->json(['message' => 'Applicant not found'], 404);
        }

        if (!$this->canViewApplicant($request, $applicant)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($applicant);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole(UserRole::APPLICANT)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $applicant = Applicant::with('course', 'documents', 'exams', 'student')
            ->where('user_id', $user->id)
            ->first();

        if (!$applicant) {
            return response()->json(['message' => 'Applicant profile not found'], 404);
        }

        return response()->json($applicant);
    }




    // adding a applicants but not publivly only admin users
    public function store(Request $request)
    {
        if (!$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'email' => 'required|email|max:255|unique:applicants,email',
            'phone_number' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string',
            'course_id' => 'required|exists:courses,id',
            'status' => 'prohibited',
        ]);

        $applicant = Applicant::create($validated);
        $this->logAudit($request, 'applicant_created', 'applicant', $applicant->id);

        return response()->json($applicant, 201);
    }

    public function update(Request $request, int $id)
    {
        $applicant = Applicant::find($id);
        if (!$applicant) {
            return response()->json(['message' => 'Applicant not found'], 404);
        }

        if (!$this->canManageApplicant($request, $applicant)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('applicants', 'email')->ignore($id),
            ],
            'phone_number' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string',
            'course_id' => 'sometimes|exists:courses,id',
            'status' => 'prohibited',
        ]);

        $applicant->update($validated);
        $this->logAudit($request, 'applicant_updated', 'applicant', $applicant->id, [
            'fields' => array_keys($validated),
        ]);

        return response()->json($applicant);
    }

    public function destroy(Request $request, int $id)
    {
        if (!$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $applicant = Applicant::find($id);
        if (!$applicant) {
            return response()->json(['message' => 'Applicant not found'], 404);
        }

        $applicant->delete();
        $this->logAudit($request, 'applicant_deleted', 'applicant', $id);

        return response()->json(['message' => 'Applicant deleted']);
    }

    public function filterByStatus(Request $request)
    {
        if ($request->user()->hasRole(UserRole::APPLICANT)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in([
                Applicant::STATUS_PENDING,
                Applicant::STATUS_APPROVED,
                Applicant::STATUS_REJECTED,
                Applicant::STATUS_ENROLLED,
            ])],
        ]);

        $applicants = Applicant::with('course')
            ->where('status', $validated['status'])
            ->latest('id')
            ->get();

        return response()->json($applicants);
    }

    private function canViewApplicant(Request $request, Applicant $applicant): bool
    {
        $user = $request->user();
        return $user->hasRole(UserRole::ADMIN)
            || ($user->hasRole(UserRole::APPLICANT) && $applicant->user_id === $user->id);
    }

    private function canManageApplicant(Request $request, Applicant $applicant): bool
    {
        $user = $request->user();
        if ($user->hasRole(UserRole::ADMIN)) {
            return true;
        }

        return $user->hasRole(UserRole::APPLICANT)
            && $applicant->user_id === $user->id
            && $applicant->status === Applicant::STATUS_PENDING;
    }
}
