<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ElectricityVendRequest extends FormRequest
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
            'meter_number' => 'required|string|max:20',
            'disco' => 'required|string|in:AEDC,EKEDC,IKEDC,IBEDC,JEDC,KEDCO,KAEDCO,PHED,EEDC,BEDC', // Example list
            'amount' => 'required|numeric|min:100',
            'phone' => 'required|string|max:15',
            'customer_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
        ];
    }
}
