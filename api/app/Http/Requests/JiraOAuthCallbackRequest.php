<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JiraOAuthCallbackRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => 'required|string',
            'state' => 'required|string',
            'error' => 'sometimes|string',
            'error_description' => 'sometimes|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code.required' => 'OAuth authorization code is required',
            'state.required' => 'OAuth state parameter is required',
            'state.size' => 'OAuth state parameter must be exactly 40 characters',
        ];
    }
}
