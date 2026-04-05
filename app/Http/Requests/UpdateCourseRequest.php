<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $courseId = $this->route('id'); 
        return [
            'course_code' => 'sometimes|string|max:50|unique:courses,course_code,' . $courseId,
            'course_name' => 'sometimes|string|max:255',
            'units'       => 'sometimes|integer|min:1',
            'department'  => 'sometimes|string|max:255',
            'status'      => 'sometimes|string|in:active,inactive',
            'type'        => 'sometimes|string',
            'description' => 'nullable|string',
        ];
    }
}
