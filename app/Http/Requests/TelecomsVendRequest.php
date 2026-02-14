<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TelecomsVendRequest extends FormRequest
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
            'amount' => 'required|numeric|min:0',
            'type' => 'required|in:airtime,data',
            'phone_number' => 'required|string|regex:/^[0-9+\-\s()]+$/|max:20',
            'provider' => 'nullable|string|max:255',
            'network' => 'nullable|string|in:mtn,glo,airtel,9mobile', // Add specific networks
            'data_plan' => 'required_if:type,data|string|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.in' => 'Type must be either airtime or data.',
            'phone_number.regex' => 'Please provide a valid phone number.',
            'data_plan.required_if' => 'Data plan is required when purchasing data.',
        ];
    }
}