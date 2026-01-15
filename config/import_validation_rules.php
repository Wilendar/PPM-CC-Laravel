<?php

return [
    /**
     * Field Validation Rules for Import System
     *
     * Definuje reguły walidacji dla różnych pól produktów podczas importu.
     * Użycie: Import Preview, PendingProduct validation, final Product creation
     *
     * @since ETAP_08 - Import/Export System
     */

    /**
     * Core Product Fields
     */
    'sku' => [
        'label' => 'SKU',
        'required' => true,
        'rules' => 'required|string|min:3|max:50|regex:/^[A-Z0-9\-_.]+$/i|unique:products,sku',
        'help' => 'Unikalny kod produktu (3-50 znaków, tylko A-Z, 0-9, -, _, .)',
        'examples' => ['ABC123', 'PART-001', 'SKU_2024.V1'],
    ],

    'name' => [
        'label' => 'Nazwa produktu',
        'required' => true,
        'rules' => 'required|string|max:255',
        'help' => 'Pełna nazwa produktu',
    ],

    'product_type' => [
        'label' => 'Typ produktu',
        'required' => true,
        'rules' => 'required|string|in:pojazd,część_zamienna,odzież,ogólny',
        'help' => 'Typ produktu determinuje dostępne cechy i dopasowania',
        'options' => [
            'pojazd' => 'Pojazd (motocykl, motorower, quad)',
            'część_zamienna' => 'Część zamienna',
            'odzież' => 'Odzież i akcesoria',
            'ogólny' => 'Produkt ogólny',
        ],
    ],

    /**
     * Category Fields
     */
    'category_l3' => [
        'label' => 'Kategoria główna (L3)',
        'required' => true,
        'rules' => 'required|string|max:255',
        'help' => 'Kategoria poziomu 3 (najwyższy poziom)',
    ],

    'category_l4' => [
        'label' => 'Podkategoria (L4)',
        'required' => false,
        'rules' => 'nullable|string|max:255',
        'help' => 'Kategoria poziomu 4 (opcjonalna)',
    ],

    'category_l5' => [
        'label' => 'Podkategoria szczegółowa (L5)',
        'required' => false,
        'rules' => 'nullable|string|max:255',
        'help' => 'Kategoria poziomu 5 (najniższy, opcjonalna)',
    ],

    /**
     * Pricing & Stock
     */
    'price' => [
        'label' => 'Cena detaliczna',
        'required' => true,
        'rules' => 'required|numeric|min:0|max:999999.99',
        'help' => 'Cena w PLN (do 999 999.99 zł)',
        'format' => 'numeric',
    ],

    'stock' => [
        'label' => 'Stan magazynowy',
        'required' => false,
        'rules' => 'nullable|integer|min:0|max:999999',
        'help' => 'Liczba sztuk w magazynie',
        'format' => 'integer',
    ],

    /**
     * Physical Attributes
     */
    'weight' => [
        'label' => 'Waga (kg)',
        'required' => false,
        'rules' => 'nullable|numeric|min:0|max:99999.99',
        'help' => 'Waga w kilogramach',
        'format' => 'numeric',
    ],

    'manufacturer' => [
        'label' => 'Producent',
        'required' => false,
        'rules' => 'nullable|string|max:255',
        'help' => 'Nazwa producenta',
    ],

    'manufacturer_code' => [
        'label' => 'Kod producenta',
        'required' => false,
        'rules' => 'nullable|string|max:100',
        'help' => 'Kod producenta (MRF CODE)',
    ],

    /**
     * Vehicle-specific Fields
     */
    'vehicle_model' => [
        'label' => 'Model pojazdu',
        'required' => false,
        'rules' => 'nullable|string|max:255',
        'help' => 'Model pojazdu (dla product_type=pojazd)',
        'applies_to' => ['pojazd'],
    ],

    'vehicle_year' => [
        'label' => 'Rok produkcji',
        'required' => false,
        'rules' => 'nullable|integer|min:1900|max:2100',
        'help' => 'Rok produkcji pojazdu',
        'applies_to' => ['pojazd'],
        'format' => 'integer',
    ],

    'vehicle_engine' => [
        'label' => 'Silnik',
        'required' => false,
        'rules' => 'nullable|string|max:255',
        'help' => 'Opis silnika (np. "88cc 4T")',
        'applies_to' => ['pojazd'],
    ],

    'vehicle_vin' => [
        'label' => 'VIN',
        'required' => false,
        'rules' => 'nullable|string|max:50',
        'help' => 'Numer VIN pojazdu',
        'applies_to' => ['pojazd'],
    ],

    /**
     * Compatibility Fields (for parts)
     */
    'compatibility_original' => [
        'label' => 'Dopasowania - Oryginał',
        'required' => false,
        'rules' => 'nullable|string|max:1000',
        'help' => 'Modele pojazdów (oryginał), rozdzielone | (np. "YCF 50|YCF 88")',
        'applies_to' => ['część_zamienna'],
        'format' => 'pipe_delimited',
    ],

    'compatibility_replacement' => [
        'label' => 'Dopasowania - Zamiennik',
        'required' => false,
        'rules' => 'nullable|string|max:1000',
        'help' => 'Modele pojazdów (zamiennik), rozdzielone | (np. "Honda CRF50")',
        'applies_to' => ['część_zamienna'],
        'format' => 'pipe_delimited',
    ],

    /**
     * Variant Fields (for apparel)
     */
    'has_variants' => [
        'label' => 'Ma warianty?',
        'required' => false,
        'rules' => 'nullable|in:TAK,NIE,1,0,true,false',
        'help' => 'Czy produkt ma warianty (rozmiar, kolor)?',
        'applies_to' => ['odzież'],
        'format' => 'boolean',
    ],

    'variant_color' => [
        'label' => 'Wariant - Kolor',
        'required' => false,
        'rules' => 'nullable|string|max:50',
        'help' => 'Kolor wariantu (jeśli has_variants=TAK)',
        'applies_to' => ['odzież'],
    ],

    'variant_size' => [
        'label' => 'Wariant - Rozmiar',
        'required' => false,
        'rules' => 'nullable|string|max:20',
        'help' => 'Rozmiar wariantu (XS, S, M, L, XL, XXL)',
        'applies_to' => ['odzież'],
    ],

    'variant_sku_suffix' => [
        'label' => 'Wariant - SKU Suffix',
        'required' => false,
        'rules' => 'nullable|string|max:20|regex:/^[\-_A-Z0-9]+$/i',
        'help' => 'Przyrostek SKU dla wariantu (np. "-RED-L")',
        'applies_to' => ['odzież'],
    ],

    /**
     * Validation rule groups per product type
     */
    'required_fields_per_type' => [
        'pojazd' => ['sku', 'name', 'product_type', 'category_l3', 'price'],
        'część_zamienna' => ['sku', 'name', 'product_type', 'category_l3', 'price'],
        'odzież' => ['sku', 'name', 'product_type', 'category_l3', 'price'],
        'ogólny' => ['sku', 'name', 'product_type', 'category_l3', 'price'],
    ],

    'recommended_fields_per_type' => [
        'pojazd' => ['vehicle_model', 'vehicle_year', 'vehicle_engine', 'stock', 'manufacturer'],
        'część_zamienna' => ['compatibility_original', 'compatibility_replacement', 'stock', 'manufacturer_code'],
        'odzież' => ['has_variants', 'stock'],
        'ogólny' => ['stock', 'manufacturer'],
    ],
];
