<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\EntranceExam;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    // POST /api/applicants/{id}/exams
    public function schedule(Request $request, $id)
    {
        $applicant = Applicant::find($id);
        if (!$applicant) {
            return response()->json(['message' => 'Applicant not found'], 404);
        }

        $validated = $request->validate([
            'exam_date' => 'required|date',
        ]);

        $exam = EntranceExam::create([
            'applicant_id' => $applicant->id,
            'exam_date' => $validated['exam_date'],
            'status' => 'scheduled',
        ]);

        return response()->json($exam, 201);
    }

    // POST /api/applicants/{id}/exams/evaluate
    public function evaluate(Request $request, $id)
    {
        $exam = EntranceExam::where('applicant_id', $id)->latest()->first();
        if (!$exam) {
            return response()->json(['message' => 'Exam not found'], 404);
        }

        $validated = $request->validate([
            'exam_score' => 'required|numeric|min:0|max:100',
        ]);

        $exam->update([
            'exam_score' => $validated['exam_score'],
            'status' => 'evaluated',
        ]);

        return response()->json($exam);
    }

    // GET /api/applicants/{id}/exams
    public function index($id)
    {
        $applicant = Applicant::with('exams')->find($id);
        if (!$applicant) {
            return response()->json(['message' => 'Applicant not found'], 404);
        }
        return response()->json($applicant->exams);
    }
}