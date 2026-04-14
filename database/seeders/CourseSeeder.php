<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $apiUrl = env('REGISTRAR_API_URL');

        if (!$apiUrl) {
            return;
        }

        $response = Http::get($apiUrl);

        if (!$response->successful()) {
            return;
        }

        $programs = $response->json();

        foreach ($programs as $p) {

            $courseCode  = $p['course_code'] ?? $p['code'] ?? null;
            $registrarId = $p['id'] ?? null;

            if (!$courseCode) {
                continue;
            }

            $course = null;

            if ($registrarId) {
                $course = Course::where('registrar_id', $registrarId)->first();
            }

            if (!$course) {
                $course = Course::where('course_code', $courseCode)->first();
            }

            if ($course) {

                $course->update([
                    'course_code' => $courseCode,
                    'registrar_id' => $registrarId ?? $course->registrar_id,
                    'course_name' => $p['course_name'] ?? $p['name'] ?? 'N/A',
                    'department'  => $p['department'] ?? 'General',
                    'status'      => $p['status'] ?? 'active',
                ]);
            } else {

                Course::create([
                    'course_code'  => $courseCode,
                    'registrar_id' => $registrarId,
                    'course_name'  => $p['course_name'] ?? $p['name'] ?? 'N/A',
                    'department'   => $p['department'] ?? 'General',
                    'status'       => $p['status'] ?? 'active',
                ]);
            }
        }
    }
}
