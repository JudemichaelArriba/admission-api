<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [
            [
                'course_code' => 'BSIT',
                'course_name' => 'Bachelor of Science in Information Technology',
                'units'       => 120,
                'department'  => 'College of Computing Studies',
                'status'      => 'active',
                'type'        => 'undergraduate',
                'description' => 'Study of software development, networking, and IT systems.',
            ],
            [
                'course_code' => 'BSHM',
                'course_name' => 'Bachelor of Science in Hospitality Management',
                'units'       => 120,
                'department'  => 'College of Hospitality and Tourism',
                'status'      => 'active',
                'type'        => 'undergraduate',
                'description' => 'Focus on hotel operations, culinary arts, and tourism.',
            ],
            [
                'course_code' => 'BSED',
                'course_name' => 'Bachelor of Secondary Education',
                'units'       => 120,
                'department'  => 'College of Education',
                'status'      => 'active',
                'type'        => 'undergraduate',
                'description' => 'Training for future high school teachers.',
            ],
            [
                'course_code' => 'BSA',
                'course_name' => 'Bachelor of Science in Accountancy',
                'units'       => 150,
                'department'  => 'College of Business and Accountancy',
                'status'      => 'active',
                'type'        => 'undergraduate',
                'description' => 'Preparation for Certified Public Accountant (CPA) licensure.',
            ],
            [
                'course_code' => 'BSBA',
                'course_name' => 'Bachelor of Science in Business Administration',
                'units'       => 120,
                'department'  => 'College of Business and Accountancy',
                'status'      => 'active',
                'type'        => 'undergraduate',
                'description' => 'Study of business management, marketing, and finance.',
            ],
        ];

        foreach ($courses as $course) {
            Course::updateOrCreate(
                ['course_code' => $course['course_code']],
                $course                                   
            );
        }
    }
}