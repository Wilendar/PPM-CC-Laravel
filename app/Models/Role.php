<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Custom Role model extending Spatie Permission Role
 *
 * Adds support for PPM-specific role attributes:
 * - level: Role hierarchy (1=Admin ... 7=User)
 * - color: Badge color for UI
 * - description: Role description
 * - is_system: System role flag (cannot be deleted)
 */
class Role extends SpatieRole
{
    /**
     * The attributes that are mass assignable.
     * Extends Spatie's fillable with PPM custom columns.
     */
    protected $fillable = [
        'name',
        'guard_name',
        'level',
        'color',
        'description',
        'is_system',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'level' => 'integer',
        'is_system' => 'boolean',
    ];

    /**
     * Role hierarchy levels
     */
    public const LEVEL_ADMIN = 1;
    public const LEVEL_MANAGER = 2;
    public const LEVEL_EDITOR = 3;
    public const LEVEL_WAREHOUSEMAN = 4;
    public const LEVEL_SALESPERSON = 5;
    public const LEVEL_CLAIMS = 6;
    public const LEVEL_USER = 7;

    /**
     * Get hierarchy levels with their names
     */
    public static function getHierarchyLevels(): array
    {
        return [
            self::LEVEL_ADMIN => ['name' => 'Admin', 'color' => 'red'],
            self::LEVEL_MANAGER => ['name' => 'Manager', 'color' => 'orange'],
            self::LEVEL_EDITOR => ['name' => 'Editor', 'color' => 'green'],
            self::LEVEL_WAREHOUSEMAN => ['name' => 'Warehouseman', 'color' => 'blue'],
            self::LEVEL_SALESPERSON => ['name' => 'Salesperson', 'color' => 'purple'],
            self::LEVEL_CLAIMS => ['name' => 'Claims', 'color' => 'teal'],
            self::LEVEL_USER => ['name' => 'User', 'color' => 'gray'],
        ];
    }
}
