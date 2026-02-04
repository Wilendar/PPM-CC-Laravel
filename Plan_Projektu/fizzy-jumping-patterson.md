# Plan: Integracja Wariantów PPM z Subiekt GT

**Data**: 2026-01-27
**Priorytet**: Szybka implementacja (2-3 dni)
**Status**: 🛠️ W PLANOWANIU

---

## Problem

| System | Warianty | Struktura |
|--------|----------|-----------|
| **PPM** | ✅ Parent-child | Product → ProductVariant (hasMany) |
| **PrestaShop** | ✅ Combinations | Zgodny z PPM |
| **BaseLinker** | ✅ Warianty | Zgodny z PPM |
| **Subiekt GT** | ❌ **BRAK** | Każdy wariant = osobny produkt |

**Rozwiązanie**: Wykorzystanie pola `tw_Pole8` (wolne!) do przechowywania `parent_sku`.

---

## Ustalenia z Użytkownikiem

- **SKU PPM**: Mieszane (część ma konwencję PARENT-SUFFIX, część nie)
- **SKU Subiekt**: Konwencja PARENT-VARIANT istnieje
- **Pole8**: WOLNE - idealne do parent_sku
- **Priorytet**: Szybka implementacja

---

## ETAP 1: REST API - Dodanie Pole6-8 (2-3h)

### 1.1 SubiektRepository.cs - SELECT

**Plik**: `_TOOLS/SubiektGT_REST_API_DotNet/SubiektRepository.cs`

```csharp
// Dodać do SELECT w GetProductsAsync, GetProductByIdAsync, GetProductBySkuAsync:
t.tw_Pole6 AS Pole6,
t.tw_Pole7 AS Pole7,
t.tw_Pole8 AS Pole8,

// Rozszerzyć model Product (~linia 519):
public string? Pole6 { get; set; }
public string? Pole7 { get; set; }
public string? Pole8 { get; set; }  // parent_sku dla wariantów
```

### 1.2 SferaProductWriter.cs - UPDATE

**Plik**: `_TOOLS/SubiektGT_REST_API_DotNet/SferaProductWriter.cs`

```csharp
// W buildUpdateSql dodać:
if (request.Pole6 != null) updates.Add("tw_Pole6 = @Pole6");
if (request.Pole7 != null) updates.Add("tw_Pole7 = @Pole7");
if (request.Pole8 != null) updates.Add("tw_Pole8 = @Pole8");

// Rozszerzyć ProductWriteRequest:
public string? Pole6 { get; set; }
public string? Pole7 { get; set; }
public string? Pole8 { get; set; }
```

### 1.3 Build i Deploy

```powershell
cd "_TOOLS/SubiektGT_REST_API_DotNet"
dotnet publish -c Release -o ./publish
# User: RDP upload publish/ → sapi.mpptrade.pl
```

---

## ETAP 2: Laravel - Rozszerzenie Klienta (2-3h)

### 2.1 SubiektRestApiClient.php

**Plik**: `app/Services/ERP/SubiektGT/SubiektRestApiClient.php`

```php
// W buildProductWriteBody() po lini ~578:
if (isset($data['pole6'])) $body['Pole6'] = $data['pole6'];
if (isset($data['pole7'])) $body['Pole7'] = $data['pole7'];
if (isset($data['pole8'])) $body['Pole8'] = $data['pole8'];  // parent_sku
```

### 2.2 SubiektDataTransformer.php

**Plik**: `app/Services/ERP/SubiektGT/SubiektDataTransformer.php`

```php
// W subiektToPPM() po lini ~141:
'Pole6' => $subiektProduct->Pole6 ?? $subiektProduct->pole6 ?? null,
'Pole7' => $subiektProduct->Pole7 ?? $subiektProduct->pole7 ?? null,
'Pole8' => $subiektProduct->Pole8 ?? $subiektProduct->pole8 ?? null,  // parent_sku
```

---

## ETAP 3: Logika Wariantów - PULL (3-4h)

### 3.1 Nowa klasa SubiektVariantResolver

**Nowy plik**: `app/Services/ERP/SubiektGT/SubiektVariantResolver.php`

Odpowiedzialność:
- `detectVariantRelation(object $product)` - wykrywa czy produkt jest wariantem
- `groupByParent(array $products)` - grupuje produkty po parent_sku

Logika detekcji (priorytet):
1. **tw_Pole8** zawiera parent_sku → wariant (pewny)
2. **SKU pattern** `PARENT-SUFFIX` i parent istnieje → wariant (potencjalny)
3. Brak detekcji → zwykły produkt

### 3.2 Modyfikacja SubiektGTService

**Plik**: `app/Services/ERP/SubiektGTService.php`

- Inject `SubiektVariantResolver`
- W `pullAllProductsViaRestApi()` grupować produkty przed importem
- Najpierw importować parenty, potem warianty
- Nowa metoda `importVariantFromSubiekt()`:
  - Tworzy/aktualizuje `ProductVariant`
  - Sync cen do `VariantPrice`
  - Sync stanów do `VariantStock`

---

## ETAP 4: Logika Wariantów - PUSH (3-4h)

### 4.1 Rozszerzenie syncProductViaRestApi

**Plik**: `app/Services/ERP/SubiektGTService.php`

```php
// Jeśli produkt ma warianty:
if ($product->variants()->exists()) {
    $this->syncProductVariantsToSubiekt($connection, $product);
}
```

### 4.2 Nowa metoda syncProductVariantsToSubiekt

Dla każdego wariantu:
1. Sprawdź czy istnieje w Subiekt (`productExists()`)
2. Jeśli TAK → `updateProductBySku()` z `pole8 = parent_sku`
3. Jeśli NIE → `createProduct()` z `pole8 = parent_sku`
4. Sync cen z `VariantPrice` → `PricesNet`
5. Sync stanów z `VariantStock` → odpowiedni magazyn

### 4.3 Nowa metoda buildVariantSyncData

Buduje payload dla API:
- `name` - nazwa wariantu
- `pole8` - parent_sku (KLUCZ!)
- `is_active` - status
- `prices` - z VariantPrice (mapowanie price_group_id → priceLevel)

---

## ETAP 5: Migracja Danych (opcjonalnie, 1-2h)

### 5.1 Artisan Command

**Nowy plik**: `app/Console/Commands/MigrateSubiektVariants.php`

```bash
php artisan subiekt:migrate-variants --dry-run
php artisan subiekt:migrate-variants --limit=100
```

Funkcje:
- Wykrywa warianty przez konwencję SKU
- Ustawia `tw_Pole8` = parent_sku dla wykrytych wariantów
- Raportuje postęp

---

## Pliki do Modyfikacji

| Plik | Zmiany |
|------|--------|
| `_TOOLS/SubiektGT_REST_API_DotNet/SubiektRepository.cs` | SELECT Pole6-8, model Product |
| `_TOOLS/SubiektGT_REST_API_DotNet/SferaProductWriter.cs` | UPDATE Pole6-8 |
| `app/Services/ERP/SubiektGT/SubiektRestApiClient.php` | buildProductWriteBody pole6-8 |
| `app/Services/ERP/SubiektGT/SubiektDataTransformer.php` | subiektToPPM pole6-8 |
| `app/Services/ERP/SubiektGT/SubiektVariantResolver.php` | **NOWY** - logika detekcji |
| `app/Services/ERP/SubiektGTService.php` | PULL/PUSH wariantów |
| `app/Console/Commands/MigrateSubiektVariants.php` | **NOWY** - migracja |

---

## Harmonogram

| Dzień | Etap | Czas | Rezultat |
|-------|------|------|----------|
| 1 | ETAP 1 + Deploy | 2-3h | REST API Pole6-8 |
| 1-2 | ETAP 2 | 2-3h | Laravel klient Pole6-8 |
| 2 | ETAP 3 | 3-4h | PULL wariantów działa |
| 3 | ETAP 4 | 3-4h | PUSH wariantów działa |
| 3 | ETAP 5 | 1-2h | Migracja (opcjonalnie) |

**RAZEM: 2-3 dni robocze**

---

## Weryfikacja Sukcesu

1. [ ] GET `/api/products/sku/{sku}` zwraca Pole6-8
2. [ ] PUT `/api/products/sku/{sku}` akceptuje Pole8
3. [ ] PULL: produkty z tw_Pole8 → ProductVariant w PPM
4. [ ] PUSH: warianty → tw_Pole8 = parent_sku w Subiekt
5. [ ] Ceny z VariantPrice sync do tw_Cena
6. [ ] Stany z VariantStock sync do tw_Stan

---

## Diagram Przepływu

```
PULL (Subiekt → PPM):
┌─────────────────────────────────────────────────────────────────┐
│ Subiekt GT                                                       │
│ tw__Towar: SKU=PROD-001-RED, tw_Pole8="PROD-001"                │
└───────────────────────────────┬─────────────────────────────────┘
                                │ REST API
                                ▼
┌───────────────────────────────────────────────────────────────────┐
│ SubiektVariantResolver.detectVariantRelation()                    │
│ → is_variant: true, parent_sku: "PROD-001"                       │
└───────────────────────────────┬───────────────────────────────────┘
                                ▼
┌───────────────────────────────────────────────────────────────────┐
│ PPM Laravel                                                       │
│ Product: SKU=PROD-001                                            │
│ └─ ProductVariant: SKU=PROD-001-RED, product_id=parent           │
│    ├─ VariantPrice (price_group_id=1..8)                         │
│    └─ VariantStock (warehouse_id=1..N)                           │
└───────────────────────────────────────────────────────────────────┘

PUSH (PPM → Subiekt):
┌───────────────────────────────────────────────────────────────────┐
│ PPM Laravel                                                       │
│ Product: SKU=PROD-001                                            │
│ └─ ProductVariant: SKU=PROD-001-RED                              │
└───────────────────────────────┬───────────────────────────────────┘
                                │ syncProductVariantsToSubiekt()
                                ▼
┌───────────────────────────────────────────────────────────────────┐
│ buildVariantSyncData():                                           │
│ {                                                                │
│   "name": "PROD-001-RED",                                        │
│   "Pole8": "PROD-001",  // parent_sku                            │
│   "PricesNet": {1: 100.00, 2: 90.00, ...}                        │
│ }                                                                │
└───────────────────────────────┬───────────────────────────────────┘
                                │ REST API PUT
                                ▼
┌───────────────────────────────────────────────────────────────────┐
│ Subiekt GT                                                        │
│ tw__Towar: SKU=PROD-001-RED, tw_Pole8="PROD-001", tw_Cena...     │
└───────────────────────────────────────────────────────────────────┘
```

---

## Uwagi

1. **tw_Pole8** = varchar(50) - wystarczy dla SKU
2. Istniejące produkty w Subiekt BEZ tw_Pole8 → traktowane jako zwykłe produkty
3. Konwencja SKU jako fallback gdy Pole8 puste
4. Mapowanie price_group_id (PPM) → priceLevel (Subiekt) wymaga tabeli mapowania
5. Mapowanie warehouse_id (PPM) → warehouse_id (Subiekt) wymaga konfiguracji
