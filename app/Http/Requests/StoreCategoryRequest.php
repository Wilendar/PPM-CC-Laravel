<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "parent_id" => "nullable|exists:categories,id",
            "name" => "required|string|max:300",
            "slug" => "nullable|string|max:300|unique:categories,slug",
            "description" => "nullable|string|max:1000",
            "level" => "integer|min:0|max:4",
            "sort_order" => "integer|min:0",
            "is_active" => "boolean",
            "icon" => "nullable|string|max:200",
            "meta_title" => "nullable|string|max:300",
            "meta_description" => "nullable|string|max:300"
        ];
    }

    public function messages(): array
    {
        return [
            "name.required" => "Category name is required",
            "parent_id.exists" => "Selected parent category does not exist",
            "level.max" => "Category cannot be deeper than 5 levels"
        ];
    }
}
