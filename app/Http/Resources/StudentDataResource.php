<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentDataResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user_id'    => $this->user_id,
            'full_name'  => trim("{$this->first_name} {$this->middle_name} {$this->last_name}"),
            'email'      => $this->email,
            'status'     => $this->status,
            'course_id'  => $this->course_id,

            $this->mergeWhen($this->relationLoaded('course') && $this->course, [
                'course' => [
                    'course_code' => $this->course?->course_code,
                    'course_name' => $this->course?->course_name,
                    'department'  => $this->course?->department,
                ],
            ]),

            $this->mergeWhen($this->relationLoaded('student') && $this->student, [
                'student_info' => [
                    'student_number' => $this->student?->student_number,
                    'enrolled_at'    => $this->student?->enrolled_at?->format('Y-m-d H:i:s'),
                ],
            ]),
        ];
    }
}