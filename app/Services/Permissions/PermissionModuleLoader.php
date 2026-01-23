<?php

namespace App\Services\Permissions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

/**
 * Permission Module Loader Service
 *
 * Auto-discovers and loads permission modules from config/permissions/*.php
 * Provides API for PermissionMatrix component and RolePermissionSeeder.
 *
 * @see config/permissions/README.md for module format documentation
 */
class PermissionModuleLoader
{
    /**
     * Cache key for loaded modules
     */
    protected const CACHE_KEY = 'permission_modules';

    /**
     * Cache TTL in seconds (1 hour)
     */
    protected const CACHE_TTL = 3600;

    /**
     * Path to permission modules directory
     */
    protected string $modulesPath;

    /**
     * Loaded modules cache (per-request)
     */
    protected ?Collection $modules = null;

    public function __construct()
    {
        $this->modulesPath = config_path('permissions');
    }

    /**
     * Discover and load all permission modules
     *
     * @return Collection<string, array>
     */
    public function discoverModules(): Collection
    {
        if ($this->modules !== null) {
            return $this->modules;
        }

        // In production, use cache
        if (app()->environment('production')) {
            $this->modules = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
                return $this->loadModulesFromDisk();
            });
        } else {
            // In development, always load fresh
            $this->modules = $this->loadModulesFromDisk();
        }

        return $this->modules;
    }

    /**
     * Load modules from disk
     *
     * @return Collection<string, array>
     */
    protected function loadModulesFromDisk(): Collection
    {
        if (!File::isDirectory($this->modulesPath)) {
            return collect();
        }

        $modules = collect();

        foreach (File::glob($this->modulesPath . '/*.php') as $file) {
            $filename = basename($file, '.php');

            // Skip template and special files
            if (str_starts_with($filename, '_')) {
                continue;
            }

            $config = require $file;

            // Validate module structure
            if ($this->validateModule($config)) {
                $modules->put($config['module'], $config);
            }
        }

        // Sort by order
        return $modules->sortBy('order');
    }

    /**
     * Get a single module by name
     *
     * @param string $name Module identifier
     * @return array|null
     */
    public function getModule(string $name): ?array
    {
        return $this->discoverModules()->get($name);
    }

    /**
     * Get all permissions as flat collection
     *
     * @return Collection<int, array>
     */
    public function getAllPermissions(): Collection
    {
        $permissions = collect();

        foreach ($this->discoverModules() as $module) {
            foreach ($module['permissions'] as $key => $permission) {
                $permissions->push([
                    'name' => $permission['name'],
                    'label' => $permission['label'],
                    'description' => $permission['description'] ?? '',
                    'dangerous' => $permission['dangerous'] ?? false,
                    'module' => $module['module'],
                    'module_name' => $module['name'],
                ]);
            }
        }

        return $permissions;
    }

    /**
     * Get modules with their permissions for UI display
     *
     * @return array<string, array>
     */
    public function getPermissionsByModule(): array
    {
        $result = [];

        foreach ($this->discoverModules() as $moduleKey => $module) {
            $result[$module['name']] = [
                'module' => $module['module'],
                'name' => $module['name'],
                'description' => $module['description'] ?? '',
                'icon' => $module['icon'] ?? 'document',
                'color' => $module['color'] ?? 'gray',
                'order' => $module['order'] ?? 100,
                'permissions' => collect($module['permissions'])->map(function ($perm) use ($module) {
                    return [
                        'name' => $perm['name'],
                        'label' => $perm['label'],
                        'description' => $perm['description'] ?? '',
                        'dangerous' => $perm['dangerous'] ?? false,
                    ];
                })->values()->all(),
            ];
        }

        return $result;
    }

    /**
     * Get module display order
     *
     * @return array<string, string> [module_key => display_name]
     */
    public function getModuleOrder(): array
    {
        return $this->discoverModules()
            ->mapWithKeys(fn($module) => [$module['module'] => $module['name']])
            ->all();
    }

    /**
     * Get default permissions for a role
     *
     * @param string $role Role name
     * @return array<string> Permission names
     */
    public function getRoleDefaults(string $role): array
    {
        $permissions = [];

        foreach ($this->discoverModules() as $module) {
            $roleDefaults = $module['role_defaults'] ?? [];

            if (isset($roleDefaults[$role])) {
                foreach ($roleDefaults[$role] as $permissionKey) {
                    if (isset($module['permissions'][$permissionKey])) {
                        $permissions[] = $module['permissions'][$permissionKey]['name'];
                    }
                }
            }
        }

        return $permissions;
    }

    /**
     * Get all role defaults for all roles
     *
     * @return array<string, array<string>> [role_name => [permission_names]]
     */
    public function getAllRoleDefaults(): array
    {
        $roles = [];

        foreach ($this->discoverModules() as $module) {
            foreach ($module['role_defaults'] ?? [] as $roleName => $permissionKeys) {
                if (!isset($roles[$roleName])) {
                    $roles[$roleName] = [];
                }

                foreach ($permissionKeys as $permissionKey) {
                    if (isset($module['permissions'][$permissionKey])) {
                        $roles[$roleName][] = $module['permissions'][$permissionKey]['name'];
                    }
                }
            }
        }

        return $roles;
    }

    /**
     * Validate module configuration structure
     *
     * @param mixed $config Module configuration
     * @return bool
     */
    public function validateModule($config): bool
    {
        if (!is_array($config)) {
            return false;
        }

        // Required fields
        $requiredFields = ['module', 'name', 'permissions'];

        foreach ($requiredFields as $field) {
            if (!isset($config[$field])) {
                return false;
            }
        }

        // Module identifier must be string
        if (!is_string($config['module']) || empty($config['module'])) {
            return false;
        }

        // Permissions must be array
        if (!is_array($config['permissions']) || empty($config['permissions'])) {
            return false;
        }

        // Validate each permission
        foreach ($config['permissions'] as $key => $permission) {
            if (!isset($permission['name']) || !isset($permission['label'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Clear cached modules
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->modules = null;
    }

    /**
     * Get list of available module files
     *
     * @return array<string>
     */
    public function getModuleFiles(): array
    {
        if (!File::isDirectory($this->modulesPath)) {
            return [];
        }

        return collect(File::glob($this->modulesPath . '/*.php'))
            ->map(fn($file) => basename($file, '.php'))
            ->filter(fn($name) => !str_starts_with($name, '_'))
            ->values()
            ->all();
    }

    /**
     * Check if a module exists
     *
     * @param string $name Module identifier
     * @return bool
     */
    public function moduleExists(string $name): bool
    {
        return $this->discoverModules()->has($name);
    }

    /**
     * Get permissions count per module
     *
     * @return array<string, int>
     */
    public function getPermissionCounts(): array
    {
        return $this->discoverModules()
            ->mapWithKeys(fn($module) => [
                $module['name'] => count($module['permissions'])
            ])
            ->all();
    }

    /**
     * Get total permissions count
     *
     * @return int
     */
    public function getTotalPermissionsCount(): int
    {
        return $this->getAllPermissions()->count();
    }
}
