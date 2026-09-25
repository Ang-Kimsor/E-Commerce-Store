<?php

namespace App\Http\Requests\Admin\Customer;

use App\Rules\PasswordRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $userId = is_object($user) ? $user->id : $user;

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $userId],
            'password' => ['nullable', 'string', new PasswordRule()],
            'telegram_id' => ['nullable', 'string', 'max:255', 'unique:users,telegram_id,' . $userId],
            'avatar' => ['nullable', 'image', 'max:5120'],
            'addresses' => ['nullable', 'array'],
            'addresses.*.id' => ['nullable', 'integer', 'exists:addresses,id'],
            'addresses.*.label' => ['required', 'string', 'max:255'],
            'addresses.*.name' => ['nullable', 'string', 'max:255'],
            'addresses.*.phone' => ['nullable', 'string', 'max:50'],
            'addresses.*.address_line_1' => ['nullable', 'string', 'max:255'],
            'addresses.*.address_line_2' => ['nullable', 'string', 'max:255'],
            'addresses.*.village' => ['nullable', 'string', 'max:100'],
            'addresses.*.commune' => ['nullable', 'string', 'max:100'],
            'addresses.*.district' => ['nullable', 'string', 'max:100'],
            'addresses.*.province' => ['required', 'string', 'max:100'],
            'addresses.*.latitude' => ['nullable', 'numeric'],
            'addresses.*.longitude' => ['nullable', 'numeric'],
            'addresses.*.is_default' => ['nullable', 'boolean'],
            'addresses.*.notes' => ['nullable', 'string'],
        ];
    }
}
