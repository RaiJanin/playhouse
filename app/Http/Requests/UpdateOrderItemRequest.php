<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'qr_child' => 'nullable|string|max:50',
            'qr_guardian' => 'nullable|string|max:50',
            'durations_id' => 'required|exists:duration_prices,id',
            'socksqty' => 'required|integer|min:0',
            'others_amnt' => 'nullable|numeric|min:0',
            'disc_code' => 'nullable|string',
            'out_for_break' => 'boolean',
            'in_from_break' => 'boolean',
            'child_age' => 'nullable|integer|min:0|max:20',
            'guardian_name' => 'nullable|string|max:100',
            'guardian_mobileno' => 'nullable|string|max:20',
            'guardian_age' => 'nullable|integer|min:0|max:120',
            'guardian_authorized' => 'boolean',
        ];
    }
}
