<?php

/**
 * Polish Validation Messages
 *
 * Custom validation messages for PPM-CC-Laravel application.
 *
 * This file contains Polish translations for Laravel validation rules,
 * including custom messages for product variant validation.
 *
 * ORGANIZATION:
 * - Standard Laravel validation messages (overrides)
 * - Custom attribute names
 * - Custom validation messages per component
 *
 * @package Lang\Pl
 * @version 1.0
 * @since ETAP_05b Phase 6 (2025-10-30)
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Standard Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Polish translations for Laravel's built-in validation rules.
    |
    */

    'accepted' => 'Pole :attribute musi zostać zaakceptowane.',
    'active_url' => 'Pole :attribute nie jest prawidłowym adresem URL.',
    'after' => 'Pole :attribute musi być datą późniejszą od :date.',
    'after_or_equal' => 'Pole :attribute musi być datą późniejszą lub równą :date.',
    'alpha' => 'Pole :attribute może zawierać tylko litery.',
    'alpha_dash' => 'Pole :attribute może zawierać tylko litery, cyfry, myślniki i podkreślenia.',
    'alpha_num' => 'Pole :attribute może zawierać tylko litery i cyfry.',
    'array' => 'Pole :attribute musi być tablicą.',
    'before' => 'Pole :attribute musi być datą wcześniejszą od :date.',
    'before_or_equal' => 'Pole :attribute musi być datą wcześniejszą lub równą :date.',
    'between' => [
        'numeric' => 'Pole :attribute musi zawierać się w granicach :min - :max.',
        'file' => 'Pole :attribute musi zawierać się w granicach :min - :max kilobajtów.',
        'string' => 'Pole :attribute musi zawierać się w granicach :min - :max znaków.',
        'array' => 'Pole :attribute musi składać się z :min - :max elementów.',
    ],
    'boolean' => 'Pole :attribute musi mieć wartość prawda albo fałsz.',
    'confirmed' => 'Potwierdzenie pola :attribute nie zgadza się.',
    'date' => 'Pole :attribute nie jest prawidłową datą.',
    'date_equals' => 'Pole :attribute musi być datą równą :date.',
    'date_format' => 'Pole :attribute nie odpowiada formatowi :format.',
    'different' => 'Pole :attribute oraz :other muszą się różnić.',
    'digits' => 'Pole :attribute musi składać się z :digits cyfr.',
    'digits_between' => 'Pole :attribute musi mieć od :min do :max cyfr.',
    'dimensions' => 'Pole :attribute ma nieprawidłowe wymiary.',
    'distinct' => 'Pole :attribute ma zduplikowaną wartość.',
    'email' => 'Pole :attribute musi być prawidłowym adresem e-mail.',
    'ends_with' => 'Pole :attribute musi kończyć się jedną z wartości: :values.',
    'exists' => 'Wybrana wartość dla :attribute jest nieprawidłowa.',
    'file' => 'Pole :attribute musi być plikiem.',
    'filled' => 'Pole :attribute nie może być puste.',
    'gt' => [
        'numeric' => 'Pole :attribute musi być większe od :value.',
        'file' => 'Pole :attribute musi być większe od :value kilobajtów.',
        'string' => 'Pole :attribute musi być dłuższe od :value znaków.',
        'array' => 'Pole :attribute musi mieć więcej niż :value elementów.',
    ],
    'gte' => [
        'numeric' => 'Pole :attribute musi być większe lub równe :value.',
        'file' => 'Pole :attribute musi być większe lub równe :value kilobajtów.',
        'string' => 'Pole :attribute musi być dłuższe lub równe :value znaków.',
        'array' => 'Pole :attribute musi mieć :value lub więcej elementów.',
    ],
    'image' => 'Pole :attribute musi być obrazkiem.',
    'in' => 'Wybrana wartość dla :attribute jest nieprawidłowa.',
    'in_array' => 'Pole :attribute nie znajduje się w :other.',
    'integer' => 'Pole :attribute musi być liczbą całkowitą.',
    'ip' => 'Pole :attribute musi być prawidłowym adresem IP.',
    'ipv4' => 'Pole :attribute musi być prawidłowym adresem IPv4.',
    'ipv6' => 'Pole :attribute musi być prawidłowym adresem IPv6.',
    'json' => 'Pole :attribute musi być poprawnym ciągiem znaków JSON.',
    'lt' => [
        'numeric' => 'Pole :attribute musi być mniejsze od :value.',
        'file' => 'Pole :attribute musi być mniejsze od :value kilobajtów.',
        'string' => 'Pole :attribute musi być krótsze od :value znaków.',
        'array' => 'Pole :attribute musi mieć mniej niż :value elementów.',
    ],
    'lte' => [
        'numeric' => 'Pole :attribute musi być mniejsze lub równe :value.',
        'file' => 'Pole :attribute musi być mniejsze lub równe :value kilobajtów.',
        'string' => 'Pole :attribute musi być krótsze lub równe :value znaków.',
        'array' => 'Pole :attribute musi mieć :value lub mniej elementów.',
    ],
    'max' => [
        'numeric' => 'Pole :attribute nie może być większe od :max.',
        'file' => 'Pole :attribute nie może być większe od :max kilobajtów.',
        'string' => 'Pole :attribute nie może być dłuższe od :max znaków.',
        'array' => 'Pole :attribute nie może mieć więcej niż :max elementów.',
    ],
    'mimes' => 'Pole :attribute musi być plikiem typu :values.',
    'mimetypes' => 'Pole :attribute musi być plikiem typu :values.',
    'min' => [
        'numeric' => 'Pole :attribute musi być nie mniejsze od :min.',
        'file' => 'Pole :attribute musi mieć przynajmniej :min kilobajtów.',
        'string' => 'Pole :attribute musi mieć przynajmniej :min znaków.',
        'array' => 'Pole :attribute musi mieć przynajmniej :min elementów.',
    ],
    'not_in' => 'Wybrana wartość dla :attribute jest nieprawidłowa.',
    'not_regex' => 'Format pola :attribute jest nieprawidłowy.',
    'numeric' => 'Pole :attribute musi być liczbą.',
    'password' => 'Hasło jest nieprawidłowe.',
    'present' => 'Pole :attribute musi być obecne.',
    'regex' => 'Format pola :attribute jest nieprawidłowy.',
    'required' => 'Pole :attribute jest wymagane.',
    'required_if' => 'Pole :attribute jest wymagane gdy :other ma wartość :value.',
    'required_unless' => 'Pole :attribute jest wymagane jeżeli :other nie ma wartości :values.',
    'required_with' => 'Pole :attribute jest wymagane gdy obecne jest :values.',
    'required_with_all' => 'Pole :attribute jest wymagane gdy obecne są wszystkie :values.',
    'required_without' => 'Pole :attribute jest wymagane gdy brak :values.',
    'required_without_all' => 'Pole :attribute jest wymagane gdy brak wszystkich :values.',
    'same' => 'Pole :attribute i :other muszą się zgadzać.',
    'size' => [
        'numeric' => 'Pole :attribute musi mieć rozmiar :size.',
        'file' => 'Pole :attribute musi mieć :size kilobajtów.',
        'string' => 'Pole :attribute musi mieć :size znaków.',
        'array' => 'Pole :attribute musi zawierać :size elementów.',
    ],
    'starts_with' => 'Pole :attribute musi zaczynać się jedną z wartości: :values.',
    'string' => 'Pole :attribute musi być ciągiem znaków.',
    'timezone' => 'Pole :attribute musi być prawidłową strefą czasową.',
    'unique' => 'Taka wartość pola :attribute już występuje.',
    'uploaded' => 'Nie udało się wgrać pliku :attribute.',
    'url' => 'Format pola :attribute jest nieprawidłowy.',
    'uuid' => 'Pole :attribute musi być poprawnym identyfikatorem UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Attribute Names
    |--------------------------------------------------------------------------
    |
    | Polish names for form attributes used in validation messages.
    |
    */

    'attributes' => [
        // Variant Basic Data
        'sku' => 'SKU',
        'name' => 'nazwa',
        'is_active' => 'aktywny',
        'is_default' => 'domyślny',
        'position' => 'pozycja',

        // Variant Attributes
        'attribute_type_id' => 'typ atrybutu',
        'attribute_value_id' => 'wartość atrybutu',

        // Variant Pricing
        'price' => 'cena',
        'price_special' => 'cena promocyjna',
        'special_from' => 'data rozpoczęcia promocji',
        'special_to' => 'data zakończenia promocji',

        // Variant Stock
        'warehouse_id' => 'magazyn',
        'quantity' => 'ilość',
        'reserved' => 'zarezerwowana',

        // Variant Images
        'image' => 'zdjęcie',
        'alt_text' => 'tekst alternatywny',

        // Bulk Operations
        'variant_ids' => 'lista wariantów',
        'action' => 'akcja',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Messages
    |--------------------------------------------------------------------------
    |
    | Custom validation messages for specific fields and components.
    |
    */

    'custom' => [
        // Variant Data Validation
        'variantData.sku' => [
            'required' => 'SKU wariantu jest wymagane.',
            'unique' => 'Ten SKU jest już używany przez inny produkt lub wariant.',
            'max' => 'SKU nie może przekraczać :max znaków.',
            'regex' => 'SKU może zawierać tylko litery, cyfry, myślniki i podkreślenia.',
        ],
        'variantData.name' => [
            'required' => 'Nazwa wariantu jest wymagana.',
            'max' => 'Nazwa wariantu nie może przekraczać :max znaków.',
            'regex' => 'Nazwa wariantu zawiera niedozwolone znaki.',
        ],
        'variantData.position' => [
            'integer' => 'Pozycja musi być liczbą całkowitą.',
            'min' => 'Pozycja nie może być ujemna.',
            'max' => 'Pozycja nie może przekraczać :max.',
        ],

        // Variant Attributes Validation
        'variantAttributes.attribute_type_id' => [
            'required' => 'Typ atrybutu jest wymagany.',
            'exists' => 'Wybrany typ atrybutu nie istnieje.',
        ],
        'variantAttributes.attribute_value_id' => [
            'required' => 'Wartość atrybutu jest wymagana.',
            'exists' => 'Wybrana wartość atrybutu nie istnieje.',
        ],

        // Variant Price Validation
        'variantPrice.price' => [
            'required' => 'Cena jest wymagana.',
            'numeric' => 'Cena musi być liczbą.',
            'min' => 'Cena nie może być ujemna.',
            'max' => 'Cena nie może przekraczać :max.',
            'regex' => 'Cena może mieć maksymalnie 2 miejsca po przecinku.',
        ],
        'variantPrice.price_special' => [
            'numeric' => 'Cena promocyjna musi być liczbą.',
            'min' => 'Cena promocyjna nie może być ujemna.',
            'max' => 'Cena promocyjna nie może przekraczać :max.',
            'regex' => 'Cena promocyjna może mieć maksymalnie 2 miejsca po przecinku.',
            'lt' => 'Cena promocyjna musi być niższa niż cena regularna.',
        ],
        'variantPrice.special_from' => [
            'date' => 'Data rozpoczęcia promocji jest nieprawidłowa.',
            'before_or_equal' => 'Data rozpoczęcia musi być wcześniejsza lub równa dacie zakończenia.',
        ],
        'variantPrice.special_to' => [
            'date' => 'Data zakończenia promocji jest nieprawidłowa.',
            'after_or_equal' => 'Data zakończenia musi być późniejsza lub równa dacie rozpoczęcia.',
        ],

        // Variant Stock Validation
        'variantStock.warehouse_id' => [
            'required' => 'Magazyn jest wymagany.',
            'exists' => 'Wybrany magazyn nie istnieje.',
        ],
        'variantStock.quantity' => [
            'required' => 'Ilość jest wymagana.',
            'integer' => 'Ilość musi być liczbą całkowitą.',
            'min' => 'Ilość nie może być ujemna.',
            'max' => 'Ilość nie może przekraczać :max.',
        ],
        'variantStock.reserved' => [
            'required' => 'Ilość zarezerwowana jest wymagana.',
            'integer' => 'Ilość zarezerwowana musi być liczbą całkowitą.',
            'min' => 'Ilość zarezerwowana nie może być ujemna.',
            'lte' => 'Zarezerwowana ilość nie może przekraczać dostępnej.',
        ],

        // Variant Image Validation
        'variantImage' => [
            'required' => 'Zdjęcie jest wymagane.',
            'image' => 'Plik musi być zdjęciem.',
            'mimes' => 'Dozwolone formaty: JPG, JPEG, PNG, WEBP.',
            'max' => 'Maksymalny rozmiar pliku to :max KB.',
            'dimensions' => 'Wymiary zdjęcia muszą być pomiędzy 200x200 a 5000x5000 pikseli.',
        ],

        // Bulk Operations Validation
        'bulk.variant_ids' => [
            'required' => 'Lista wariantów jest wymagana.',
            'array' => 'Lista wariantów musi być tablicą.',
            'min' => 'Wybierz co najmniej jeden wariant.',
            'max' => 'Maksymalnie :max wariantów w jednej operacji.',
        ],
        'bulk.variant_ids.*' => [
            'exists' => 'Wybrany wariant nie istnieje.',
        ],
        'bulk.action' => [
            'required' => 'Akcja jest wymagana.',
            'in' => 'Nieprawidłowa akcja. Dostępne: :values.',
        ],
    ],

];
