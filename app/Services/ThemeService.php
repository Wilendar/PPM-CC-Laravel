<?php

namespace App\Services;

use App\Models\AdminTheme;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ThemeService
{
    /**
     * Cache duration dla themes (1 hour)
     */
    const CACHE_DURATION = 3600;
    
    /**
     * Maximum file size dla logo uploads (2MB)
     */
    const MAX_LOGO_SIZE = 2048;
    
    /**
     * Allowed logo file types
     */
    const ALLOWED_LOGO_TYPES = ['jpg', 'jpeg', 'png', 'svg', 'webp'];

    /**
     * Get active theme dla user
     */
    public function getActiveTheme(User $user): AdminTheme
    {
        $cacheKey = "admin_theme_user_{$user->id}";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($user) {
            $theme = AdminTheme::where('user_id', $user->id)
                              ->active()
                              ->first();
            
            if (!$theme) {
                // Create default theme dla user
                $theme = $this->createDefaultTheme($user);
            }
            
            return $theme;
        });
    }

    /**
     * Create default theme dla user
     */
    public function createDefaultTheme(User $user): AdminTheme
    {
        // Check if global default theme exists
        $defaultTheme = AdminTheme::default()->first();
        
        if ($defaultTheme) {
            // Clone global default dla user
            $theme = $defaultTheme->cloneForUser($user->id);
            $theme->makeActive();
        } else {
            // Create basic default theme
            $theme = AdminTheme::create([
                'user_id' => $user->id,
                'theme_name' => 'Default Theme',
                'primary_color' => '#3b82f6',
                'secondary_color' => '#64748b',
                'accent_color' => '#10b981',
                'layout_density' => 'normal',
                'sidebar_position' => 'left',
                'header_style' => 'fixed',
                'company_name' => 'PPM Admin',
                'is_active' => true,
                'widget_layout' => $this->getDefaultWidgetLayout(),
                'dashboard_settings' => $this->getDefaultDashboardSettings(),
            ]);
        }
        
        $this->clearUserThemeCache($user);
        
        return $theme;
    }

    /**
     * Update theme dla user
     */
    public function updateTheme(User $user, array $data): AdminTheme
    {
        $theme = $this->getActiveTheme($user);
        
        // Validate custom CSS if provided
        if (isset($data['custom_css']) && $data['custom_css']) {
            AdminTheme::validateCustomCss($data['custom_css']);
        }
        
        // Handle logo upload if provided
        if (isset($data['company_logo']) && $data['company_logo'] instanceof UploadedFile) {
            $data['company_logo'] = $this->handleLogoUpload($data['company_logo'], $user);
        }
        
        // Validate colors
        $colorFields = ['primary_color', 'secondary_color', 'accent_color'];
        foreach ($colorFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->validateColor($data[$field]);
            }
        }
        
        $theme->update($data);
        
        $this->clearUserThemeCache($user);
        
        return $theme->refresh();
    }

    /**
     * Handle logo file upload
     */
    public function handleLogoUpload(UploadedFile $file, User $user): string
    {
        // Validate file
        $this->validateLogoFile($file);
        
        // Generate unique filename
        $extension = $file->getClientOriginalExtension();
        $filename = 'admin_logo_' . $user->id . '_' . time() . '.' . $extension;
        
        // Store file
        $path = $file->storeAs('admin/themes/logos', $filename, 'public');
        
        // Remove old logo if exists
        $currentTheme = $this->getActiveTheme($user);
        if ($currentTheme->company_logo && Storage::disk('public')->exists($currentTheme->company_logo)) {
            Storage::disk('public')->delete($currentTheme->company_logo);
        }
        
        return $path;
    }

    /**
     * Validate logo file
     */
    private function validateLogoFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > (self::MAX_LOGO_SIZE * 1024)) {
            throw new \InvalidArgumentException('Logo file size cannot exceed ' . self::MAX_LOGO_SIZE . 'KB');
        }
        
        // Check file type
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_LOGO_TYPES)) {
            throw new \InvalidArgumentException('Logo file type must be: ' . implode(', ', self::ALLOWED_LOGO_TYPES));
        }
        
        // Check if file is actually an image
        $imageInfo = getimagesize($file->getPathname());
        if (!$imageInfo) {
            throw new \InvalidArgumentException('Uploaded file is not a valid image');
        }
    }

    /**
     * Validate hex color
     */
    private function validateColor(string $color): string
    {
        $color = trim($color);
        
        // Add # if missing
        if (!str_starts_with($color, '#')) {
            $color = '#' . $color;
        }
        
        // Validate hex format
        if (!preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
            throw new \InvalidArgumentException('Invalid hex color format: ' . $color);
        }
        
        return $color;
    }

    /**
     * Create new theme dla user
     */
    public function createTheme(User $user, array $data): AdminTheme
    {
        // Validate required fields
        if (!isset($data['theme_name']) || empty($data['theme_name'])) {
            throw new \InvalidArgumentException('Theme name is required');
        }
        
        // Set default values
        $data = array_merge([
            'user_id' => $user->id,
            'primary_color' => '#3b82f6',
            'secondary_color' => '#64748b',
            'accent_color' => '#10b981',
            'layout_density' => 'normal',
            'sidebar_position' => 'left',
            'header_style' => 'fixed',
            'company_name' => 'PPM Admin',
            'is_active' => false,
            'widget_layout' => $this->getDefaultWidgetLayout(),
            'dashboard_settings' => $this->getDefaultDashboardSettings(),
        ], $data);
        
        // Handle logo upload if provided
        if (isset($data['company_logo']) && $data['company_logo'] instanceof UploadedFile) {
            $data['company_logo'] = $this->handleLogoUpload($data['company_logo'], $user);
        }
        
        // Validate custom CSS if provided
        if (isset($data['custom_css']) && $data['custom_css']) {
            AdminTheme::validateCustomCss($data['custom_css']);
        }
        
        $theme = AdminTheme::create($data);
        
        return $theme;
    }

    /**
     * Switch active theme dla user
     */
    public function switchTheme(User $user, AdminTheme $theme): AdminTheme
    {
        // Verify theme belongs to user lub is shareable
        if ($theme->user_id !== $user->id && !$this->isThemeShareable($theme)) {
            throw new \InvalidArgumentException('Theme not accessible by user');
        }
        
        // If theme belongs to different user, clone it
        if ($theme->user_id !== $user->id) {
            $theme = $theme->cloneForUser($user->id);
        }
        
        $theme->makeActive();
        
        $this->clearUserThemeCache($user);
        
        return $theme;
    }

    /**
     * Check if theme is shareable
     */
    private function isThemeShareable(AdminTheme $theme): bool
    {
        return $theme->is_default || 
               ($theme->settings_json['shareable'] ?? false);
    }

    /**
     * Get all available themes dla user
     */
    public function getAvailableThemes(User $user): \Illuminate\Database\Eloquent\Collection
    {
        // Get user's own themes
        $userThemes = AdminTheme::where('user_id', $user->id)->get();
        
        // Get shareable themes from other users
        $shareableThemes = AdminTheme::where('user_id', '!=', $user->id)
                                   ->where(function ($query) {
                                       $query->where('is_default', true)
                                             ->orWhereJsonContains('settings_json->shareable', true);
                                   })
                                   ->get();
        
        return $userThemes->concat($shareableThemes);
    }

    /**
     * Delete theme
     */
    public function deleteTheme(User $user, AdminTheme $theme): bool
    {
        // Verify ownership
        if ($theme->user_id !== $user->id) {
            throw new \InvalidArgumentException('Cannot delete theme that does not belong to user');
        }
        
        // Cannot delete active theme
        if ($theme->is_active) {
            throw new \InvalidArgumentException('Cannot delete active theme. Switch to another theme first.');
        }
        
        // Delete logo file if exists
        if ($theme->company_logo && Storage::disk('public')->exists($theme->company_logo)) {
            Storage::disk('public')->delete($theme->company_logo);
        }
        
        $deleted = $theme->delete();
        
        $this->clearUserThemeCache($user);
        
        return $deleted;
    }

    /**
     * Update widget layout dla active theme
     */
    public function updateWidgetLayout(User $user, array $layout): AdminTheme
    {
        $theme = $this->getActiveTheme($user);
        $theme->updateWidgetLayout($layout);
        
        $this->clearUserThemeCache($user);
        
        return $theme;
    }

    /**
     * Update dashboard settings dla active theme
     */
    public function updateDashboardSettings(User $user, array $settings): AdminTheme
    {
        $theme = $this->getActiveTheme($user);
        $theme->updateDashboardSettings($settings);
        
        $this->clearUserThemeCache($user);
        
        return $theme;
    }

    /**
     * Generate CSS dla user's active theme
     */
    public function generateThemeCSS(User $user): string
    {
        $cacheKey = "admin_theme_css_user_{$user->id}";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($user) {
            $theme = $this->getActiveTheme($user);
            return $theme->toCss();
        });
    }

    /**
     * Get default widget layout
     */
    private function getDefaultWidgetLayout(): array
    {
        return [
            'grid' => [
                'columns' => 12,
                'gap' => 16,
            ],
            'widgets' => [
                ['id' => 'stats-overview', 'x' => 0, 'y' => 0, 'w' => 12, 'h' => 2],
                ['id' => 'recent-activity', 'x' => 0, 'y' => 2, 'w' => 6, 'h' => 4],
                ['id' => 'system-health', 'x' => 6, 'y' => 2, 'w' => 6, 'h' => 4],
                ['id' => 'integration-status', 'x' => 0, 'y' => 6, 'w' => 8, 'h' => 3],
                ['id' => 'quick-actions', 'x' => 8, 'y' => 6, 'w' => 4, 'h' => 3],
            ],
        ];
    }

    /**
     * Get default dashboard settings
     */
    private function getDefaultDashboardSettings(): array
    {
        return [
            'auto_refresh' => true,
            'refresh_interval' => 60, // seconds
            'show_tooltips' => true,
            'compact_widgets' => false,
            'show_gridlines' => false,
            'enable_animations' => true,
            'date_format' => 'd/m/Y H:i',
            'timezone' => 'Europe/Warsaw',
        ];
    }

    /**
     * Clear theme cache dla user
     */
    private function clearUserThemeCache(User $user): void
    {
        Cache::forget("admin_theme_user_{$user->id}");
        Cache::forget("admin_theme_css_user_{$user->id}");
    }

    /**
     * Export theme configuration
     */
    public function exportTheme(AdminTheme $theme): array
    {
        return [
            'theme_name' => $theme->theme_name,
            'configuration' => $theme->configuration,
            'created_at' => $theme->created_at->toISOString(),
            'version' => '1.0',
        ];
    }

    /**
     * Import theme configuration
     */
    public function importTheme(User $user, array $config): AdminTheme
    {
        // Validate import config
        if (!isset($config['theme_name']) || !isset($config['configuration'])) {
            throw new \InvalidArgumentException('Invalid theme configuration');
        }
        
        $themeData = [
            'user_id' => $user->id,
            'theme_name' => $config['theme_name'] . ' (Imported)',
            'primary_color' => $config['configuration']['colors']['primary'] ?? '#3b82f6',
            'secondary_color' => $config['configuration']['colors']['secondary'] ?? '#64748b',
            'accent_color' => $config['configuration']['colors']['accent'] ?? '#10b981',
            'layout_density' => $config['configuration']['layout']['density'] ?? 'normal',
            'sidebar_position' => $config['configuration']['layout']['sidebar_position'] ?? 'left',
            'header_style' => $config['configuration']['layout']['header_style'] ?? 'fixed',
            'custom_css' => $config['configuration']['custom_css'] ?? null,
            'company_name' => $config['configuration']['branding']['company_name'] ?? 'PPM Admin',
            'company_colors' => $config['configuration']['branding']['company_colors'] ?? [],
            'widget_layout' => $config['configuration']['widgets'] ?? $this->getDefaultWidgetLayout(),
            'dashboard_settings' => $config['configuration']['dashboard'] ?? $this->getDefaultDashboardSettings(),
            'is_active' => false,
        ];
        
        return $this->createTheme($user, $themeData);
    }
}