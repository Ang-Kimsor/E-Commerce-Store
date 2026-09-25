<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products')],
            'slug' => ['required', 'string', 'max:255', Rule::unique('products')],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'stock' => ['required', 'integer', 'min:0'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
            'image' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
        ];
    }
}
