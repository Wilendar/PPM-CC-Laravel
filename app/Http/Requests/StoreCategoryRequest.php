<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Category;

/**
 * StoreCategoryRequest - Validation dla tworzenia nowych kategorii
 * 
 * Enterprise validation rules dla Category model:
 * - Tree structure integrity (max depth, circular reference prevention)
 * - Slug uniqueness i SEO validation
 * - Business rules compliance
 * - Performance optimized parent validation
 * 
 * @package App\Http\Requests
 * @version 1.0
 * @since FAZA A - Core Models Implementation
 */
class StoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller/policies
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * Tree-specific validation rules:
     * - Parent existence i depth validation
     * - Slug uniqueness
     * - Name requirements
     * - Sort order consistency
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // === TREE STRUCTURE ===
            'parent_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            // === BASIC CATEGORY INFO ===
            'name' => [
                'required',
                'string',
                'max:300',
                'min:2',
            ],
            'slug' => [
                'nullable',
                'string',
                'max:300',
                'unique:categories,slug',
                'regex:/^[a-z0-9\-]+$/', // URL-safe characters only
            ],
            'description' => [
                'nullable',
                'string',
                'max:2000', // Reasonable limit for category descriptions
            ],

            // === ORDERING & STATUS ===
            'sort_order' => [
                'integer',
                'min:0',
                'max:9999',
            ],
            'is_active' => [
                'boolean',
            ],
            'icon' => [
                'nullable',
                'string',
                'max:200',
                'regex:/^[a-zA-Z0-9\-_\s]+$/', // Font-awesome or custom icon names
            ],

            // === SEO METADATA ===
            'meta_title' => [
                'nullable',
                'string',
                'max:300',
            ],
            'meta_description' => [
                'nullable',
                'string',
                'max:300',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'parent_id' => 'kategoria nadrzędna',
            'name' => 'nazwa kategorii',
            'slug' => 'slug URL',
            'description' => 'opis kategorii',
            'sort_order' => 'kolejność sortowania',
            'is_active' => 'status aktywności',
            'icon' => 'ikona',
            'meta_title' => 'tytuł SEO',
            'meta_description' => 'opis SEO',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'parent_id.exists' => 'Wybrana kategoria nadrzędna nie istnieje.',
            
            'name.required' => 'Nazwa kategorii jest wymagana.',
            'name.min' => 'Nazwa kategorii musi mieć minimum 2 znaki.',
            'name.max' => 'Nazwa kategorii nie może być dłuższa niż 300 znaków.',
            
            'slug.unique' => 'Slug URL już istnieje.',
            'slug.regex' => 'Slug może zawierać tylko małe litery, cyfry i myślniki.',
            'slug.max' => 'Slug nie może być dłuższy niż 300 znaków.',
            
            'description.max' => 'Opis kategorii nie może przekraczać 2000 znaków.',
            
            'sort_order.integer' => 'Kolejność sortowania musi być liczbą całkowitą.',
            'sort_order.min' => 'Kolejność sortowania nie może być ujemna.',
            'sort_order.max' => 'Kolejność sortowania jest za duża.',
            
            'icon.max' => 'Nazwa ikony nie może być dłuższa niż 200 znaków.',
            'icon.regex' => 'Nazwa ikony zawiera nieprawidłowe znaki.',
            
            'meta_title.max' => 'Tytuł SEO nie może być dłuższy niż 300 znaków.',
            'meta_description.max' => 'Opis SEO nie może być dłuższy niż 300 znaków.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            // Name cleanup
            'name' => trim($this->name ?? ''),
            
            // Slug cleanup
            'slug' => $this->slug ? strtolower(trim($this->slug)) : null,
            
            // Description cleanup
            'description' => $this->description ? trim($this->description) : null,
            
            // Icon cleanup
            'icon' => $this->icon ? trim($this->icon) : null,
            
            // Boolean normalization
            'is_active' => $this->boolean('is_active', true), // Default to active
            
            // Integer normalization
            'sort_order' => $this->integer('sort_order', 0),
            'parent_id' => $this->filled('parent_id') ? (int)$this->parent_id : null,
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom tree-specific validation
            
            // Check maximum tree depth
            if ($this->filled('parent_id')) {
                $parent = Category::find($this->parent_id);
                
                if ($parent && $parent->level >= Category::MAX_LEVEL) {
                    $validator->errors()->add(
                        'parent_id',
                        'Nie można tworzyć kategorii na tym poziomie. Maksymalna głębokość to ' . (Category::MAX_LEVEL + 1) . ' poziomów.'
                    );
                }
            }
            
            // Validate slug uniqueness within same parent (optional business rule)
            if ($this->filled('slug') && $this->filled('parent_id')) {
                $existingSlugInParent = Category::where('slug', $this->slug)
                    ->where('parent_id', $this->parent_id)
                    ->exists();
                
                if ($existingSlugInParent) {
                    $validator->errors()->add(
                        'slug',
                        'Slug już istnieje w tej kategorii nadrzędnej.'
                    );
                }
            }
            
            // Validate icon format (basic Font Awesome validation)
            if ($this->filled('icon') && !$this->isValidIcon($this->icon)) {
                $validator->errors()->add(
                    'icon',
                    'Nieprawidłowy format ikony. Użyj nazwy Font Awesome (np. "fas fa-folder") lub nazwy pliku.'
                );
            }
            
            // Check for reserved category names
            if ($this->filled('name') && $this->isReservedName($this->name)) {
                $validator->errors()->add(
                    'name',
                    'Ta nazwa kategorii jest zarezerwowana przez system.'
                );
            }
        });
    }

    /**
     * Validate icon format
     */
    private function isValidIcon(?string $icon): bool
    {
        if (!$icon) return true;
        
        // Font Awesome pattern (fas fa-icon-name, far fa-icon-name, etc.)
        $fontAwesomePattern = '/^(fas|far|fal|fab|fad)\s+fa-[a-z0-9\-]+$/';
        
        // Custom icon file pattern (icon-name.svg, icon-name.png, etc.)
        $customIconPattern = '/^[a-z0-9\-_]+\.(svg|png|jpg|jpeg|gif)$/';
        
        // Simple icon name pattern
        $simpleNamePattern = '/^[a-z0-9\-_]+$/';
        
        return preg_match($fontAwesomePattern, $icon) || 
               preg_match($customIconPattern, $icon) || 
               preg_match($simpleNamePattern, $icon);
    }

    /**
     * Check for reserved category names
     */
    private function isReservedName(string $name): bool
    {
        $reserved = [
            'admin', 'api', 'root', 'system', 'config', 'settings',
            'home', 'index', 'search', 'category', 'categories',
            'product', 'products', 'user', 'users', 'dashboard'
        ];
        
        return in_array(strtolower($name), $reserved);
    }

    /**
     * Get validated data with computed fields
     * 
     * Override to add computed fields after validation
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        
        // Auto-generate slug if not provided
        if (empty($validated['slug']) && !empty($validated['name'])) {
            $validated['slug'] = $this->generateSlugFromName($validated['name']);
        }
        
        return $key ? data_get($validated, $key, $default) : $validated;
    }

    /**
     * Generate slug from category name
     */
    private function generateSlugFromName(string $name): string
    {
        // Basic slug generation (could be enhanced with transliteration)
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9\s\-]/', '', $slug);
        $slug = preg_replace('/[\s\-]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Ensure uniqueness
        $baseSlug = $slug;
        $counter = 1;
        
        while (Category::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}