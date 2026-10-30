<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Alta y edición de usuarios externos en GoRest (05 §3); PUT exige los 4 campos. */
class ExternalUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:150', 'ends_with:@discoteca.test'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
