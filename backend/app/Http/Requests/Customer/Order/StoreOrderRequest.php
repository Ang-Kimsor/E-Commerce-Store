<?php

namespace App\Http\Requests\Customer\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],

            'address_id' => ['nullable', 'exists:addresses,id'],
            'address' => ['nullable', 'array'],
            'address.label' => ['required_without:address_id', 'string', 'max:255'],
            'address.name' => ['required_without:address_id', 'string', 'max:255'],
            'address.phone' => ['nullable', 'string', 'max:50'],
            'address.address_line_1' => ['nullable', 'string', 'max:255'],
            'address.address_line_2' => ['nullable', 'string', 'max:255'],
            'address.village' => ['nullable', 'string', 'max:255'],
            'address.commune' => ['nullable', 'string', 'max:255'],
            'address.district' => ['nullable', 'string', 'max:255'],
            'address.province' => ['required_without:address_id', 'string', 'max:255'],
            'address.notes' => ['nullable', 'string'],
            'address.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'address.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
