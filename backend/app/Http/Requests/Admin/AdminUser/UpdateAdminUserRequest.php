<?php

namespace App\Http\Requests\Admin\AdminUser;

use App\Enums\UserRole;
use App\Rules\PasswordRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $admin = $this->route('admin');
        $adminId = is_object($admin) ? $admin->id : $admin;
        $targetUser = $admin instanceof \App\Models\User ? $admin : \App\Models\User::find($adminId);
        $isAdminRole = $targetUser ? $targetUser->role === UserRole::Admin : true;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($adminId)],
            'password' => ['nullable', 'string', new PasswordRule()],
            'role' => ['nullable', Rule::in([UserRole::Admin->value])],
            'phone' => ['nullable', 'string', 'max:20'],
            'telegram_id' => $isAdminRole
                ? ['sometimes', 'required', 'string', 'max:255', Rule::unique('users', 'telegram_id')->ignore($adminId)]
                : ['nullable', 'string', 'max:255', Rule::unique('users', 'telegram_id')->ignore($adminId)],
            'status' => ['sometimes', 'nullable'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            'remove_avatar' => ['nullable'],
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
