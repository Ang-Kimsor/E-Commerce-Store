<?php

namespace App\Http\Requests\Admin\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                Rule::exists('users', 'id')->whereNull('deleted_at')->where('is_active', true),
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],

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
            'address.latitude' => ['nullable', 'numeric'],
            'address.longitude' => ['nullable', 'numeric'],
            'address.notes' => ['nullable', 'string'],
            'address.is_default' => ['nullable', 'boolean'],

            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'notes' => ['nullable', 'string'],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'status_remark' => ['required', 'string', 'max:2000'],
            'payment_status_remark' => ['required', 'string', 'max:2000'],
            'created_by' => [
                'required',
                Rule::exists('users', 'id')->whereNull('deleted_at')->where('is_active', true),
            ],
            'payment_receipt' => ['nullable', 'file', 'image', 'max:5120'],
        ];
    }
}
