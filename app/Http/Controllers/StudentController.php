<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = (int) ($validated['per_page'] ?? 15);
        $students = Student::with(['applicant.course'])->latest('id')->paginate($perPage);
        return response()->json($students);
    }

    public function show(Request $request, int $id)
    {
        if (!$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $student = Student::with(['applicant.course'])->find($id);
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        return response()->json($student);
    }
}
