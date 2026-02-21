<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    // POST /api/applicants/{id}/approve
    public function approve($id)
    {
        $applicant = Applicant::find($id);
        if (!$applicant) {
            return response()->json(['message' => 'Applicant not found'], 404);
        }

        $applicant->update(['status' => 'approved']);

        return response()->json(['message' => 'Applicant approved', 'applicant' => $applicant]);
    }

    // POST /api/applicants/{id}/reject
    public function reject($id)
    {
        $applicant = Applicant::find($id);
        if (!$applicant) {
            return response()->json(['message' => 'Applicant not found'], 404);
        }

        $applicant->update(['status' => 'rejected']);

        return response()->json(['message' => 'Applicant rejected', 'applicant' => $applicant]);
    }

    // POST /api/applicants/{id}/enroll
    public function enroll($id)
    {
        $applicant = Applicant::find($id);
        if (!$applicant) {
            return response()->json(['message' => 'Applicant not found'], 404);
        }

        if ($applicant->status !== 'approved') {
            return response()->json(['message' => 'Only approved applicants can be enrolled'], 400);
        }

        $applicant->update(['status' => 'enrolled']);

        return response()->json(['message' => 'Applicant enrolled', 'applicant' => $applicant]);
    }
}