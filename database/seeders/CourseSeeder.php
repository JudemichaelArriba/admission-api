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

            $courseCode = $p['course_code'] ?? $p['code'] ?? null;

            if (!$courseCode) {
                continue;
            }

            Course::updateOrCreate(
                [
                    // ✅ SAFE UNIQUE KEY (prevents duplicates)
                    'course_code' => $courseCode,
                ],
                [
                    // ✅ External system ID stored safely
                    'registrar_id' => $p['id'] ?? null,

                    'course_name' => $p['course_name'] ?? $p['name'] ?? 'N/A',
                    'department'  => $p['department'] ?? 'General',
                    'status'      => $p['status'] ?? 'active',
                ]
            );
        }
    }
}