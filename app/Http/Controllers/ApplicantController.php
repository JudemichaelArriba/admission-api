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

    // PUT /api/applicants/{id}
    public function update(Request $request, $id)
    {
        $applicant = Applicant::find($id);
        if (!$applicant) {
            return response()->json(['message' => 'Applicant not found'], 404);
        }

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'email' => 'sometimes|email|unique:applicants,email,' . $id,
            'phone_number' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string',
            'course_id' => 'sometimes|exists:courses,id',
        ]);

        $applicant->update($validated);

        return response()->json($applicant);
    }

    // DELETE /api/applicants/{id}
    public function destroy($id)
    {
        $applicant = Applicant::find($id);
        if (!$applicant) {
            return response()->json(['message' => 'Applicant not found'], 404);
        }

        $applicant->delete();

        return response()->json(['message' => 'Applicant deleted']);
    }

    public function filterByStatus(Request $request)
    {
        $status = strtolower($request->query('status', ''));
        $applicants = Applicant::with('course')
            ->whereRaw('LOWER(status) = ?', [$status])
            ->get();

        if ($applicants->isEmpty()) {
            return response()->json(['message' => 'No applicants found for status: ' . $status], 200);
        }

        return response()->json($applicants, 200);
    }

}
