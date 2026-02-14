<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EntertainmentVendRequest extends FormRequest
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
            'provider' => 'nullable|string|max:255',
            'type' => 'required|string|in:cable_tv,internet,streaming', // Add specific types
            'smartcard_number' => 'required_if:type,cable_tv|string|max:50',
            'package_code' => 'nullable|string|max:50',
            'customer_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'smartcard_number.required_if' => 'Smartcard number is required for cable TV subscriptions.',
            'type.in' => 'Entertainment type must be cable_tv, internet, or streaming.',
        ];
    }
}