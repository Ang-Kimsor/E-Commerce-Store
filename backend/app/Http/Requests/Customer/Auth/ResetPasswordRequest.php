<?php

namespace App\Http\Requests\Customer\Auth;

use App\Rules\PasswordRule;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email' => 'required|string|email',
            'reset_token' => 'required|string',
            'password' => ['required', 'string', 'confirmed', new PasswordRule()],
        ];
    }
}
