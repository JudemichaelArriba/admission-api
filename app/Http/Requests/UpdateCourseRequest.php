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
            'name' => 'sometimes|string|max:255',
            'code' => ['nullable', 'string', 'max:50', Rule::unique('courses', 'code')->ignore($courseId)],
            'description' => 'nullable|string',
        ];
    }
}
