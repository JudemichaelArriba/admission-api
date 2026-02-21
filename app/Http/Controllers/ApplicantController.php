<?php

namespace App\Http\Controllers;



use App\Models\Applicant;
use Illuminate\Http\Request;
class ApplicantController extends Controller
{
 
    public function index()
    {
        return response()->json(Applicant::with('course')->get());
    }

       public function show($id)
    {
        $applicant = Applicant::with('course', 'documents', 'exams')->find($id);
        if (!$applicant) {
            return response()->json(['message' => 'Applicant not found'], 404);
        }
        return response()->json($applicant);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'email' => 'required|email|unique:applicants,email',
            'phone_number' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string',
            'course_id' => 'required|exists:courses,id',
        ]);

        $applicant = Applicant::create($validated);

        return response()->json($applicant, 201);
    }

}
