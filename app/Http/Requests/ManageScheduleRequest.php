<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManageScheduleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'exam_date' => ['required', 'date', 'after_or_equal:today'],
            'exam_end_time' => ['required', 'date', 'after:exam_date'],
            'room' => ['required', 'string', 'max:255'],
        ];
    }
}