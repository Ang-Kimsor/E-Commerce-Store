<?php

namespace App\Http\Requests\Admin\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminOrderRequest extends FormRequest
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
                Rule::exists('users', 'id')->where(function ($query) {
                    $order = $this->route('order');
                    if ($order && (int) $order->customer_id === (int) $this->input('customer_id')) {
                        return;
                    }
                    $query->whereNull('deleted_at')->where('is_active', true);
                }),
            ],
            'address_id' => ['nullable', 'exists:addresses,id'],
            'address' => ['nullable', 'array'],
            'address.label' => ['nullable', 'string', 'max:50'],
            'address.name' => ['required_without:address_id', 'string', 'max:255'],
            'address.phone' => ['required_without:address_id', 'string', 'max:50'],
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
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'payment_status' => ['required', Rule::enum(PaymentStatus::class)],
            'shipping_cost' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'status_remark' => ['nullable', 'string', 'max:2000'],
            'payment_status_remark' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string'],
            'created_by' => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    $order = $this->route('order');
                    if ($order && (int) $order->created_by === (int) $this->input('created_by')) {
                        return;
                    }
                    $query->whereNull('deleted_at')->where('is_active', true);
                }),
            ],
            'payment_receipt' => ['nullable', 'file', 'image', 'max:5120'],
            'items' => ['nullable', 'array', 'min:1'],
            'items.*.product_id' => ['required_with:items', 'exists:products,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
