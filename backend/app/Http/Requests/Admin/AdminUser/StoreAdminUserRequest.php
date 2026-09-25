<?php

namespace App\Http\Requests\Admin\AdminUser;

use App\Enums\UserRole;
use App\Rules\PasswordRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', new PasswordRule()],
            'role' => ['nullable', Rule::in([UserRole::Admin->value])],
            'phone' => ['nullable', 'string', 'max:20'],
            'telegram_id' => ['required', 'string', 'max:255', 'unique:users,telegram_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'telegram_id.required' => 'Telegram User ID is required.',
            'telegram_id.unique' => 'This Telegram ID is already registered.',
        ];
    }
}
