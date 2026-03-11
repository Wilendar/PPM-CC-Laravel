<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "product_id" => "required|exists:products,id",
            "variant_sku" => "required|string|max:100|unique:product_variants,variant_sku|regex:/^[A-Z0-9\-_]+$/",
            "variant_name" => "required|string|max:200",
            "ean" => "nullable|string|max:20",
            "sort_order" => "integer|min:0",
            "inherit_prices" => "boolean",
            "inherit_stock" => "boolean", 
            "inherit_attributes" => "boolean",
            "is_active" => "boolean"
        ];
    }

    public function messages(): array
    {
        return [
            "variant_sku.required" => "Variant SKU is required",
            "variant_sku.unique" => "This variant SKU already exists",
            "variant_sku.regex" => "Variant SKU must contain only uppercase letters, numbers, hyphens and underscores",
            "variant_name.required" => "Variant name is required",
            "product_id.exists" => "Selected product does not exist"
        ];
    }
}
