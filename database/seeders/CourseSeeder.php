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

        if ($apiUrl) {
            $response = Http::get($apiUrl);

            if ($response->successful()) {
                $programs = $response->json();

                foreach ($programs as $p) {
                    Course::updateOrCreate(
                        ['id' => $p['id']], 
                        [
                            'course_code' => $p['course_code'] ?? $p['code'] ?? 'N/A',
                            'course_name' => $p['course_name'] ?? $p['name'] ?? 'N/A',
                            'department'  => $p['department'] ?? 'General',
                            'status'      => $p['status'] ?? 'active',
                        
                        ]
                    );
                }
            }
        }
    }
}