<?php

/**
 * FAZA 9.1.4: Import Panel Permission Module (P1-P11)
 *
 * Granularne uprawnienia do kolumn i akcji panelu importu produktow.
 * Kazda kolumna i kazdy przycisk akcji ma indywidualne uprawnienie.
 *
 * @see app/Http/Livewire/Products/Import/Traits/ImportPanelPermissionTrait.php
 */

return [
    'module' => 'import',
    'name' => 'Panel Importu',
    'description' => 'Granularne uprawnienia do kolumn i akcji panelu importu produktow',
    'icon' => 'arrow-down-tray',
    'order' => 15,
    'color' => 'green',

    'permissions' => [
        // P1: Obraz + Zdjecia
        'images' => [
            'name' => 'import.images',
            'label' => 'Obraz + Zdjecia',
            'description' => 'Kolumna Obraz oraz akcja Zdjecia w wierszu',
            'dangerous' => false,
        ],

        // P2: SKU + Nazwa + Typ (modal import)
        'basic_data' => [
            'name' => 'import.basic_data',
            'label' => 'SKU + Nazwa + Typ',
            'description' => 'Kolumny SKU, Nazwa, Typ produktu oraz przycisk Importuj Produkty',
            'dangerous' => false,
        ],

        // P3: Cena
        'prices' => [
            'name' => 'import.prices',
            'label' => 'Cena',
            'description' => 'Kolumna Cena oraz modal edycji cen',
            'dangerous' => false,
        ],

        // P4: Kategorie L3-L6
        'categories' => [
            'name' => 'import.categories',
            'label' => 'Kategorie',
            'description' => 'Kolumny kategorii (L3, L4, L5, L6)',
            'dangerous' => false,
        ],

        // P5: Publikacja (targety)
        'publication_targets' => [
            'name' => 'import.publication_targets',
            'label' => 'Publikacja',
            'description' => 'Kolumna Publikacja - zarzadzanie targetami eksportu',
            'dangerous' => false,
        ],

        // P6: Warianty
        'variants' => [
            'name' => 'import.variants',
            'label' => 'Warianty',
            'description' => 'Akcja tworzenia i edycji wariantow produktu',
            'dangerous' => false,
        ],

        // P7: Dopasowania
        'compatibility' => [
            'name' => 'import.compatibility',
            'label' => 'Dopasowania',
            'description' => 'Akcja zarzadzania dopasowaniami (czesci zamienne)',
            'dangerous' => false,
        ],

        // P8: Opisy
        'descriptions' => [
            'name' => 'import.descriptions',
            'label' => 'Opisy',
            'description' => 'Akcja edycji opisow produktu (krotki + pelny)',
            'dangerous' => false,
        ],

        // P9: Data i czas publikacji
        'schedule' => [
            'name' => 'import.schedule',
            'label' => 'Data i czas publikacji',
            'description' => 'Kolumna harmonogramu publikacji - ustawianie daty/czasu',
            'dangerous' => false,
        ],

        // P10: Przycisk Publikuj
        'publish' => [
            'name' => 'import.publish',
            'label' => 'Przycisk Publikuj',
            'description' => 'Mozliwosc publikacji produktow do PPM i systemow zewnetrznych',
            'dangerous' => true,
        ],

        // P11: Duplikuj + Usun
        'manage' => [
            'name' => 'import.manage',
            'label' => 'Duplikuj + Usun',
            'description' => 'Duplikowanie i usuwanie produktow z panelu importu',
            'dangerous' => true,
        ],

        // P12: Cofnij publikacje
        'unpublish' => [
            'name' => 'import.unpublish',
            'label' => 'Cofnij publikacje',
            'description' => 'Mozliwosc cofniecia publikacji - usuwa produkt z PPM i systemow zewnetrznych',
            'dangerous' => true,
        ],
    ],

    'role_defaults' => [
        'Admin' => [
            'images', 'basic_data', 'prices', 'categories', 'publication_targets',
            'variants', 'compatibility', 'descriptions', 'schedule', 'publish', 'manage', 'unpublish',
        ],
        'Manager' => [
            'images', 'basic_data', 'prices', 'categories', 'publication_targets',
            'variants', 'compatibility', 'descriptions', 'schedule', 'publish', 'manage', 'unpublish',
        ],
        'Editor' => [
            'images', 'basic_data', 'categories', 'descriptions',
        ],
        'Warehouseman' => [
            'basic_data',
        ],
        'Salesperson' => [
            'basic_data', 'prices',
        ],
        'Claims' => [],
        'User' => [],
    ],
];
