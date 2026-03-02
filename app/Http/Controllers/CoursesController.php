<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CoursesController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = (int) ($validated['per_page'] ?? 20);
        return response()->json(Course::latest('id')->paginate($perPage), 200);
    }

    public function show(int $id)
    {
        $course = Course::find($id);
        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        return response()->json($course, 200);
    }

    public function store(Request $request)
    {
        if (!$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:courses,code',
            'description' => 'nullable|string',
        ]);

        $course = Course::create($validated);
        $this->logAudit($request, 'course_created', 'course', $course->id);

        return response()->json($course, 201);
    }

    public function update(Request $request, int $id)
    {
        if (!$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $course = Course::find($id);
        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => ['nullable', 'string', 'max:50', Rule::unique('courses', 'code')->ignore($id)],
            'description' => 'nullable|string',
        ]);

        $course->update($validated);
        $this->logAudit($request, 'course_updated', 'course', $course->id, [
            'fields' => array_keys($validated),
        ]);

        return response()->json($course, 200);
    }

    public function destroy(Request $request, int $id)
    {
        if (!$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $course = Course::find($id);
        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        $course->delete();
        $this->logAudit($request, 'course_deleted', 'course', $id);

        return response()->json(['message' => 'Course deleted'], 200);
    }
}
