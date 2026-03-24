<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicantRequest extends FormRequest
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
        $applicantId = $this->route('id');

        return [
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('applicants', 'email')->ignore($applicantId),
            ],
            'phone_number' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string',
            'course_id' => 'sometimes|exists:courses,id',
            'status' => 'prohibited',
        ];
    }
}
