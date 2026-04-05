<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Course;
use App\Http\Requests\CreateCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use Illuminate\Http\Request;

class CoursesController extends Controller
{
    public function index(Request $request)
    {
     
        $courses = Course::active()
            ->latest('id')
            ->get();

        return response()->json($courses, 200);
    }

    public function show(int $id)
    {
        $course = Course::find($id);
        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        return response()->json($course, 200);
    }

    public function store(CreateCourseRequest $request)
    {
        if (!$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validated();

        $course = Course::create($validated);
        $this->logAudit($request, 'course_created', 'course', $course->id);

        return response()->json($course, 201);
    }

    public function update(UpdateCourseRequest $request, int $id)
    {
        if (!$request->user()->hasRole(UserRole::ADMIN)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $course = Course::find($id);
        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        $validated = $request->validated();

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
