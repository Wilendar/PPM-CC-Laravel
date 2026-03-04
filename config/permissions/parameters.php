<?php

/**
 * Parameters Permission Module
 *
 * Granular per-tab permissions for /admin/product-parameters panel.
 * 8 tabs x read/edit + general read for backward compatibility.
 *
 * Tabs: attributes, manufacturers, warehouses, product_types,
 *       data_cleanup, status_monitoring, smart_matching, category_mappings
 */

return [
    'module' => 'parameters',
    'name' => 'Parametry Produktow',
    'description' => 'Zarzadzanie parametrami produktow (per-tab granularity)',
    'icon' => 'adjustments-horizontal',
    'order' => 15,
    'color' => 'teal',

    'permissions' => [
        // General access (backward compat fallback)
        'read' => [
            'name' => 'parameters.read',
            'label' => 'Odczyt ogolny',
            'description' => 'Ogolny dostep do panelu parametrow (fallback)',
            'dangerous' => false,
        ],

        // Tab: Atrybuty / Warianty
        'attributes_read' => [
            'name' => 'parameters.attributes.read',
            'label' => 'Atrybuty - odczyt',
            'description' => 'Przegladanie atrybutow i wariantow',
            'dangerous' => false,
        ],
        'attributes_edit' => [
            'name' => 'parameters.attributes.edit',
            'label' => 'Atrybuty - edycja',
            'description' => 'Edycja atrybutow i wariantow',
            'dangerous' => false,
        ],

        // Tab: Producenci / Marki
        'manufacturers_read' => [
            'name' => 'parameters.manufacturers.read',
            'label' => 'Producenci - odczyt',
            'description' => 'Przegladanie producentow/marek',
            'dangerous' => false,
        ],
        'manufacturers_edit' => [
            'name' => 'parameters.manufacturers.edit',
            'label' => 'Producenci - edycja',
            'description' => 'Edycja producentow/marek',
            'dangerous' => false,
        ],

        // Tab: Magazyny
        'warehouses_read' => [
            'name' => 'parameters.warehouses.read',
            'label' => 'Magazyny - odczyt',
            'description' => 'Przegladanie konfiguracji magazynow',
            'dangerous' => false,
        ],
        'warehouses_edit' => [
            'name' => 'parameters.warehouses.edit',
            'label' => 'Magazyny - edycja',
            'description' => 'Edycja konfiguracji magazynow',
            'dangerous' => false,
        ],

        // Tab: Typy produktow
        'product_types_read' => [
            'name' => 'parameters.product_types.read',
            'label' => 'Typy - odczyt',
            'description' => 'Przegladanie typow produktow',
            'dangerous' => false,
        ],
        'product_types_edit' => [
            'name' => 'parameters.product_types.edit',
            'label' => 'Typy - edycja',
            'description' => 'Edycja typow produktow',
            'dangerous' => false,
        ],

        // Tab: Czyszczenie danych
        'data_cleanup_read' => [
            'name' => 'parameters.data_cleanup.read',
            'label' => 'Czyszczenie - odczyt',
            'description' => 'Przegladanie osieroconych danych',
            'dangerous' => false,
        ],
        'data_cleanup_run' => [
            'name' => 'parameters.data_cleanup.run',
            'label' => 'Czyszczenie - uruchomienie',
            'description' => 'Uruchamianie czyszczenia osieroconych danych',
            'dangerous' => true,
        ],

        // Tab: Status monitoring
        'status_monitoring_read' => [
            'name' => 'parameters.status_monitoring.read',
            'label' => 'Status - odczyt',
            'description' => 'Przegladanie konfiguracji monitoringu statusu',
            'dangerous' => false,
        ],
        'status_monitoring_edit' => [
            'name' => 'parameters.status_monitoring.edit',
            'label' => 'Status - edycja',
            'description' => 'Edycja konfiguracji monitoringu statusu',
            'dangerous' => false,
        ],

        // Tab: Smart matching
        'smart_matching_read' => [
            'name' => 'parameters.smart_matching.read',
            'label' => 'Smart matching - odczyt',
            'description' => 'Przegladanie regul smart matching',
            'dangerous' => false,
        ],
        'smart_matching_edit' => [
            'name' => 'parameters.smart_matching.edit',
            'label' => 'Smart matching - edycja',
            'description' => 'Edycja regul smart matching',
            'dangerous' => false,
        ],

        // Tab: Mapowanie kategorii
        'category_mappings_read' => [
            'name' => 'parameters.category_mappings.read',
            'label' => 'Mapowanie - odczyt',
            'description' => 'Przegladanie mapowan kategorii na typy',
            'dangerous' => false,
        ],
        'category_mappings_edit' => [
            'name' => 'parameters.category_mappings.edit',
            'label' => 'Mapowanie - edycja',
            'description' => 'Edycja mapowan kategorii na typy',
            'dangerous' => false,
        ],

        // Legacy: delete (zachowane dla kompatybilnosci)
        'delete' => [
            'name' => 'parameters.delete',
            'label' => 'Usuwanie',
            'description' => 'Usuwanie parametrow produktow',
            'dangerous' => true,
        ],
    ],

    'role_defaults' => [
        'Admin' => [
            'read', 'delete',
            'attributes_read', 'attributes_edit',
            'manufacturers_read', 'manufacturers_edit',
            'warehouses_read', 'warehouses_edit',
            'product_types_read', 'product_types_edit',
            'data_cleanup_read', 'data_cleanup_run',
            'status_monitoring_read', 'status_monitoring_edit',
            'smart_matching_read', 'smart_matching_edit',
            'category_mappings_read', 'category_mappings_edit',
        ],
        'Manager' => [
            'read',
            'attributes_read', 'attributes_edit',
            'manufacturers_read', 'manufacturers_edit',
            'warehouses_read', 'warehouses_edit',
            'product_types_read', 'product_types_edit',
            'data_cleanup_read',
            'status_monitoring_read', 'status_monitoring_edit',
            'smart_matching_read', 'smart_matching_edit',
            'category_mappings_read', 'category_mappings_edit',
        ],
        'Edytor' => [
            'read',
            'attributes_read',
            'manufacturers_read',
            'product_types_read',
        ],
        'Magazyn' => [],
        'Handlowy' => [],
        'Reklamacje' => [],
        'User' => [],
    ],
];
