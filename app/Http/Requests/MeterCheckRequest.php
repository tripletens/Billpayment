<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MeterCheckRequest extends FormRequest
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
            'meter' => 'required|string|max:20',
            'disco' => 'required|string|in:AEDC,EKEDC,IKEDC,IBEDC,JEDC,KEDCO,KAEDCO,PHED,EEDC,BEDC',
            'vendType' => 'required|string|in:PREPAID,POSTPAID',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'meter.required' => 'Meter number is required.',
            'disco.required' => 'Distribution company (disco) is required.',
            'disco.in' => 'Invalid disco. Allowed values: AEDC, EKEDC, IKEDC, IBEDC, JEDC, KEDCO, KAEDCO, PHED, EEDC, BEDC',
            'vendType.required' => 'Vend type is required.',
            'vendType.in' => 'Vend type must be either PREPAID or POSTPAID.',
        ];
    }
}
