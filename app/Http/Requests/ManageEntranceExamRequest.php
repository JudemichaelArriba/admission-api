<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManageEntranceExamRequest extends FormRequest
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
        return [
            'action' => ['required', 'string', Rule::in(['schedule', 'evaluate'])],
            'exam_date' => [
                'required_if:action,schedule',
                'date',
                'after_or_equal:today',
                'prohibited_unless:action,schedule',
            ],
            'exam_score' => [
                'required_if:action,evaluate',
                'numeric',
                'min:0',
                'max:100',
                'prohibited_unless:action,evaluate',
            ],
        ];
    }
}
