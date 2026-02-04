# Integration Labels Skill

## Overview

Ten skill wymusza prawidłowe użycie labels (label_color, label_icon) przy tworzeniu statusów powiązań z integracjami ERP i PrestaShop. **ZAWSZE** używaj rzeczywistych nazw i kolorów z bazy danych zamiast hardcodowanych wartości.

---

## Kiedy używać tego Skilla

Użyj tego skilla gdy:

- **Tworzysz** komponenty wyświetlające powiązania z integracjami
- **Modyfikujesz** kod wyświetlający statusy synchronizacji
- **Dodajesz** nowe miejsca pokazujące nazwy integracji ERP/PrestaShop
- **Implementujesz** badges, tagi lub etykiety dla powiązań produktów
- **Refaktorujesz** kod używający hardcoded nazw integracji

**Trigger patterns:**
- Kod zawierający "Subiekt", "Baselinker", "Dynamics", "PrestaShop" jako literały
- Hardcoded kolory dla integracji (#f97316, #06b6d4)
- Wyświetlanie `erpData` lub `shopData` bez label_color/label_icon

---

## FUNDAMENTALNE ZASADY

### ZASADA 1: ZAKAZ HARDCODOWANIA NAZW INTEGRACJI

```php
// BŁĘDNIE - hardcoded nazwy
$label = 'Subiekt GT';
$label = 'Baselinker';
$color = '#f97316';

// PRAWIDŁOWO - zawsze z bazy danych
$label = $erpConnection->instance_name;
$color = $erpConnection->label_color;
$icon = $erpConnection->label_icon;
```

### ZASADA 2: ZAWSZE EAGER LOAD LABEL FIELDS

```php
// BŁĘDNIE - brak label fields w eager loading
$product->load('erpData.erpConnection:id,instance_name');

// PRAWIDŁOWO - pełne label fields
$product->load('erpData.erpConnection:id,instance_name,erp_type,label_color,label_icon');
$product->load('shopData.shop:id,name,label_color,label_icon');
```

### ZASADA 3: UŻYWAJ ACCESSORÓW Z FALLBACK

Modele mają zdefiniowane accessory z automatycznym fallback:

```php
// ERPConnection model
$connection->label_color;  // Zwraca label_color LUB default dla erp_type
$connection->label_icon;   // Zwraca label_icon LUB default dla erp_type

// PrestaShopShop model
$shop->label_color;  // Zwraca label_color LUB #06b6d4 (cyan)
$shop->label_icon;   // Zwraca label_icon LUB 'shopping-cart'
```

---

## WZORCE IMPLEMENTACJI

### Wzorzec 1: Wyświetlanie Badge ERP

```php
@foreach($product->erpData as $erpData)
    @php
        $erpColor = $erpData->erpConnection->label_color;
    @endphp
    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs"
          style="background-color: {{ $erpColor }}20; color: {{ $erpColor }}; border: 1px solid {{ $erpColor }}50;">
        {{ $erpData->erpConnection->instance_name }}
    </span>
@endforeach
```

### Wzorzec 2: Wyświetlanie Badge PrestaShop Shop

```php
@foreach($product->shopData as $shopData)
    @php
        $shopColor = $shopData->shop->label_color;
    @endphp
    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs"
          style="background-color: {{ $shopColor }}20; color: {{ $shopColor }}; border: 1px solid {{ $shopColor }}50;">
        {{ $shopData->shop->name }}
    </span>
@endforeach
```

### Wzorzec 3: Przygotowanie danych w Job/Service

```php
protected function getProductLinksData(Product $product): array
{
    $links = ['erp' => [], 'shops' => []];

    foreach ($product->erpData as $erpData) {
        $links['erp'][] = [
            'connection_id' => $erpData->erp_connection_id,
            'connection_name' => $erpData->erpConnection->instance_name,
            'external_id' => $erpData->external_id,
            'label_color' => $erpData->erpConnection->label_color,  // MANDATORY!
            'label_icon' => $erpData->erpConnection->label_icon,    // MANDATORY!
        ];
    }

    foreach ($product->shopData as $shopData) {
        $links['shops'][] = [
            'shop_id' => $shopData->shop_id,
            'shop_name' => $shopData->shop->name,
            'external_id' => $shopData->external_id,
            'label_color' => $shopData->shop->label_color,  // MANDATORY!
            'label_icon' => $shopData->shop->label_icon,    // MANDATORY!
        ];
    }

    return $links;
}
```

### Wzorzec 4: Wyświetlanie z JSON data (np. scan results)

```php
@php
    $links = $result->ppm_data['links'] ?? [];
    $erpLinks = $links['erp'] ?? [];
    $shopLinks = $links['shops'] ?? [];

    // Default colors (fallback jeśli brak w danych)
    $defaultErpColor = '#f97316';  // orange-500
    $defaultShopColor = '#06b6d4'; // cyan-500
@endphp

@foreach($erpLinks as $erp)
    @php
        $erpColor = $erp['label_color'] ?? $defaultErpColor;
    @endphp
    <span style="background-color: {{ $erpColor }}20; color: {{ $erpColor }};">
        {{ $erp['connection_name'] }}
    </span>
@endforeach
```

---

## KOLORY I IKONY - REFERENCE

### ERP Default Colors (ERPConnection::LABEL_COLORS)

| ERP Type | Default Color | Hex |
|----------|---------------|-----|
| subiekt_gt | Orange | #f97316 |
| baselinker | Green | #22c55e |
| dynamics | Blue | #3b82f6 |
| other | Gray | #6b7280 |

### ERP Default Icons (ERPConnection::LABEL_ICONS)

| ERP Type | Default Icon |
|----------|--------------|
| subiekt_gt | database |
| baselinker | shopping-bag |
| dynamics | cube |
| other | cog |

### PrestaShop Defaults

| Field | Default Value |
|-------|---------------|
| label_color | #06b6d4 (cyan-500) |
| label_icon | shopping-cart |

---

## CHECKLIST PRZED COMMIT

- [ ] Brak hardcoded nazw integracji ("Subiekt", "Baselinker", etc.)
- [ ] Brak hardcoded kolorów (#f97316, #06b6d4) bez fallback logic
- [ ] Eager loading zawiera label_color i label_icon
- [ ] Używane są accessory modeli (nie surowe column values)
- [ ] JSON data zawiera label_color i label_icon przy serializacji

---

## ANTI-PATTERNS

### BŁĄD 1: Hardcoded switch na erp_type dla kolorów

```php
// BŁĘDNIE!
switch ($erpData->erpConnection->erp_type) {
    case 'subiekt_gt': $color = '#f97316'; break;
    case 'baselinker': $color = '#22c55e'; break;
}

// PRAWIDŁOWO - accessor robi to za Ciebie
$color = $erpData->erpConnection->label_color;
```

### BŁĄD 2: Brak label_color w serialization

```php
// BŁĘDNIE!
$data = [
    'connection_name' => $connection->instance_name,
    // brak label_color i label_icon!
];

// PRAWIDŁOWO
$data = [
    'connection_name' => $connection->instance_name,
    'label_color' => $connection->label_color,
    'label_icon' => $connection->label_icon,
];
```

### BŁĄD 3: Ignorowanie fallback w Blade

```php
// BŁĘDNIE - może być null!
style="color: {{ $erp['label_color'] }};"

// PRAWIDŁOWO - z fallback
@php $color = $erp['label_color'] ?? '#f97316'; @endphp
style="color: {{ $color }};"
```

---

## PLIKI REFERENCE

| Plik | Opis |
|------|------|
| `app/Models/ERPConnection.php` | LABEL_COLORS, LABEL_ICONS, accessors |
| `app/Models/PrestaShopShop.php` | DEFAULT_LABEL_COLOR, DEFAULT_LABEL_ICON |
| `app/Jobs/Scan/ScanProductLinksJob.php` | Wzorzec getProductLinksData() |
| `resources/views/livewire/admin/scan/partials/results-table.blade.php` | Wzorzec wyświetlania |

---

## SYSTEM UCZENIA SIĘ

### Tracking Informacji
Ten skill automatycznie zbiera:
- Liczba naprawionych hardcoded nazw
- Pliki z brakującymi label fields
- Miejsca bez fallback values

### Metryki Sukcesu
- Zero hardcoded integration names: 100%
- Label fields w eager loading: 100%
- Fallback values w Blade: 100%

### Historia Ulepszeń
<!-- Automatycznie generowane przy każdej aktualizacji -->

---

## CHANGELOG

### v1.0.0 (2026-02-03)
- [INIT] Początkowa wersja skilla
- [FEATURE] Zasady użycia label_color i label_icon
- [FEATURE] Wzorce implementacji dla ERP i PrestaShop
- [FEATURE] Anti-patterns i checklist
- [DOCS] Reference plików i kolorów

---

**Last Updated:** 2026-02-03
**Source:** ERPConnection model, PrestaShopShop model, Scan Jobs
