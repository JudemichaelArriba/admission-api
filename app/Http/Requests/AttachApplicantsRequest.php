<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttachApplicantsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'applicant_ids' => ['required', 'array'],
            'applicant_ids.*' => ['exists:applicants,id'],
        ];
    }
}