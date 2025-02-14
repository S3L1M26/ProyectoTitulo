<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSipUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'sip_id' => [
                'required',
                'numeric',
                Rule::unique('sip_accounts', 'sip_user_id'),
                Rule::unique('asterisk.ps_aors', 'id'),
                Rule::unique('asterisk.ps_auths', 'id'),
                Rule::unique('asterisk.ps_endpoints', 'id')
            ],
            'password' => 'required|min:8',
        ];
    }
}
