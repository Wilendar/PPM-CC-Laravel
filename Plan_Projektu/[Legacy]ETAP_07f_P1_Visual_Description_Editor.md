# ETAP_07f: Visual Description Editor (Elementor-style)

**Status**: 🛠️ W TRAKCIE (Fazy 1-8 ukonczone, pozostaly 9-10)
**Priorytet**: WYSOKI
**Szacowany czas**: 4-6 tygodni
**Zaleznosci**: ETAP_05 (Produkty), ETAP_07 (PrestaShop API)

---

## ✅ 1. FUNDAMENT - Database Schema i Models

### ✅ 1.1 Migracje Bazy Danych

#### ✅ 1.1.1 Tabela `description_blocks`
- id, name, type, category, icon, default_settings (JSON)
- is_active, sort_order, created_at, updated_at
    └── 📁 PLIK: database/migrations/2025_12_11_100001_create_description_blocks_table.php

#### ✅ 1.1.2 Tabela `description_templates`
- id, name, description, shop_id (nullable)
- blocks_json, thumbnail_path, is_default
- created_by, created_at, updated_at
    └── 📁 PLIK: database/migrations/2025_12_11_100002_create_description_templates_table.php

#### ✅ 1.1.3 Tabela `product_descriptions`
- id, product_id, shop_id
- blocks_json, rendered_html, last_rendered_at
- template_id (nullable), created_at, updated_at
    └── 📁 PLIK: database/migrations/2025_12_11_100003_create_product_descriptions_table.php

#### ✅ 1.1.4 Tabela `shop_stylesets`
- id, shop_id, name, css_namespace
- css_content, variables_json
- is_active, created_at, updated_at
    └── 📁 PLIK: database/migrations/2025_12_11_100004_create_shop_stylesets_table.php

### ✅ 1.2 Eloquent Models

#### ✅ 1.2.1 Model `DescriptionBlock`
- Scopes: active(), byCategory(), ordered()
- Accessors: parsed_settings, parsed_schema, category_label
- Methods: createInstance(), validateData()
    └── 📁 PLIK: app/Models/DescriptionBlock.php

#### ✅ 1.2.2 Model `DescriptionTemplate`
- Relationships: shop, creator, productDescriptions
- Scopes: forShop(), global(), defaults(), createdBy()
- Methods: duplicate(), export(), import(), getUsageCount()
    └── 📁 PLIK: app/Models/DescriptionTemplate.php

#### ✅ 1.2.3 Model `ProductDescription`
- Relationships: product, shop, template
- Scopes: forProduct(), forShop(), needsRerender(), usingTemplate()
- Methods: render(), parseBlocks(), setRenderedHtml(), applyTemplate()
- Events: saving -> markForRerender when blocks change
    └── 📁 PLIK: app/Models/ProductDescription.php

#### ✅ 1.2.4 Model `ShopStyleset`
- Relationships: shop
- Scopes: active(), forShop()
- Methods: compileCss(), getVariable(), setVariable(), varRef(), export(), minifyCss()
    └── 📁 PLIK: app/Models/ShopStyleset.php

### ✅ 1.3 Seeders

#### ✅ 1.3.1 Seeder `DescriptionBlockSeeder`
- 17 default blocks: Layout (5), Content (6), Media (4), Interactive (4)
    └── 📁 PLIK: database/seeders/DescriptionBlockSeeder.php

#### ✅ 1.3.2 Seeder `ShopStylesetSeeder`
- Per-shop CSS: B2B, KAYO, YCF, Pitgang, MRF styles
    └── 📁 PLIK: database/seeders/ShopStylesetSeeder.php

---

## ✅ 2. BLOCK REGISTRY - System Blokow

### ✅ 2.1 Block Base Classes

#### ✅ 2.1.1 Abstract `BaseBlock`
```php
abstract class BaseBlock
{
    public string $type;
    public string $name;
    public string $icon;
    public string $category;
    public array $defaultSettings;

    abstract public function render(array $content, array $settings): string;
    abstract public function getSchema(): array;
    abstract public function getPreview(): string;
}
```
    └── 📁 PLIK: app/Services/VisualEditor/Blocks/BaseBlock.php

#### ✅ 2.1.2 `BlockRegistry` Service
- registerBlock(), getBlock(), getAllBlocks()
- getBlocksByCategory(), validateBlockData()
    └── 📁 PLIK: app/Services/VisualEditor/BlockRegistry.php

#### ✅ 2.1.3 `BlockRenderer` Service
- renderBlock(), renderBlocks()
- generateHtml(), applyStyleset()
    └── 📁 PLIK: app/Services/VisualEditor/BlockRenderer.php

#### ✅ 2.1.4 `StylesetManager` Service
- getForShop(), compileVariables()
- applyToHtml(), getFullCss()
    └── 📁 PLIK: app/Services/VisualEditor/StylesetManager.php

### ✅ 2.2 Layout Blocks

#### ✅ 2.2.1 `HeroBannerBlock`
- Full-width image/video banner
- Settings: height, overlay, text_position
- Support: responsive images (picture element)
    └── 📁 PLIK: app/Services/VisualEditor/Blocks/Layout/HeroBannerBlock.php

#### ✅ 2.2.2 `TwoColumnBlock`
- 50/50 or custom ratio split
- Settings: ratio (50-50, 60-40, 70-30), reverse_mobile
- Child blocks support
    └── 📁 PLIK: app/Services/VisualEditor/Blocks/Layout/TwoColumnBlock.php

#### ✅ 2.2.3 `ThreeColumnBlock`
- 33/33/33 or custom split
- Settings: ratios, stack_on_mobile
- Child blocks support
    └── 📁 PLIK: app/Services/VisualEditor/Blocks/Layout/ThreeColumnBlock.php

#### ✅ 2.2.4 `GridSectionBlock`
- Custom CSS Grid layout
- Settings: columns, rows, gap
- Named areas support
    └── 📁 PLIK: app/Services/VisualEditor/Blocks/Layout/GridSectionBlock.php

#### ✅ 2.2.5 `FullWidthBlock`
- Edge-to-edge container
- Settings: background, padding
- Inner content wrapper
    └── 📁 PLIK: app/Services/VisualEditor/Blocks/Layout/FullWidthBlock.php

### ✅ 2.3 Content Blocks

#### ✅ 2.3.1 `HeadingBlock`
- H1-H6 headings
- Settings: level, alignment, style
- Subtitle support
    └── 📁 PLIK: app/Services/VisualEditor/Blocks/Content/HeadingBlock.php

#### ✅ 2.3.2 `TextBlock`
- Rich text content
- Settings: alignment, columns
- TinyMCE integration
    └── 📁 PLIK: app/Services/VisualEditor/Blocks/Content/TextBlock.php

#### ✅ 2.3.3 `FeatureCardBlock`
- Image + title + description
- Settings: layout (image-left, image-right, image-top)
- Icon support
    └── 📁 PLIK: app/Services/VisualEditor/Blocks/Content/FeatureCardBlock.php

#### ✅ 2.3.4 `SpecTableBlock`
- Key-value pairs table
- Settings: columns, header_style
- Grouping support
    └── 📁 PLIK: app/Services/VisualEditor/Blocks/Content/SpecTableBlock.php

#### ✅ 2.3.5 `MeritListBlock`
- Icon + heading + text list
- Settings: layout (horizontal, vertical)
- Built-in icons library
    └── 📁 PLIK: app/Services/VisualEditor/Blocks/Content/MeritListBlock.php

#### ✅ 2.3.6 `InfoCardBlock`
- Image + text combo
- Settings: image_position, ratio
- CTA button support
    └── 📁 PLIK: app/Services/VisualEditor/Blocks/Content/InfoCardBlock.php

### ✅ 2.4 Media Blocks

#### ✅ 2.4.1 `ImageBlock`
- Single image with caption
- Settings: size, alignment, link
- Responsive srcset support
    └── 📁 PLIK: app/Services/VisualEditor/Blocks/Media/ImageBlock.php

#### ✅ 2.4.2 `ImageGalleryBlock`
- Multiple images grid
- Settings: columns, lightbox
- Lazy loading
    └── 📁 PLIK: app/Services/VisualEditor/Blocks/Media/ImageGalleryBlock.php

#### ✅ 2.4.3 `VideoEmbedBlock`
- YouTube/Vimeo embed
- Settings: autoplay, controls, lazy_facade
- Privacy-friendly facade
    └── 📁 PLIK: app/Services/VisualEditor/Blocks/Media/VideoEmbedBlock.php

#### ✅ 2.4.4 `ParallaxImageBlock`
- Parallax scroll effect
- Settings: speed, height
- Fallback for mobile
    └── 📁 PLIK: app/Services/VisualEditor/Blocks/Media/ParallaxImageBlock.php

#### ✅ 2.4.5 `PictureElementBlock`
- Responsive picture element
- Settings: breakpoints, formats
- WebP support
    └── 📁 PLIK: app/Services/VisualEditor/Blocks/Media/PictureElementBlock.php

### ✅ 2.5 Interactive Blocks

#### ✅ 2.5.1 `SliderBlock`
- Splide.js carousel
- Settings: autoplay, arrows, dots
- Nested blocks support
    └── 📁 PLIK: app/Services/VisualEditor/Blocks/Interactive/SliderBlock.php

#### ✅ 2.5.2 `AccordionBlock`
- Collapsible sections
- Settings: allow_multiple, default_open
- Icon customization
    └── 📁 PLIK: app/Services/VisualEditor/Blocks/Interactive/AccordionBlock.php

#### ✅ 2.5.3 `TabsBlock`
- Tab navigation
- Settings: style, position
- Nested blocks support
    └── 📁 PLIK: app/Services/VisualEditor/Blocks/Interactive/TabsBlock.php

#### ✅ 2.5.4 `CTAButtonBlock`
- Call to action button
- Settings: style, size, icon
- Link/modal/scroll targets
    └── 📁 PLIK: app/Services/VisualEditor/Blocks/Interactive/CTAButtonBlock.php

#### ✅ 2.5.5 `RawHtmlBlock` (2025-12-15)
- Custom HTML/CSS/JS block
- Content: html (code editor)
- Settings: wrapper_class, wrapper_id, custom_css, custom_js, js_position, sanitize
- Security: sanitizeHtml() removes scripts/event handlers when sanitize=true
- Groups: content (html), wrapper (class/id), advanced (css/js/sanitize)
    └── 📁 PLIK: app/Services/VisualEditor/Blocks/Interactive/RawHtmlBlock.php

---

## ✅ 3. STYLESET SYSTEM - Per-Shop CSS

### ✅ 3.1 Base Styleset

#### ✅ 3.1.1 CSS Variables Schema
```css
--pd-primary-color
--pd-secondary-color
--pd-accent-color
--pd-text-color
--pd-background-color
--pd-font-family
--pd-heading-font
--pd-spacing-unit
--pd-border-radius
```
    └── 📁 PLIK: app/Services/VisualEditor/Styleset/ShopStylesets/AbstractShopStyleset.php

#### ✅ 3.1.2 Base Block Styles
- Generic block styles
- Responsive breakpoints
- Typography scales
    └── 📁 PLIK: resources/css/visual-editor/base-blocks.css

### ✅ 3.2 Shop-Specific Stylesets

#### ✅ 3.2.1 B2B Styleset
- Namespace: `pd-*`
- Colors: corporate blue/gray
- Typography: clean, professional
    └── 📁 PLIK: app/Services/VisualEditor/Styleset/ShopStylesets/B2BStyleset.php

#### ✅ 3.2.2 KAYO Styleset
- Namespace: `pd-*` (extended)
- Colors: brand orange/black
- Effects: parallax, sliders
    └── 📁 PLIK: app/Services/VisualEditor/Styleset/ShopStylesets/KayoStyleset.php

#### ✅ 3.2.3 YCF Styleset
- Namespace: `pd-*` + custom
- Colors: racing red/white
- Typography: serif accents
    └── 📁 PLIK: app/Services/VisualEditor/Styleset/ShopStylesets/YcfStyleset.php

#### ✅ 3.2.4 Pitgang Styleset
- Namespace: `blok-*`
- Colors: black/white/accent
- Visual: skull stamps, bold
    └── 📁 PLIK: app/Services/VisualEditor/Styleset/ShopStylesets/PitgangStyleset.php

#### ✅ 3.2.5 MRF Styleset
- Namespace: `pd-*` + grid utilities
- Colors: brand colors (to be defined)
- Features: Bootstrap-like spacing, CSS Grid layout
- Responsive: `md-` prefix modifiers
    └── 📁 PLIK: app/Services/VisualEditor/Styleset/ShopStylesets/MrfStyleset.php

### ✅ 3.3 Styleset Manager

#### ✅ 3.3.1 `StylesetCompiler` Service
- compileStyleset()
- mergeVariables()
- generateCss()
    └── 📁 PLIK: app/Services/VisualEditor/Styleset/StylesetCompiler.php
    └── 📁 PLIK: app/Services/VisualEditor/Styleset/StylesetValidator.php
    └── 📁 PLIK: app/Services/VisualEditor/Styleset/StylesetFactory.php

#### ✅ 3.3.2 Styleset Editor (Admin)
- Variable editor
- Live preview
- Export/import
    └── 📁 PLIK: app/Http/Livewire/Admin/VisualEditor/StylesetEditor.php

---

## ✅ 4. LIVEWIRE VISUAL EDITOR

### ✅ 4.1 Main Editor Component

#### ✅ 4.1.1 `VisualDescriptionEditor.php`
```php
class VisualDescriptionEditor extends Component
{
    public ?int $productId;
    public ?int $shopId;
    public array $blocks = [];
    public ?int $selectedBlockIndex = null;
    public bool $showBlockPalette = false;
    public bool $showPreview = true;

    // Methods
    public function addBlock(string $type, ?int $position = null)
    public function removeBlock(int $index)
    public function moveBlock(int $from, int $to)
    public function duplicateBlock(int $index)
    public function updateBlockContent(int $index, array $content)
    public function updateBlockSettings(int $index, array $settings)
    public function saveDescription()
    public function loadTemplate(int $templateId)
    public function saveAsTemplate(string $name)
}
```
    └── 📁 PLIK: app/Http/Livewire/Products/VisualDescription/VisualDescriptionEditor.php
    └── 📁 PLIK: app/Http/Livewire/Products/VisualDescription/Traits/EditorBlockManagement.php
    └── 📁 PLIK: app/Http/Livewire/Products/VisualDescription/Traits/EditorUndoRedo.php
    └── 📁 PLIK: app/Http/Livewire/Products/VisualDescription/Traits/EditorTemplates.php
    └── 📁 PLIK: app/Http/Livewire/Products/VisualDescription/Traits/EditorPreview.php

#### ✅ 4.1.2 Blade View `visual-description-editor.blade.php`
- Three-panel layout (palette, canvas, properties)
- Responsive design
- Dark mode support
    └── 📁 PLIK: resources/views/livewire/products/visual-description/visual-description-editor.blade.php

### ✅ 4.2 Sub-Components

#### ✅ 4.2.1 `BlockPalette.php`
- Kategoryzowana lista blokow
- Search/filter
- Drag source
    └── 📁 PLIK: resources/views/livewire/products/visual-description/partials/block-palette.blade.php

#### ✅ 4.2.2 `BlockCanvas.php`
- Sortable blocks list
- Drop zone
- Selection highlight
    └── 📁 PLIK: resources/views/livewire/products/visual-description/partials/block-canvas.blade.php

#### ✅ 4.2.3 `PropertyPanel.php`
- Dynamic form based on block schema
- Content editor (TinyMCE)
- Settings controls
    └── 📁 PLIK: resources/views/livewire/products/visual-description/partials/property-panel.blade.php
    └── 📁 PLIK: resources/views/livewire/products/visual-description/partials/property-field.blade.php

#### ✅ 4.2.4 `PreviewPane.php`
- Real-time HTML preview
- Shop styleset applied
- Responsive preview modes
    └── 📁 PLIK: resources/views/livewire/products/visual-description/partials/preview-pane.blade.php

### ✅ 4.3 Alpine.js Integration

#### ✅ 4.3.1 Drag & Drop (SortableJS)
- Block reordering
- Cross-list dragging
- Nested blocks
    └── 📁 PLIK: resources/views/livewire/products/visual-description/visual-description-editor.blade.php (wire:sortable)

#### ✅ 4.3.2 Keyboard Shortcuts
- Ctrl+S: Save
- Ctrl+Z: Undo
- Delete: Remove block
- Ctrl+D: Duplicate
    └── 📁 PLIK: resources/views/livewire/products/visual-description/visual-description-editor.blade.php (@keydown handlers)

#### ✅ 4.3.3 Undo/Redo System
- History stack
- State snapshots
- Keyboard bindings
    └── 📁 PLIK: app/Http/Livewire/Products/VisualDescription/Traits/EditorUndoRedo.php

### ✅ 4.4 Additional Components

#### ✅ 4.4.1 Template Modal
- Load/save templates
- Permission checking
- Search/filter
    └── 📁 PLIK: resources/views/livewire/products/visual-description/partials/template-modal.blade.php

#### ✅ 4.4.2 Routing
- Visual Editor routes
- Styleset Editor routes
    └── 📁 PLIK: routes/web.php (visual-editor prefix)

#### ✅ 4.4.3 Service Provider
- BlockRegistry registration
- StylesetManager singleton
- BlockRenderer binding
    └── 📁 PLIK: app/Providers/VisualEditorServiceProvider.php
    └── 📁 PLIK: bootstrap/app.php (provider registration)

---

## ✅ 5. TEMPLATE SYSTEM

### ✅ 5.1 Template Management

#### ✅ 5.1.1 `TemplateManager.php` Livewire
- List templates (global + per-shop)
- Create/edit/delete
- Duplicate templates
- Export/Import JSON
- Computed properties (#[Computed] attribute)
    └── 📁 PLIK: app/Http/Livewire/Admin/VisualEditor/TemplateManager.php

#### ✅ 5.1.2 Template Preview
- Thumbnail generation (placeholder icon)
- Live preview modal
- Block summary
- Usage statistics
    └── 📁 PLIK: resources/views/livewire/admin/visual-editor/template-manager.blade.php

### ✅ 5.2 Template Features

#### ✅ 5.2.1 Global vs Shop Templates
- Global: available for all shops
- Shop: specific to one shop
- scopeForShop() includes global templates
    └── 📁 PLIK: app/Models/DescriptionTemplate.php (updated with category field)

#### ✅ 5.2.2 Template Variables
- Placeholder system: {{product_name}}, {{product_sku}}, {{product_price}}, etc.
- Dynamic content insertion via TemplateVariableService
- 16 supported variables grouped by category
    └── 📁 PLIK: app/Services/VisualEditor/TemplateVariableService.php

#### ✅ 5.2.3 Template Categories
- Product types: motocykle, quady, czesci, akcesoria, odziez
- Sections: intro, features, specs, gallery
- TemplateCategoryService with icons and descriptions
    └── 📁 PLIK: app/Services/VisualEditor/TemplateCategoryService.php

#### ✅ 5.2.4 Database Migration
- Added category column to description_templates
    └── 📁 PLIK: database/migrations/2025_12_11_134314_add_category_to_description_templates.php

#### ✅ 5.2.5 Routing
- /admin/visual-editor/templates
- /admin/visual-editor/styleset
    └── 📁 PLIK: routes/web.php (visual-editor prefix)

---

## ✅ 6. INTEGRACJA Z PRODUKTAMI

### ✅ 6.1 ProductForm Integration

#### ✅ 6.1.1 Nowa zakladka "Opis Wizualny"
- Toggle: standard/visual editor
- Per-shop descriptions
- Template selector
- Empty state with "Stworz nowy opis" CTA
    └── 📁 PLIK: app/Http/Livewire/Products/Management/Traits/ProductFormVisualDescription.php
    └── 📁 PLIK: resources/views/livewire/products/management/tabs/visual-description-tab.blade.php
    └── 📁 PLIK: resources/views/livewire/products/management/partials/tab-navigation.blade.php

#### ✅ 6.1.2 Tab Navigation Update
- Added "Opis Wizualny" tab with palette icon
- Conditional rendering in product-form.blade.php
    └── 📁 PLIK: resources/views/livewire/products/management/product-form.blade.php (lines 124-125)

#### ✅ 6.1.3 Visual Editor Route
- Route: /admin/visual-editor/product/{product}/shop/{shop}
- View wrapper for Livewire component
    └── 📁 PLIK: routes/web.php (visual-editor.product)
    └── 📁 PLIK: resources/views/admin/visual-editor/product-editor.blade.php

#### ✅ 6.1.4 Description Sync
- ✅ Sync Controls UI w visual-description-tab.blade.php
- ✅ Livewire methods w ProductFormVisualDescription trait (saveSyncSettings, updateSyncToPrestaShop, toggleInlineCss)
- ✅ Version History system:
  - Migration: database/migrations/2025_12_15_100001_create_product_description_versions_table.php
  - Model: app/Models/ProductDescriptionVersion.php
  - ProductDescription relationship: versions()
- ✅ History Viewer UI modal:
  - Modal: resources/views/livewire/products/management/partials/version-history-modal.blade.php
  - Methods: openVersionHistory(), closeVersionHistory(), previewVersion(), restoreVersion()
  - Computed: versionPreviewHtml, versionHistoryInfo
- ✅ ProductSyncStrategy integration verified (markVisualDescriptionAsSynced)
    └── 📁 PLIK: app/Http/Livewire/Products/Management/Traits/ProductFormVisualDescription.php
    └── 📁 PLIK: resources/views/livewire/products/management/tabs/visual-description-tab.blade.php
    └── 📁 PLIK: resources/views/livewire/products/management/partials/version-history-modal.blade.php
    └── 📁 PLIK: app/Models/ProductDescriptionVersion.php
    └── 📁 PLIK: database/migrations/2025_12_15_100001_create_product_description_versions_table.php

### ✅ 6.2 Bulk Operations

#### ✅ 6.2.1 BulkApplyTemplateJob
- Chunked processing (50 products per batch)
- Progress tracking via JobProgressService
- Variable replacement for dynamic content
- HTML rendering after block application
    └── 📁 PLIK: app/Jobs/VisualEditor/BulkApplyTemplateJob.php

#### ✅ 6.2.2 BulkExportDescriptionsJob
- Export to JSON with full description data
- Includes blocks array, rendered HTML, template info
- Downloadable file stored on local disk
    └── 📁 PLIK: app/Jobs/VisualEditor/BulkExportDescriptionsJob.php

#### ✅ 6.2.3 BulkImportDescriptionsJob
- Import from JSON with SKU-based product matching
- Block structure validation
- HTML re-rendering after import
- Options: overwrite, skip_existing
    └── 📁 PLIK: app/Jobs/VisualEditor/BulkImportDescriptionsJob.php

#### ✅ 6.2.4 BulkOperationsService
- applyTemplateToProducts(): Dispatch bulk template apply job
- exportDescriptions(): Export descriptions to JSON
- importDescriptions(): Import from JSON file
- getExportableProducts(): Query products with visual descriptions
- getStatistics(): Shop coverage statistics
    └── 📁 PLIK: app/Services/VisualEditor/BulkOperationsService.php

#### ✅ 6.2.5 Export Download Controller
- Secure file download for exports
- File deletion endpoint
    └── 📁 PLIK: app/Http/Controllers/Admin/ExportDownloadController.php
    └── 📁 PLIK: routes/web.php (admin.exports.download, admin.exports.delete)

---

## ❌ 7. MEDIA INTEGRATION

### ❌ 7.1 Media Library Integration

#### ❌ 7.1.1 Image Picker Modal
- Browse product media
- Upload new images
- External URL support

#### ❌ 7.1.2 Responsive Image Generation
- Auto-generate srcset
- WebP conversion
- Lazy loading

### ❌ 7.2 External Media

#### ❌ 7.2.1 CDN Support (mm.mpptrade.pl)
- URL builder
- Path templates
- Cache management

#### ❌ 7.2.2 Video Services
- YouTube facade
- Vimeo embed
- Privacy settings

---

## ✅ 8. RENDERING I EXPORT

### ✅ 8.1 HTML Rendering (2025-12-11)

#### ✅ 8.1.1 `DescriptionRenderer` Service
- render(blocks, shopId) - full HTML with wrapper and CSS
- renderClean(blocks, shopId) - clean HTML without styles
- renderInline(blocks, shopId) - inline styles for email
- minify(html) - remove whitespace and comments
- renderAndCache(description) - render and save to model
    └── 📁 PLIK: app/Services/VisualEditor/DescriptionRenderer.php

#### ✅ 8.1.2 Output Formats
- Full HTML (with <style> tag and wrapper div)
- Clean HTML (wrapper only, CSS deployed separately)
- Inline styles (CSS variables resolved to values)
    └── 📁 PLIK: app/Services/VisualEditor/DescriptionRenderer.php

### ✅ 8.2 PrestaShop Sync (2025-12-11)

#### ✅ 8.2.1 ProductDescription Model Updates
- needsRerender() method - check if HTML needs regeneration
- needsSync() method - check if sync to PrestaShop needed
- calculateChecksum() - MD5 hash for change detection
- renderAndCache() - render blocks and cache HTML
- getHtmlForPrestaShop() - get HTML for description/description_short
- markAsSynced() - update sync timestamps
- sanitizeForPrestaShop() - remove scripts, event handlers
- truncateForShortDescription() - create summary for description_short
    └── 📁 PLIK: app/Models/ProductDescription.php

#### ✅ 8.2.2 Database Migration for Sync Settings
- sync_to_prestashop (bool) - enable/disable auto-sync
- target_field (enum) - description, description_short, or both
- include_inline_css (bool) - include styles in HTML
- last_synced_at (timestamp) - last successful sync
- sync_checksum (string) - MD5 for change detection
    └── 📁 PLIK: database/migrations/2025_12_11_120001_add_sync_settings_to_product_descriptions.php

#### ✅ 8.2.3 ProductTransformer Integration
- getVisualDescription() method - retrieves visual description HTML
- Priority: Visual Editor > Shop-specific text > Product default
- Automatic rendering if HTML needs refresh
    └── 📁 PLIK: app/Services/PrestaShop/ProductTransformer.php

#### ✅ 8.2.4 ProductSyncStrategy Integration
- markVisualDescriptionAsSynced() method - updates sync status after product sync
- Non-blocking: errors logged but don't fail product sync
- Integrates with existing product sync flow
    └── 📁 PLIK: app/Services/PrestaShop/Sync/ProductSyncStrategy.php

#### ✅ 8.2.5 CSS Deployment Service (2025-12-11)
- generateCssFile() - create versioned CSS file from styleset
- deployToPrestaShop() - upload CSS to shop (FTP/SFTP/API)
- getInlineCss() - get minified CSS for embedding
- getCssUrlForShop() - get URL for CSS link tag
- cleanOldVersions() - remove outdated CSS files
- getDeploymentStatus() - check deployment configuration
    └── 📁 PLIK: app/Services/VisualEditor/CssDeploymentService.php

#### ✅ 8.2.6 Sync Jobs (2025-12-11)
- SyncDescriptionToPrestaShopJob - sync single product description
- BulkSyncDescriptionsJob - bulk sync multiple descriptions
- Progress tracking via JobProgressService
- Chunked processing for memory efficiency
    └── 📁 PLIK: app/Jobs/VisualEditor/SyncDescriptionToPrestaShopJob.php
    └── 📁 PLIK: app/Jobs/VisualEditor/BulkSyncDescriptionsJob.php

---

## ❌ 9. ADMIN PANEL

### ❌ 9.1 Block Management

#### ❌ 9.1.1 Block List Page
- Enable/disable blocks
- Sort order
- Usage statistics

#### ❌ 9.1.2 Custom Block Creator
- Visual schema builder
- Template editor
- Preview testing

### ❌ 9.2 Styleset Management

#### ❌ 9.2.1 Styleset List
- Per-shop stylesets
- Active/inactive toggle
- CSS preview

#### ❌ 9.2.2 Styleset Editor
- Variable editor
- Custom CSS
- Live preview

---

## ❌ 10. FRONTEND ASSETS

### ❌ 10.1 Editor Assets

#### ❌ 10.1.1 CSS
- resources/css/visual-editor/editor.css
- resources/css/visual-editor/blocks.css
- resources/css/visual-editor/preview.css

#### ❌ 10.1.2 JavaScript
- resources/js/visual-editor/sortable-integration.js
- resources/js/visual-editor/preview-renderer.js
- resources/js/visual-editor/keyboard-shortcuts.js

### ❌ 10.2 Block Preview CSS

#### ❌ 10.2.1 Per-Shop CSS Files
- resources/css/stylesets/b2b.css
- resources/css/stylesets/kayo.css
- resources/css/stylesets/ycf.css
- resources/css/stylesets/pitgang.css
- resources/css/stylesets/mrf.css

---

## METRYKI SUKCESU

| Metryka | Cel |
|---------|-----|
| Czas tworzenia opisu | < 30 min (vs 2h+ reczne HTML) |
| Konsystencja wizualna | 100% zgodnosc ze stylesetem |
| Reuse templates | > 70% opisow z templates |
| User satisfaction | > 4.0/5.0 |
| Bledy renderowania | < 0.1% |

---

## ZALEZNOSCI ZEWNETRZNE

| Biblioteka | Wersja | Cel |
|------------|--------|-----|
| SortableJS | 1.15+ | Drag & drop |
| TinyMCE | 6.x | Rich text editor |
| Splide.js | 4.x | Sliders (frontend) |
| Alpine.js | 3.x | Interaktywnosc |

---

## TIMELINE

| Faza | Czas | Opis |
|------|------|------|
| Faza 1 | 1 tydzien | Database + Models + Base Classes |
| Faza 2 | 1.5 tygodnia | Block Registry (15+ blokow) |
| Faza 3 | 1 tydzien | Styleset System |
| Faza 4 | 1.5 tygodnia | Livewire Editor UI |
| Faza 5 | 0.5 tygodnia | Template System |
| Faza 6 | 0.5 tygodnia | Integration + Testing |

**TOTAL**: ~6 tygodni

---

## RYZYKA

| Ryzyko | Prawdopodobienstwo | Wplyw | Mitygacja |
|--------|-------------------|-------|-----------|
| Performance z duzymi opisami | Srednie | Wysoki | Lazy loading, pagination |
| Kompatybilnosc CSS z theme | Wysokie | Sredni | Izolacja namespace, testing |
| User learning curve | Srednie | Sredni | Onboarding, templates |
| Mobile editing | Niskie | Niski | Responsive design |

---

**Utworzono**: 2025-12-11
**Ostatnia aktualizacja**: 2025-12-11
**Autor**: architect agent

---

## CHANGELOG

| Data | Zmiana |
|------|--------|
| 2025-12-11 | Dodano sklep MRF (sklep.pitbikemrf.pl) - 50 produktow, 71 klas CSS, namespace pd-* + grid utilities |
| 2025-12-11 | Faza 1 + Faza 2 ukonczone - database schema, models, block registry (20 blokow) |
| 2025-12-11 | Faza 3 ukonczona - StylesetCompiler, StylesetValidator, 5 shop stylesets (B2B, KAYO, YCF, Pitgang, MRF), StylesetEditor |
| 2025-12-11 | Faza 4 ukonczona - VisualDescriptionEditor component z 4 traits, 7 blade partials, routing, provider registration |
| 2025-12-11 | Faza 5 ukonczona - TemplateManager component, TemplateVariableService (16 variables), TemplateCategoryService (10 categories), migration for category column |
| 2025-12-11 | Faza 6.2 ukonczona - Bulk Operations (BulkApplyTemplateJob, BulkExportDescriptionsJob, BulkImportDescriptionsJob, BulkOperationsService, ExportDownloadController) |
| 2025-12-11 | Faza 8.2 ukonczona - PrestaShop Description Sync (ProductDescription sync methods, migration for sync_settings, ProductTransformer integration, ProductSyncStrategy integration) |
| 2025-12-15 | Faza 6.1.4 ukonczona - Description Sync (Sync Controls UI, Version History system z migration + model, History Viewer modal, ProductSyncStrategy integration verified) |
| 2025-12-15 | RawHtmlBlock (2.5.5) - Custom HTML/CSS/JS block z edytorem kodu, sanityzacja, grupy: content/wrapper/advanced |
| 2025-12-15 | property-panel.blade.php fix - naprawione iterowanie po schema (content/settings zamiast properties) |
| 2025-12-15 | property-field.blade.php - dodano case 'code' z edytorem kodu, kolorowaniem skladni, przyciskiem kopiowania |
| 2025-12-15 | VisualEditorServiceProvider - dodano RawHtmlBlock do manualnej rejestracji blokow |
| 2025-12-15 | HtmlToBlocksParser - nowy serwis parsujacy HTML na bloki (mapowanie pd-*/blok-* klas, fallback do RawHtmlBlock) |
| 2025-12-15 | VisualDescriptionEditor - dodano metody importu: openImportModal(), importFromHtml(), importFromPrestaShop(), executeImport() |
| 2025-12-15 | visual-description-editor.blade.php - dodano Import dropdown w headerze + Import Modal (wybor zrodla HTML/PrestaShop, tryb append/replace) |
| 2025-12-16 | FAZA 4: Analiza wzorcow sekcji PrestaShop - raport architect_PRESTASHOP_BLOCK_PATTERNS_ANALYSIS.md |
| 2025-12-16 | FAZA 5: Nowe bloki PrestaShop - PdMeritsBlock (pd-merits), PdSliderBlock (pd-slider), PdParallaxBlock (pd-parallax) |
| 2025-12-16 | BlockRegistry autodiscovery - folder Blocks/PrestaShop/ z 4 blokami (PrestashopSectionBlock + 3 nowe) |
| 2025-12-16 | FAZA 5: Kolejne bloki PrestaShop - PdSpecificationBlock (pd-specification), PdAssetListBlock (pd-asset-list) |
| 2025-12-16 | 6 blokow PrestaShop zdeployowanych: Parametry, Lista Zalet, Parallax, Slider, Specyfikacja, Sekcja PrestaShop |
| 2025-12-16 | FAZA 6: UI Enhancements - dodano description do BaseBlock, wszystkie bloki PS maja tooltips z opisami w palecie |
| 2025-12-16 | FAZA 7: HtmlToBlocksParser - mapowanie sekcji PS na bloki (pd-merits, pd-slider, pd-parallax, pd-specification, pd-asset-list) |
| 2025-12-16 | FAZA 7: 5 metod ekstrakcji bloków - extractMeritsBlock(), extractSliderBlock(), extractSpecificationBlock(), extractParallaxBlock(), extractAssetListBlock() |
| 2025-12-16 | FAZA 7: Fallback pattern - nieznane typy sekcji pozostaja jako prestashop-section passthrough |
