<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBountyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'issue_url'      => ['required', 'string', 'url', 'max:500', 'regex:#^https://(github\.com|gitlab\.com)/.+/issues/\d+#i'],
            'amount_cents'   => ['required', 'integer', 'min:2000', 'max:5000000'],
            'public_message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'issue_url.regex'    => 'The URL must be a valid GitHub or GitLab issue URL.',
            'amount_cents.min'   => 'The minimum bounty amount is $20.00.',
            'amount_cents.max'   => 'The maximum bounty amount is $50,000.00.',
        ];
    }
}
