<?php

namespace App\Http\Requests;

use App\Models\PaymentMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddPaymentRequest extends FormRequest
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
        $validCodes = PaymentMode::where('active', true)
            ->pluck('mp_code')
            ->push(PaymentMode::CHARGE_CODE)
            ->all();

        return [
            'payment_method' => ['required', Rule::in($validCodes)],
            'cash_tendered' => 'required_if:payment_method,' . PaymentMode::CASH_CODE . '|numeric|min:0',
            'amount' => 'required_unless:payment_method,' . PaymentMode::CASH_CODE . '|numeric|min:0.01',
            'charge_account_name' => 'required_if:payment_method,' . PaymentMode::CHARGE_CODE . '|string|max:255',
            'reference' => 'nullable|string|max:100',
            'remarks' => 'nullable|string|max:255',
        ];
    }
}
