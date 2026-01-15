<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Visual Description Editor - Shop Styleset Model
 *
 * Zestaw stylow CSS dla sklepu.
 * Definiuje namespace CSS, zmienne i pelna tresc CSS.
 *
 * @property int $id
 * @property int $shop_id
 * @property string $name
 * @property string $css_namespace
 * @property string $css_content
 * @property array|null $variables_json
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read PrestaShopShop $shop
 */
class ShopStyleset extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'name',
        'css_namespace',
        'css_content',
        'variables_json',
        'is_active',
    ];

    protected $casts = [
        'variables_json' => 'array',
        'is_active' => 'boolean',
        'shop_id' => 'integer',
    ];

    /**
     * Default CSS variables schema
     */
    public const DEFAULT_VARIABLES = [
        'primary-color' => '#2563eb',
        'secondary-color' => '#64748b',
        'accent-color' => '#f59e0b',
        'text-color' => '#1f2937',
        'background-color' => '#ffffff',
        'font-family' => 'system-ui, -apple-system, sans-serif',
        'heading-font' => 'inherit',
        'spacing-unit' => '1rem',
        'border-radius' => '0.375rem',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    /**
     * Shop this styleset belongs to
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'shop_id');
    }

    // =====================
    // SCOPES
    // =====================

    /**
     * Scope: Only active stylesets
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Stylesets for specific shop
     */
    public function scopeForShop(Builder $query, int $shopId): Builder
    {
        return $query->where('shop_id', $shopId);
    }

    // =====================
    // ACCESSORS
    // =====================

    /**
     * Get variables with fallback to defaults
     */
    public function getVariablesAttribute(): array
    {
        return array_merge(
            self::DEFAULT_VARIABLES,
            $this->variables_json ?? []
        );
    }

    /**
     * Get CSS namespace with trailing dash if needed
     */
    public function getNamespacePrefixAttribute(): string
    {
        $namespace = $this->css_namespace ?? 'pd-';

        return str_ends_with($namespace, '-') ? $namespace : $namespace . '-';
    }

    // =====================
    // METHODS
    // =====================

    /**
     * Compile CSS with variables
     */
    public function compileCss(): string
    {
        $variables = $this->variables;
        $namespace = $this->namespace_prefix;

        // Build CSS custom properties
        $cssVars = ":root {\n";
        foreach ($variables as $name => $value) {
            $cssVars .= "  --{$namespace}{$name}: {$value};\n";
        }
        $cssVars .= "}\n\n";

        // Append main CSS content
        return $cssVars . $this->css_content;
    }

    /**
     * Get variable value by name
     */
    public function getVariable(string $name): ?string
    {
        $variables = $this->variables;
        return $variables[$name] ?? null;
    }

    /**
     * Set variable value
     */
    public function setVariable(string $name, string $value): void
    {
        $variables = $this->variables_json ?? [];
        $variables[$name] = $value;
        $this->variables_json = $variables;
    }

    /**
     * Generate CSS variable reference
     */
    public function varRef(string $name): string
    {
        return "var(--{$this->namespace_prefix}{$name})";
    }

    /**
     * Export styleset as array
     */
    public function export(): array
    {
        return [
            'name' => $this->name,
            'namespace' => $this->css_namespace,
            'variables' => $this->variables_json,
            'css' => $this->css_content,
            'exported_at' => now()->toIso8601String(),
            'version' => '1.0',
        ];
    }

    /**
     * Import styleset from array
     */
    public static function import(array $data, int $shopId): self
    {
        return self::create([
            'shop_id' => $shopId,
            'name' => $data['name'] . ' (Import)',
            'css_namespace' => $data['namespace'] ?? 'pd-',
            'css_content' => $data['css'] ?? '',
            'variables_json' => $data['variables'] ?? [],
            'is_active' => true,
        ]);
    }

    /**
     * Get active styleset for shop
     */
    public static function getActiveForShop(int $shopId): ?self
    {
        return self::forShop($shopId)->active()->first();
    }

    /**
     * Minify CSS content
     */
    public function minifyCss(): string
    {
        $css = $this->compileCss();

        // Basic minification
        $css = preg_replace('/\/\*.*?\*\//s', '', $css); // Remove comments
        $css = preg_replace('/\s+/', ' ', $css); // Collapse whitespace
        $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css); // Remove spaces around punctuation
        $css = trim($css);

        return $css;
    }
}
