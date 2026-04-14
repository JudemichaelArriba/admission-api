<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Course;
use App\Http\Requests\CreateCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CoursesController extends Controller
{
    public function index(Request $request)
    {
        if (!Cache::has('courses_synced_recently')) {
            $this->syncFromRegistrar();
        }
        $courses = Course::active()->latest('id')->get();

        return response()->json($courses, 200);
    }
    public function syncFromRegistrar()
    {
        $apiUrl = env('REGISTRAR_API_URL');

        if (!$apiUrl) {
            Log::error('Registrar API URL is missing from .env');
            return false;
        }

        try {
            $response = Http::get($apiUrl);

            if (!$response->successful()) {
                Log::error('Registrar API request failed');
                return false;
            }

            $programs = $response->json();

            $incomingCodes = [];

            DB::beginTransaction();

            foreach ($programs as $p) {

                $courseCode = $p['course_code'] ?? $p['code'] ?? null;

                if (!$courseCode) {
                    continue;
                }

                $incomingCodes[] = $courseCode;

                Course::updateOrCreate(
                    [
                        
                        'course_code' => $courseCode,
                    ],
                    [
                      
                        'registrar_id' => $p['id'] ?? null,

                        'course_name' => $p['course_name'] ?? $p['name'] ?? 'N/A',
                        'department'  => $p['department'] ?? 'General',
                        'status'      => $p['status'] ?? 'active',
                    ]
                );
            }

          
            Course::whereNotIn('course_code', $incomingCodes)->delete();

            DB::commit();

            Cache::put('courses_synced_recently', true, now()->addHour());

            return true;
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error("Course Sync Failed: " . $e->getMessage());

            return false;
        }
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
