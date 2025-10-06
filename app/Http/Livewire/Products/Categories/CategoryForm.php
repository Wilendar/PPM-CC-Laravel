<?php

namespace App\Http\Livewire\Products\Categories;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

/**
 * CategoryForm Livewire Component - Advanced Category Create/Edit Form
 *
 * Enhanced Features:
 * - Auto slug generation from name
 * - Parent category selection with tree widget
 * - Advanced icon selection/upload system
 * - Category banner/image upload with crop
 * - SEO optimization fields
 * - Visibility and publishing settings
 * - Category-specific attributes configuration
 * - Default values for products in category
 * - Real-time form validation
 * - Rich description editor
 *
 * @package App\Http\Livewire\Products\Categories
 * @version 1.0
 * @since ETAP_05 - FAZA 3: Category Form Management
 */
class CategoryForm extends Component
{
    use WithFileUploads;

    // ==========================================
    // FORM STATE PROPERTIES
    // ==========================================

    /**
     * Category being edited (null for create mode)
     *
     * @var Category|null
     */
    public $category = null;

    /**
     * Form mode: create|edit
     *
     * @var string
     */
    public $mode = 'create';

    /**
     * Main category form data
     *
     * @var array
     */
    public $form = [
        'parent_id' => null,
        'name' => '',
        'slug' => '',
        'description' => '',
        'short_description' => '',
        'sort_order' => 0,
        'is_active' => true,
        'is_featured' => false,
    ];

    /**
     * SEO and metadata fields
     *
     * @var array
     */
    public $seoForm = [
        'meta_title' => '',
        'meta_description' => '',
        'meta_keywords' => '',
        'canonical_url' => '',
        'og_title' => '',
        'og_description' => '',
        'og_image' => '',
    ];

    /**
     * Visual settings
     *
     * @var array
     */
    public $visualForm = [
        'icon' => '',
        'icon_type' => 'font_awesome', // font_awesome, custom_upload, none
        'color_primary' => '#3B82F6',
        'color_secondary' => '#EFF6FF',
        'banner_position' => 'top', // top, side, background
        'display_style' => 'default', // default, card, minimal, featured
    ];

    /**
     * Category visibility and publishing settings
     *
     * @var array
     */
    public $visibilityForm = [
        'is_visible' => true,
        'show_in_menu' => true,
        'show_in_filter' => true,
        'show_product_count' => true,
        'min_products_to_show' => 0,
        'available_from' => null,
        'available_to' => null,
    ];

    /**
     * Default values for products in this category
     *
     * @var array
     */
    public $defaultsForm = [
        'default_tax_rate' => 23.00,
        'default_weight' => null,
        'default_dimensions' => [
            'height' => null,
            'width' => null,
            'length' => null,
        ],
        'suggested_price_groups' => [],
        'auto_attributes' => [],
    ];

    // ==========================================
    // FILE UPLOAD PROPERTIES
    // ==========================================

    /**
     * Uploaded category icon file
     *
     * @var \Livewire\TemporaryUploadedFile|null
     */
    public $iconUpload = null;

    /**
     * Uploaded category banner/image
     *
     * @var \Livewire\TemporaryUploadedFile|null
     */
    public $bannerUpload = null;

    /**
     * Icon preview URL
     *
     * @var string|null
     */
    public $iconPreview = null;

    /**
     * Banner preview URL
     *
     * @var string|null
     */
    public $bannerPreview = null;

    // ==========================================
    // UI STATE PROPERTIES
    // ==========================================

    /**
     * Current active tab
     *
     * @var string
     */
    public $activeTab = 'basic';

    /**
     * Available tabs
     *
     * @var array
     */
    public $tabs = [
        'basic' => 'Podstawowe',
        'seo' => 'SEO i Meta',
        'visual' => 'Wygląd',
        'visibility' => 'Widoczność',
        'defaults' => 'Domyślne wartości',
    ];

    /**
     * Auto-generate slug from name
     *
     * @var bool
     */
    public $autoSlug = true;

    /**
     * Show parent category tree
     *
     * @var bool
     */
    public $showParentTree = false;

    /**
     * Loading states for various operations
     *
     * @var array
     */
    public $loadingStates = [
        'save' => false,
        'upload_icon' => false,
        'upload_banner' => false,
        'generate_slug' => false,
    ];

    // ==========================================
    // VALIDATION RULES
    // ==========================================

    /**
     * Validation rules for form fields
     *
     * @var array
     */
    protected $rules = [
        'form.name' => 'required|string|max:300',
        'form.slug' => 'required|string|max:300|unique:categories,slug',
        'form.description' => 'nullable|string|max:5000',
        'form.short_description' => 'nullable|string|max:500',
        'form.parent_id' => 'nullable|exists:categories,id',
        'form.sort_order' => 'integer|min:0|max:9999',
        'form.is_active' => 'boolean',
        'form.is_featured' => 'boolean',

        'seoForm.meta_title' => 'nullable|string|max:300',
        'seoForm.meta_description' => 'nullable|string|max:300',
        'seoForm.meta_keywords' => 'nullable|string|max:500',
        'seoForm.canonical_url' => 'nullable|url|max:500',
        'seoForm.og_title' => 'nullable|string|max:300',
        'seoForm.og_description' => 'nullable|string|max:300',

        'visualForm.icon' => 'nullable|string|max:200',
        'visualForm.icon_type' => 'in:font_awesome,custom_upload,none',
        'visualForm.color_primary' => 'nullable|regex:/^#[a-fA-F0-9]{6}$/',
        'visualForm.color_secondary' => 'nullable|regex:/^#[a-fA-F0-9]{6}$/',
        'visualForm.banner_position' => 'in:top,side,background',
        'visualForm.display_style' => 'in:default,card,minimal,featured',

        'visibilityForm.is_visible' => 'boolean',
        'visibilityForm.show_in_menu' => 'boolean',
        'visibilityForm.show_in_filter' => 'boolean',
        'visibilityForm.show_product_count' => 'boolean',
        'visibilityForm.min_products_to_show' => 'integer|min:0|max:1000',
        'visibilityForm.available_from' => 'nullable|date',
        'visibilityForm.available_to' => 'nullable|date|after:visibilityForm.available_from',

        'defaultsForm.default_tax_rate' => 'nullable|numeric|min:0|max:100',
        'defaultsForm.default_weight' => 'nullable|numeric|min:0|max:99999',
        'defaultsForm.default_dimensions.height' => 'nullable|numeric|min:0|max:9999',
        'defaultsForm.default_dimensions.width' => 'nullable|numeric|min:0|max:9999',
        'defaultsForm.default_dimensions.length' => 'nullable|numeric|min:0|max:9999',

        'iconUpload' => 'nullable|image|max:2048|mimes:jpeg,jpg,png,webp',
        'bannerUpload' => 'nullable|image|max:5120|mimes:jpeg,jpg,png,webp',
    ];

    // ==========================================
    // LIVEWIRE LIFECYCLE METHODS
    // ==========================================

    /**
     * Component mount - initialize form for create or edit mode
     *
     * @param Category|null $category
     */
    public function mount(?Category $category = null): void
    {
        if ($category) {
            $this->category = $category;
            $this->mode = 'edit';
            $this->loadCategoryData();
        } else {
            $this->mode = 'create';
            $this->initializeDefaults();
        }

        // Update slug validation rule for edit mode
        if ($this->mode === 'edit' && $this->category) {
            $this->rules['form.slug'] = 'required|string|max:300|unique:categories,slug,' . $this->category->id;
        }
    }

    /**
     * Updated form name - auto-generate slug if enabled
     */
    public function updatedFormName($value): void
    {
        if ($this->autoSlug && !empty($value)) {
            $this->form['slug'] = $this->generateSlug($value);
        }
    }

    /**
     * Updated icon upload - generate preview
     */
    public function updatedIconUpload(): void
    {
        if ($this->iconUpload) {
            $this->iconPreview = $this->iconUpload->temporaryUrl();
            $this->visualForm['icon_type'] = 'custom_upload';
        }
    }

    /**
     * Updated banner upload - generate preview
     */
    public function updatedBannerUpload(): void
    {
        if ($this->bannerUpload) {
            $this->bannerPreview = $this->bannerUpload->temporaryUrl();
        }
    }

    // ==========================================
    // FORM DATA MANAGEMENT METHODS
    // ==========================================

    /**
     * Initialize default values for create mode
     */
    private function initializeDefaults(): void
    {
        // Get next sort order for same level
        $parentId = request('parent_id');
        if ($parentId) {
            $this->form['parent_id'] = $parentId;
        }

        $maxSortOrder = Category::where('parent_id', $parentId)->max('sort_order') ?? 0;
        $this->form['sort_order'] = $maxSortOrder + 10;

        // Set default colors based on level
        $level = $parentId ? Category::find($parentId)?->level + 1 : 0;
        $this->visualForm['color_primary'] = $this->getDefaultColor($level);
    }

    /**
     * Load existing category data for edit mode
     */
    private function loadCategoryData(): void
    {
        $category = $this->category;

        // Load basic form data
        $this->form = [
            'parent_id' => $category->parent_id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description ?? '',
            'short_description' => $category->short_description ?? '',
            'sort_order' => $category->sort_order,
            'is_active' => $category->is_active,
            'is_featured' => $category->is_featured ?? false,
        ];

        // Load SEO data
        $this->seoForm = [
            'meta_title' => $category->meta_title ?? '',
            'meta_description' => $category->meta_description ?? '',
            'meta_keywords' => $category->meta_keywords ?? '',
            'canonical_url' => $category->canonical_url ?? '',
            'og_title' => $category->og_title ?? '',
            'og_description' => $category->og_description ?? '',
            'og_image' => $category->og_image ?? '',
        ];

        // Load visual settings
        $visualSettings = $category->visual_settings ?? [];
        $this->visualForm = array_merge($this->visualForm, $visualSettings);

        // Load visibility settings
        $visibilitySettings = $category->visibility_settings ?? [];
        $this->visibilityForm = array_merge($this->visibilityForm, $visibilitySettings);

        // Load default values
        $defaultValues = $category->default_values ?? [];
        $this->defaultsForm = array_merge($this->defaultsForm, $defaultValues);

        // Set previews for existing files
        if ($category->icon_path) {
            $this->iconPreview = Storage::url($category->icon_path);
        }
        if ($category->banner_path) {
            $this->bannerPreview = Storage::url($category->banner_path);
        }
    }

    // ==========================================
    // SLUG GENERATION METHODS
    // ==========================================

    /**
     * Generate unique slug from name
     *
     * @param string $name
     * @return string
     */
    public function generateSlug(string $name): string
    {
        $this->loadingStates['generate_slug'] = true;

        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        // Ensure uniqueness
        while ($this->slugExists($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $this->loadingStates['generate_slug'] = false;
        return $slug;
    }

    /**
     * Check if slug exists in database
     *
     * @param string $slug
     * @return bool
     */
    private function slugExists(string $slug): bool
    {
        $query = Category::where('slug', $slug);

        if ($this->mode === 'edit' && $this->category) {
            $query->where('id', '!=', $this->category->id);
        }

        return $query->exists();
    }

    /**
     * Manual slug regeneration
     */
    public function regenerateSlug(): void
    {
        if (!empty($this->form['name'])) {
            $this->form['slug'] = $this->generateSlug($this->form['name']);
        }
    }

    /**
     * Toggle auto slug generation
     */
    public function toggleAutoSlug(): void
    {
        $this->autoSlug = !$this->autoSlug;

        if ($this->autoSlug && !empty($this->form['name'])) {
            $this->form['slug'] = $this->generateSlug($this->form['name']);
        }
    }

    // ==========================================
    // FILE UPLOAD METHODS
    // ==========================================

    /**
     * Process icon upload and generate optimized versions
     */
    public function processIconUpload(): void
    {
        if (!$this->iconUpload) {
            return;
        }

        $this->loadingStates['upload_icon'] = true;

        try {
            $filename = 'categories/icons/' . uniqid() . '_icon.' . $this->iconUpload->extension();

            // Optimize and resize icon
            $manager = ImageManager::gd();
            $image = $manager->read($this->iconUpload->getRealPath());
            $image->scale(64, 64);

            // Save optimized icon
            Storage::put($filename, (string) $image->encode());

            // Clean up old icon if exists
            if ($this->category && $this->category->icon_path) {
                Storage::delete($this->category->icon_path);
            }

            $this->visualForm['icon'] = $filename;
            $this->iconPreview = Storage::url($filename);

            session()->flash('message', 'Ikona została przesłana pomyślnie.');

        } catch (\Exception $e) {
            Log::error('CategoryForm: Icon upload error', [
                'error' => $e->getMessage(),
                'category_id' => $this->category?->id
            ]);

            session()->flash('error', 'Błąd podczas przesyłania ikony: ' . $e->getMessage());
        }

        $this->loadingStates['upload_icon'] = false;
    }

    /**
     * Process banner upload and generate optimized versions
     */
    public function processBannerUpload(): void
    {
        if (!$this->bannerUpload) {
            return;
        }

        $this->loadingStates['upload_banner'] = true;

        try {
            $filename = 'categories/banners/' . uniqid() . '_banner.' . $this->bannerUpload->extension();

            // Optimize and resize banner
            $manager = ImageManager::gd();
            $image = $manager->read($this->bannerUpload->getRealPath());
            $image->scale(1200, 400);

            // Save optimized banner
            Storage::put($filename, (string) $image->encode());

            // Generate thumbnail
            $thumbFilename = 'categories/banners/thumbs/' . basename($filename);
            $thumb = $manager->read($this->bannerUpload->getRealPath());
            $thumb->scale(300, 100);
            Storage::put($thumbFilename, (string) $thumb->encode());

            // Clean up old banner if exists
            if ($this->category && $this->category->banner_path) {
                Storage::delete($this->category->banner_path);
                Storage::delete(str_replace('/banners/', '/banners/thumbs/', $this->category->banner_path));
            }

            $this->seoForm['og_image'] = Storage::url($filename);
            $this->bannerPreview = Storage::url($filename);

            session()->flash('message', 'Banner został przesłany pomyślnie.');

        } catch (\Exception $e) {
            Log::error('CategoryForm: Banner upload error', [
                'error' => $e->getMessage(),
                'category_id' => $this->category?->id
            ]);

            session()->flash('error', 'Błąd podczas przesyłania bannera: ' . $e->getMessage());
        }

        $this->loadingStates['upload_banner'] = false;
    }

    // ==========================================
    // FORM SUBMISSION METHODS
    // ==========================================

    /**
     * Save category (create or update)
     */
    public function save(): void
    {
        $this->loadingStates['save'] = true;

        try {
            $this->validate();

            // Process uploads first
            if ($this->iconUpload) {
                $this->processIconUpload();
            }
            if ($this->bannerUpload) {
                $this->processBannerUpload();
            }

            DB::transaction(function () {
                if ($this->mode === 'create') {
                    $this->createCategory();
                } else {
                    $this->updateCategory();
                }
            });

            // Redirect back to category tree with success message
            session()->flash('message',
                $this->mode === 'create'
                    ? 'Kategoria została utworzona pomyślnie.'
                    : 'Kategoria została zaktualizowana pomyślnie.'
            );

            $this->redirect(route('admin.products.categories.index'));

        } catch (\Exception $e) {
            Log::error('CategoryForm: Error saving category', [
                'error' => $e->getMessage(),
                'mode' => $this->mode,
                'category_id' => $this->category?->id,
                'form_data' => $this->form
            ]);

            session()->flash('error', 'Błąd podczas zapisywania kategorii: ' . $e->getMessage());
        }

        $this->loadingStates['save'] = false;
    }

    /**
     * Create new category
     */
    private function createCategory(): void
    {
        $categoryData = array_merge($this->form, [
            'meta_title' => $this->seoForm['meta_title'],
            'meta_description' => $this->seoForm['meta_description'],
            'meta_keywords' => $this->seoForm['meta_keywords'],
            'canonical_url' => $this->seoForm['canonical_url'],
            'og_title' => $this->seoForm['og_title'],
            'og_description' => $this->seoForm['og_description'],
            'og_image' => $this->seoForm['og_image'],
            'icon_path' => $this->visualForm['icon'],
            'visual_settings' => $this->visualForm,
            'visibility_settings' => $this->visibilityForm,
            'default_values' => $this->defaultsForm,
        ]);

        $this->category = Category::create($categoryData);
    }

    /**
     * Update existing category
     */
    private function updateCategory(): void
    {
        $categoryData = array_merge($this->form, [
            'meta_title' => $this->seoForm['meta_title'],
            'meta_description' => $this->seoForm['meta_description'],
            'meta_keywords' => $this->seoForm['meta_keywords'],
            'canonical_url' => $this->seoForm['canonical_url'],
            'og_title' => $this->seoForm['og_title'],
            'og_description' => $this->seoForm['og_description'],
            'og_image' => $this->seoForm['og_image'],
            'icon_path' => $this->visualForm['icon'],
            'visual_settings' => $this->visualForm,
            'visibility_settings' => $this->visibilityForm,
            'default_values' => $this->defaultsForm,
        ]);

        $this->category->update($categoryData);
    }

    // ==========================================
    // UI HELPER METHODS
    // ==========================================

    /**
     * Switch active tab
     *
     * @param string $tab
     */
    public function setActiveTab(string $tab): void
    {
        if (array_key_exists($tab, $this->tabs)) {
            $this->activeTab = $tab;
        }
    }

    /**
     * Toggle parent category tree visibility
     */
    public function toggleParentTree(): void
    {
        $this->showParentTree = !$this->showParentTree;
    }

    /**
     * Get available parent categories for dropdown
     *
     * @return array
     */
    public function getParentOptionsProperty(): array
    {
        $query = Category::active()->treeOrder();

        // Exclude current category and its descendants in edit mode
        if ($this->mode === 'edit' && $this->category) {
            $excludeIds = [$this->category->id];
            $excludeIds = array_merge($excludeIds, $this->category->descendants->pluck('id')->toArray());
            $query->whereNotIn('id', $excludeIds);
        }

        return $query->get()->mapWithKeys(function ($category) {
            $prefix = str_repeat('— ', $category->level);
            return [$category->id => $prefix . $category->name];
        })->toArray();
    }

    /**
     * Get default color based on category level
     *
     * @param int $level
     * @return string
     */
    private function getDefaultColor(int $level): string
    {
        $colors = [
            '#3B82F6', // Blue
            '#10B981', // Green
            '#F59E0B', // Yellow
            '#EF4444', // Red
            '#8B5CF6', // Purple
        ];

        return $colors[$level % count($colors)];
    }

    // ==========================================
    // LIVEWIRE RENDER METHOD
    // ==========================================

    /**
     * Render the component
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.products.categories.category-form', [
            'parentOptions' => $this->parentOptions,
        ]);
    }
}