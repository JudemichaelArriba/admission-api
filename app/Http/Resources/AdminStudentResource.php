<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminStudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        return [
            'student_number' => $this->student_number,
            'enrolled_at'    => $this->enrolled_at?->format('Y-m-d'),
            

            'first_name'     => $this->applicant?->first_name,
            'last_name'      => $this->applicant?->last_name,
            'email'          => $this->applicant?->email,
            'phone_number'   => $this->applicant?->phone_number,
            
            'course_id'      => $this->applicant?->course_id,
            'course' => [
                'course_code' => $this->applicant?->course?->course_code,
                'course_name' => $this->applicant?->course?->course_name,
            ],
        ];
    }
}
