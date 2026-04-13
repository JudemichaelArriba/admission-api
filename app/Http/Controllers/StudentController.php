<?php
namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Student;
use App\Http\Resources\AdminStudentResource; 
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        // Allow API key clients (no session user) OR admin users
        $user = $request->user();

        if ($user !== null && !$user->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $students = Student::with(['applicant.course'])->latest('enrolled_at')->get();
        return AdminStudentResource::collection($students);
    }

    public function show(Request $request, string $id)
    {
        $user = $request->user();

        if ($user !== null && !$user->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $student = Student::with(['applicant.course'])->find($id);

        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        return new AdminStudentResource($student);
    }
}