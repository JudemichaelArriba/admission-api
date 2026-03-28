<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Applicant;
use App\Http\Requests\ApplicantIndexRequest;
use Illuminate\Http\Request;
use App\Http\Requests\CreateApplicantRequest;
use App\Http\Requests\UpdateApplicantRequest;


class ApplicantController extends Controller
{
    public function index(ApplicantIndexRequest $request)
    {
        $user = $request->user();

        if ($user->hasRole(UserRole::APPLICANT)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validated();
        $query = Applicant::with('course')->latest('id');
        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        return response()->json($query->get());
    }

    public function show(Request $request, int $id)
    {
        if (!$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden', 403]);
        }

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

        $applicant = Applicant::with(['course', 'documents', 'exams'])
            ->where('user_id', $user->id)
            ->first();

        if (!$applicant) {
            return response()->json(['message' => 'Applicant profile not found'], 404);
        }

        return response()->json($applicant);
    }




    // adding a applicants but not publicly only admin users
    public function store(CreateApplicantRequest $request)
    {
        if (!$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validated();

        $applicant = Applicant::create($validated);
        $this->logAudit($request, 'applicant_created', 'applicant', $applicant->id);

        return response()->json($applicant, 201);
    }

    public function update(UpdateApplicantRequest $request, int $id)
    {
        $applicant = Applicant::find($id);
        if (!$applicant) {
            return response()->json(['message' => 'Applicant not found'], 404);
        }

        if (!$this->canManageApplicant($request, $applicant)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validated();

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
