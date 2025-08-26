<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JiraOAuthAuthorizeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'jira_base_url' => [
                'required',
                'string',
                'ends_with:atlassian.net,atlassian.net/',
            ],
        ];
    }
}
