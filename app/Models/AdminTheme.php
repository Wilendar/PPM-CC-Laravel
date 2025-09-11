<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminTheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'theme_name',
        'primary_color',
        'secondary_color',
        'accent_color',
        'layout_density',
        'sidebar_position',
        'header_style',
        'custom_css',
        'company_logo',
        'company_name',
        'company_colors',
        'widget_layout',
        'dashboard_settings',
        'is_active',
        'is_default',
        'settings_json'
    ];

    protected $casts = [
        'company_colors' => 'array',
        'widget_layout' => 'array',
        'dashboard_settings' => 'array',
        'settings_json' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Relationship z User model
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope dla active themes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope dla default theme
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get theme configuration jako array
     */
    public function getConfigurationAttribute()
    {
        return [
            'theme_name' => $this->theme_name,
            'colors' => [
                'primary' => $this->primary_color ?? '#3b82f6',
                'secondary' => $this->secondary_color ?? '#64748b',
                'accent' => $this->accent_color ?? '#10b981',
            ],
            'layout' => [
                'density' => $this->layout_density ?? 'normal',
                'sidebar_position' => $this->sidebar_position ?? 'left',
                'header_style' => $this->header_style ?? 'fixed',
            ],
            'branding' => [
                'company_logo' => $this->company_logo,
                'company_name' => $this->company_name ?? 'PPM Admin',
                'company_colors' => $this->company_colors ?? [],
            ],
            'widgets' => $this->widget_layout ?? [],
            'dashboard' => $this->dashboard_settings ?? [],
            'custom_css' => $this->custom_css,
        ];
    }

    /**
     * Get CSS variables dla theme
     */
    public function getCssVariables()
    {
        $config = $this->configuration;
        
        return [
            '--primary-color' => $config['colors']['primary'],
            '--secondary-color' => $config['colors']['secondary'],
            '--accent-color' => $config['colors']['accent'],
            '--layout-density' => $config['layout']['density'],
            '--sidebar-width' => $config['layout']['density'] === 'compact' ? '200px' : '250px',
            '--header-height' => $config['layout']['density'] === 'compact' ? '50px' : '60px',
            '--spacing-unit' => $config['layout']['density'] === 'compact' ? '0.5rem' : 
                               ($config['layout']['density'] === 'spacious' ? '1.5rem' : '1rem'),
        ];
    }

    /**
     * Apply theme jako CSS
     */
    public function toCss()
    {
        $variables = $this->getCssVariables();
        $customCss = $this->custom_css ?? '';
        
        $css = ":root {\n";
        foreach ($variables as $name => $value) {
            $css .= "  {$name}: {$value};\n";
        }
        $css .= "}\n\n";
        
        // Add density-specific styles
        if ($this->layout_density === 'compact') {
            $css .= $this->getCompactStyles();
        } elseif ($this->layout_density === 'spacious') {
            $css .= $this->getSpaciousStyles();
        }
        
        // Add custom CSS
        if ($customCss) {
            $css .= "\n/* Custom CSS */\n" . $customCss . "\n";
        }
        
        return $css;
    }

    /**
     * Get compact layout styles
     */
    private function getCompactStyles()
    {
        return "
/* Compact Layout Styles */
.admin-sidebar {
    width: var(--sidebar-width);
}

.admin-header {
    height: var(--header-height);
    padding: 0 var(--spacing-unit);
}

.admin-content {
    padding: var(--spacing-unit);
}

.widget {
    padding: var(--spacing-unit);
    margin-bottom: var(--spacing-unit);
}

.btn {
    padding: calc(var(--spacing-unit) / 2) var(--spacing-unit);
}

.table th, .table td {
    padding: calc(var(--spacing-unit) / 2);
}
";
    }

    /**
     * Get spacious layout styles
     */
    private function getSpaciousStyles()
    {
        return "
/* Spacious Layout Styles */
.admin-sidebar {
    width: 280px;
}

.admin-header {
    height: 70px;
    padding: 0 var(--spacing-unit);
}

.admin-content {
    padding: calc(var(--spacing-unit) * 2);
}

.widget {
    padding: calc(var(--spacing-unit) * 2);
    margin-bottom: calc(var(--spacing-unit) * 2);
}

.btn {
    padding: var(--spacing-unit) calc(var(--spacing-unit) * 2);
}

.table th, .table td {
    padding: var(--spacing-unit);
}
";
    }

    /**
     * Clone theme dla another user
     */
    public function cloneForUser($userId)
    {
        return static::create([
            'user_id' => $userId,
            'theme_name' => $this->theme_name . ' (Copy)',
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'accent_color' => $this->accent_color,
            'layout_density' => $this->layout_density,
            'sidebar_position' => $this->sidebar_position,
            'header_style' => $this->header_style,
            'custom_css' => $this->custom_css,
            'company_logo' => $this->company_logo,
            'company_name' => $this->company_name,
            'company_colors' => $this->company_colors,
            'widget_layout' => $this->widget_layout,
            'dashboard_settings' => $this->dashboard_settings,
            'settings_json' => $this->settings_json,
            'is_active' => false,
            'is_default' => false,
        ]);
    }

    /**
     * Set jako active theme dla user
     */
    public function makeActive()
    {
        // Deactivate other themes dla tego user
        static::where('user_id', $this->user_id)
              ->where('id', '!=', $this->id)
              ->update(['is_active' => false]);
        
        // Activate this theme
        $this->update(['is_active' => true]);
        
        return $this;
    }

    /**
     * Update widget layout
     */
    public function updateWidgetLayout(array $layout)
    {
        return $this->update([
            'widget_layout' => array_merge($this->widget_layout ?? [], $layout)
        ]);
    }

    /**
     * Update dashboard settings
     */
    public function updateDashboardSettings(array $settings)
    {
        return $this->update([
            'dashboard_settings' => array_merge($this->dashboard_settings ?? [], $settings)
        ]);
    }

    /**
     * Validate custom CSS dla security
     */
    public static function validateCustomCss($css)
    {
        // Remove dangerous CSS functions
        $dangerous = [
            'expression',
            'javascript:',
            'vbscript:',
            'onload',
            'onerror',
            'url(',
            '@import',
            '@charset',
        ];
        
        foreach ($dangerous as $danger) {
            if (stripos($css, $danger) !== false) {
                throw new \InvalidArgumentException("Dangerous CSS detected: {$danger}");
            }
        }
        
        return true;
    }

    /**
     * Boot method dla model events
     */
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($theme) {
            if ($theme->custom_css) {
                static::validateCustomCss($theme->custom_css);
            }
        });
    }
}