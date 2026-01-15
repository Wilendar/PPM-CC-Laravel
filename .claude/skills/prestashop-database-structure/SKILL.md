---
name: "prestashop-database-structure"
description: "Reference skill for PrestaShop database structure, table schemas, ObjectModel patterns, and query optimization. Use when working with PrestaShop database tables, migrations, or raw SQL queries."
---

# PrestaShop Database Structure Skill

## 🎯 Overview

Ten skill dostarcza comprehensive reference dla struktury bazy danych PrestaShop 8.x/9.x, including:

- **Table naming conventions** (prefixes, suffixes)
- **Multilang pattern** (_lang tables)
- **Multistore pattern** (_shop tables)
- **ObjectModel ORM** usage
- **Common query patterns**
- **Index optimization**
- **Foreign key relationships**
- **Best practices** i **pitfalls**

**Główna dokumentacja:** [_DOCS/PRESTASHOP_DATABASE_STRUCTURE.md](../../../_DOCS/PRESTASHOP_DATABASE_STRUCTURE.md)

---

## 🚀 Kiedy używać tego Skilla

Użyj `prestashop-database-structure` gdy:

- ✅ **Projektujesz integrację** z bazą danych PrestaShop
- ✅ **Piszesz migracje** dla danych PrestaShop
- ✅ **Tworzysz raw SQL queries** dla tabel PrestaShop
- ✅ **Debugujesz problemy** z relacjami między tabelami
- ✅ **Optymalizujesz queries** dla PrestaShop
- ✅ **Implementujesz synchronizację** danych do/z PrestaShop
- ✅ **Analizujesz strukturę** istniejącej bazy PrestaShop
- ✅ **Tworzysz modele** w Laravel dla tabel PrestaShop

---

## 📋 Quick Reference

### Kluczowe Koncepcje

#### 1. Table Prefixes
```
ps_* - Default prefix (customizable per installation)
```

**ZAWSZE sprawdź aktualny prefix w:**
- PrestaShop: `app/config/parameters.php` → `database_prefix`
- PPM: `config/prestashop.php` → `'table_prefix' => env('PRESTASHOP_TABLE_PREFIX', 'ps_')`

#### 2. Multilang Pattern (_lang suffix)

**Schema:**
```
ps_product (main table)
  ├─ id_product (PK)
  ├─ price, active, etc.

ps_product_lang (translations)
  ├─ id_product (FK → ps_product)
  ├─ id_shop (FK → ps_shop)
  ├─ id_lang (FK → ps_lang)
  ├─ name, description, etc.
  └─ PRIMARY KEY (id_product, id_shop, id_lang)
```

**Query Pattern:**
```sql
SELECT p.*, pl.name, pl.description
FROM ps_product p
INNER JOIN ps_product_lang pl
  ON p.id_product = pl.id_product
  AND pl.id_shop = 1
  AND pl.id_lang = 1
WHERE p.active = 1 AND p.deleted = 0;
```

#### 3. Multistore Pattern (_shop suffix)

**Schema:**
```
ps_product (main table)
  ├─ id_product (PK)

ps_product_shop (per-shop data)
  ├─ id_product (FK → ps_product)
  ├─ id_shop (FK → ps_shop)
  ├─ price, active, visibility, etc.
  └─ PRIMARY KEY (id_product, id_shop)
```

**Query Pattern:**
```sql
SELECT p.*, ps.price, ps.active
FROM ps_product p
INNER JOIN ps_product_shop ps
  ON p.id_product = ps.id_product
  AND ps.id_shop = 1
WHERE ps.active = 1 AND p.deleted = 0;
```

#### 4. ObjectModel Pattern

**PHP Example:**
```php
use PrestaShop\PrestaShop\Adapter\Entity\Product;

class Product extends ObjectModel
{
    public static $definition = [
        'table' => 'product',
        'primary' => 'id_product',
        'multilang' => true,
        'multishop' => true,
        'fields' => [
            'id_manufacturer' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId'
            ],
            'reference' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isReference',
                'size' => 64
            ],
            'price' => [
                'type' => self::TYPE_FLOAT,
                'validate' => 'isPrice',
                'required' => true
            ],
            // Multilang fields
            'name' => [
                'type' => self::TYPE_STRING,
                'lang' => true,
                'validate' => 'isGenericName',
                'required' => true,
                'size' => 255
            ]
        ]
    ];
}
```

---

## 📖 Core Tables Reference

### Products
| Table | Purpose | Key Fields |
|-------|---------|------------|
| `ps_product` | Main product data | id_product, reference, price, active, deleted |
| `ps_product_lang` | Product translations | name, description, link_rewrite |
| `ps_product_shop` | Per-shop product data | price, active, visibility |
| `ps_product_attribute` | Product combinations/variants | reference, ean13, price impact |

### Categories
| Table | Purpose | Key Fields |
|-------|---------|------------|
| `ps_category` | Main category data | id_category, id_parent, nleft, nright, active, deleted |
| `ps_category_lang` | Category translations | name, description, link_rewrite |
| `ps_category_shop` | Per-shop category data | position, active |
| `ps_category_product` | Product-category associations | id_category, id_product, position |

### Stock
| Table | Purpose | Key Fields |
|-------|---------|------------|
| `ps_stock_available` | Stock quantities | id_product, id_product_attribute, id_shop, quantity |

### Manufacturers & Suppliers
| Table | Purpose | Key Fields |
|-------|---------|------------|
| `ps_manufacturer` | Manufacturers | id_manufacturer, name, active |
| `ps_supplier` | Suppliers | id_supplier, name, active |
| `ps_product_supplier` | Product-supplier link | id_product, id_product_attribute, id_supplier, product_supplier_reference |

### Attributes (Combinations)
| Table | Purpose | Key Fields |
|-------|---------|------------|
| `ps_attribute_group` | Attribute groups (Color, Size) | id_attribute_group, name, group_type |
| `ps_attribute` | Attribute values (Red, XL) | id_attribute, id_attribute_group, color |
| `ps_product_attribute` | Product combinations | id_product_attribute, id_product, reference, ean13 |
| `ps_product_attribute_combination` | Combination-attribute link | id_product_attribute, id_attribute |

**Full Reference:** Zobacz [_DOCS/PRESTASHOP_DATABASE_STRUCTURE.md](../../../_DOCS/PRESTASHOP_DATABASE_STRUCTURE.md) dla wszystkich 50+ tabel.

---

## 🔧 Common Operations

### 1. Pobranie Produktu z Multilang

**Scenario:** Pobierz produkt ze wszystkimi językami

```sql
-- Option 1: Wszystkie języki jako rows
SELECT
    p.id_product,
    p.reference,
    p.price,
    pl.id_lang,
    pl.name,
    pl.description
FROM ps_product p
INNER JOIN ps_product_shop ps
    ON p.id_product = ps.id_product AND ps.id_shop = 1
INNER JOIN ps_product_lang pl
    ON p.id_product = pl.id_product AND pl.id_shop = 1
WHERE p.id_product = 123;

-- Option 2: GROUP_CONCAT dla agregacji języków
SELECT
    p.id_product,
    p.reference,
    p.price,
    GROUP_CONCAT(CONCAT(pl.id_lang, ':', pl.name) SEPARATOR '|') as names
FROM ps_product p
INNER JOIN ps_product_shop ps
    ON p.id_product = ps.id_product AND ps.id_shop = 1
INNER JOIN ps_product_lang pl
    ON p.id_product = pl.id_product AND pl.id_shop = 1
WHERE p.id_product = 123
GROUP BY p.id_product;
```

### 2. Sprawdzenie Istnienia Produktu po SKU

**Scenario:** Verify czy produkt z danym SKU istnieje

```sql
SELECT
    p.id_product,
    p.reference,
    p.active,
    p.deleted,
    ps.price
FROM ps_product p
INNER JOIN ps_product_shop ps
    ON p.id_product = ps.id_product AND ps.id_shop = 1
WHERE p.reference = 'ABC-123'
AND p.deleted = 0
LIMIT 1;
```

**Laravel Eloquent Equivalent:**
```php
use Illuminate\Support\Facades\DB;

$product = DB::connection('prestashop')
    ->table('ps_product as p')
    ->join('ps_product_shop as ps', function ($join) {
        $join->on('p.id_product', '=', 'ps.id_product')
             ->where('ps.id_shop', 1);
    })
    ->where('p.reference', 'ABC-123')
    ->where('p.deleted', 0)
    ->select('p.id_product', 'p.reference', 'p.active', 'ps.price')
    ->first();
```

### 3. Update Stock dla Produktu

**Scenario:** Zaktualizuj stock dla produktu/kombinacji

```sql
-- Main product (no combination)
UPDATE ps_stock_available
SET quantity = 50, date_upd = NOW()
WHERE id_product = 123
  AND id_product_attribute = 0
  AND id_shop = 1;

-- Specific combination
UPDATE ps_stock_available
SET quantity = 25, date_upd = NOW()
WHERE id_product = 123
  AND id_product_attribute = 456
  AND id_shop = 1;
```

**⚠️ UWAGA:** `ps_stock_available` jest MANAGED przez PrestaShop - preferuj update przez API zamiast direct SQL!

### 4. Pobranie Kategorii z Hierarchią

**Scenario:** Pobierz kategorię wraz z full path (breadcrumb)

```sql
-- Nested Set Model query
SELECT
    c1.id_category,
    c1.id_parent,
    c1.nleft,
    c1.nright,
    cl1.name,
    GROUP_CONCAT(cl2.name ORDER BY c2.nleft SEPARATOR ' > ') as full_path
FROM ps_category c1
INNER JOIN ps_category_lang cl1
    ON c1.id_category = cl1.id_category AND cl1.id_lang = 1
INNER JOIN ps_category c2
    ON c1.nleft BETWEEN c2.nleft AND c2.nright
INNER JOIN ps_category_lang cl2
    ON c2.id_category = cl2.id_category AND cl2.id_lang = 1
WHERE c1.id_category = 5
GROUP BY c1.id_category;
```

### 5. Synchronizacja z Laravel Model

**Scenario:** Sync PrestaShop product do PPM Product model

```php
use App\Models\Product;
use Illuminate\Support\Facades\DB;

public function syncProductFromPrestaShop(int $prestashopProductId): Product
{
    // 1. Fetch from PrestaShop DB
    $psProduct = DB::connection('prestashop')
        ->table('ps_product as p')
        ->join('ps_product_shop as ps', function ($join) {
            $join->on('p.id_product', '=', 'ps.id_product')
                 ->where('ps.id_shop', config('prestashop.shop_id'));
        })
        ->join('ps_product_lang as pl', function ($join) {
            $join->on('p.id_product', '=', 'pl.id_product')
                 ->where('pl.id_shop', config('prestashop.shop_id'))
                 ->where('pl.id_lang', config('prestashop.default_lang_id'));
        })
        ->leftJoin('ps_manufacturer as m', 'p.id_manufacturer', '=', 'm.id_manufacturer')
        ->where('p.id_product', $prestashopProductId)
        ->select([
            'p.id_product',
            'p.reference',
            'p.ean13',
            'p.active',
            'p.deleted',
            'ps.price',
            'pl.name',
            'pl.description',
            'm.name as manufacturer_name'
        ])
        ->first();

    if (!$psProduct || $psProduct->deleted) {
        throw new \Exception("PrestaShop product {$prestashopProductId} not found or deleted");
    }

    // 2. Update or create PPM Product
    $product = Product::updateOrCreate(
        ['prestashop_id' => $psProduct->id_product],
        [
            'sku' => $psProduct->reference,
            'name' => $psProduct->name,
            'description' => $psProduct->description,
            'price' => (float) $psProduct->price,
            'active' => (bool) $psProduct->active,
            'manufacturer_name' => $psProduct->manufacturer_name,
            'ean13' => $psProduct->ean13,
            'synced_at' => now()
        ]
    );

    return $product;
}
```

---

## ⚠️ Pułapki i Ostrzeżenia

### 1. NIGDY nie pomijaj id_shop w multilang/multistore queries

```sql
-- ❌ ZŁE: Brak id_shop filter
SELECT p.*, pl.name
FROM ps_product p
JOIN ps_product_lang pl ON p.id_product = pl.id_product
WHERE pl.id_lang = 1;
-- Result: Duplicate rows jeśli multi-shop!

-- ✅ DOBRE: Zawsze filtruj id_shop
SELECT p.*, pl.name
FROM ps_product p
JOIN ps_product_lang pl
    ON p.id_product = pl.id_product
    AND pl.id_shop = 1
WHERE pl.id_lang = 1;
```

### 2. Sprawdzaj ZAWSZE pole 'deleted'

```sql
-- ❌ ZŁE: Brak deleted check
SELECT * FROM ps_product WHERE reference = 'ABC-123';

-- ✅ DOBRE: Filter deleted products
SELECT * FROM ps_product
WHERE reference = 'ABC-123' AND deleted = 0;
```

### 3. Stock NIE modyfikuj bezpośrednio przez SQL

```sql
-- ❌ UNIKAJ: Direct SQL update
UPDATE ps_stock_available SET quantity = 50 WHERE id_product = 123;

-- ✅ PREFERUJ: PrestaShop API
POST /api/stock_availables/456
<stock_available>
  <id>456</id>
  <quantity>50</quantity>
</stock_available>
```

**Dlaczego?** PrestaShop ma hooki, cache invalidation, stock movement logs - SQL bypasses all of that!

### 4. Nested Set Model - NIGDY ręcznie nie edytuj nleft/nright

```sql
-- ❌ NIGDY NIE RÓB TEGO!
UPDATE ps_category SET nleft = 5, nright = 10 WHERE id_category = 123;

-- ✅ Użyj PrestaShop Category::regenerateEntireNtree()
```

**Dlaczego?** Nested Set wymaga rekursywnej rekalibracji całego drzewa!

### 5. Associations używają composite PKs

```sql
-- ❌ ZŁE: Brak wszystkich PK fields
DELETE FROM ps_category_product WHERE id_category = 5;

-- ✅ DOBRE: Specify wszystkie PK components
DELETE FROM ps_category_product
WHERE id_category = 5 AND id_product = 123;
```

---

## 📚 Pełna Dokumentacja

**Main Reference:**
- [_DOCS/PRESTASHOP_DATABASE_STRUCTURE.md](../../../_DOCS/PRESTASHOP_DATABASE_STRUCTURE.md) - Complete table schemas, field descriptions, indexes, foreign keys

**Related Skills:**
- [prestashop-api-integration](./../prestashop-api-integration/SKILL.md) - API workflow skill
- [prestashop-xml-integration](./../prestashop-xml-integration/SKILL.md) - XML format skill

**External Resources:**
- [PrestaShop DevDocs - Database Structure](https://devdocs.prestashop-project.org/8/development/database/)
- [PrestaShop ObjectModel Reference](https://devdocs.prestashop-project.org/8/development/components/database/)

---

## 🔍 How to Use This Skill

### Step 1: Identify Your Need

Określ co chcesz zrobić:
- [ ] Zaprojektować integrację z tabelą PrestaShop
- [ ] Napisać raw SQL query
- [ ] Stworzyć Laravel migration dla danych PrestaShop
- [ ] Zrozumieć strukturę istniejącej tabeli
- [ ] Debugować problem z relacjami
- [ ] Optymalizować query performance

### Step 2: Find Relevant Section

1. **Quick Reference** (powyżej) - Common patterns i koncepcje
2. **Core Tables Reference** (powyżej) - Lista głównych tabel
3. **Common Operations** (powyżej) - Gotowe query examples
4. **Full Documentation** ([_DOCS/PRESTASHOP_DATABASE_STRUCTURE.md](../../../_DOCS/PRESTASHOP_DATABASE_STRUCTURE.md)) - Complete reference

### Step 3: Apply Pattern

Skopiuj relevantny pattern i dostosuj:
- Zamień `ps_` na actual prefix z konfiguracji
- Ustaw proper `id_shop` i `id_lang`
- Dodaj WHERE conditions dla Twojego use case
- Test query na development database FIRST!

### Step 4: Verify & Optimize

```sql
-- Test query
EXPLAIN SELECT ... FROM ps_product ... ;

-- Check indexes used
SHOW INDEX FROM ps_product;

-- Verify result count
SELECT COUNT(*) FROM (...) as subquery;
```

---

## 🎯 Examples

### Example 1: Sync All Products from PrestaShop

**Scenario:** Zaimportuj wszystkie aktywne produkty z PrestaShop do PPM

```php
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class PrestaShopProductImporter
{
    public function importAllProducts(): array
    {
        $imported = 0;
        $errors = [];

        // Fetch all active products
        $psProducts = DB::connection('prestashop')
            ->table('ps_product as p')
            ->join('ps_product_shop as ps', function ($join) {
                $join->on('p.id_product', '=', 'ps.id_product')
                     ->where('ps.id_shop', config('prestashop.shop_id'));
            })
            ->join('ps_product_lang as pl', function ($join) {
                $join->on('p.id_product', '=', 'pl.id_product')
                     ->where('pl.id_shop', config('prestashop.shop_id'))
                     ->where('pl.id_lang', config('prestashop.default_lang_id'));
            })
            ->leftJoin('ps_manufacturer as m', 'p.id_manufacturer', '=', 'm.id_manufacturer')
            ->leftJoin('ps_stock_available as sa', function ($join) {
                $join->on('p.id_product', '=', 'sa.id_product')
                     ->where('sa.id_product_attribute', 0)
                     ->where('sa.id_shop', config('prestashop.shop_id'));
            })
            ->where('p.deleted', 0)
            ->where('ps.active', 1)
            ->select([
                'p.id_product',
                'p.reference',
                'p.ean13',
                'ps.price',
                'pl.name',
                'm.name as manufacturer_name',
                'sa.quantity'
            ])
            ->get();

        foreach ($psProducts as $psProduct) {
            try {
                Product::updateOrCreate(
                    ['prestashop_id' => $psProduct->id_product],
                    [
                        'sku' => $psProduct->reference,
                        'name' => $psProduct->name,
                        'price' => (float) $psProduct->price,
                        'manufacturer_name' => $psProduct->manufacturer_name,
                        'ean13' => $psProduct->ean13,
                        'stock_quantity' => $psProduct->quantity ?? 0,
                        'active' => true,
                        'synced_at' => now()
                    ]
                );

                $imported++;

            } catch (\Exception $e) {
                $errors[] = [
                    'prestashop_id' => $psProduct->id_product,
                    'reference' => $psProduct->reference,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'imported' => $imported,
            'errors' => $errors
        ];
    }
}

// Usage
$importer = new PrestaShopProductImporter();
$result = $importer->importAllProducts();

echo "Imported: {$result['imported']}\n";
echo "Errors: " . count($result['errors']) . "\n";
```

### Example 2: Find Product by Multiple Identifiers

**Scenario:** Znajdź produkt po SKU, EAN13, lub PrestaShop ID

```php
public function findProduct(
    ?string $sku = null,
    ?string $ean13 = null,
    ?int $prestashopId = null
): ?object
{
    $query = DB::connection('prestashop')
        ->table('ps_product as p')
        ->join('ps_product_shop as ps', function ($join) {
            $join->on('p.id_product', '=', 'ps.id_product')
                 ->where('ps.id_shop', config('prestashop.shop_id'));
        })
        ->join('ps_product_lang as pl', function ($join) {
            $join->on('p.id_product', '=', 'pl.id_product')
                 ->where('pl.id_shop', config('prestashop.shop_id'))
                 ->where('pl.id_lang', config('prestashop.default_lang_id'));
        })
        ->where('p.deleted', 0)
        ->select([
            'p.id_product',
            'p.reference',
            'p.ean13',
            'ps.price',
            'ps.active',
            'pl.name'
        ]);

    if ($prestashopId) {
        $query->where('p.id_product', $prestashopId);
    } elseif ($sku) {
        $query->where('p.reference', $sku);
    } elseif ($ean13) {
        $query->where('p.ean13', $ean13);
    } else {
        return null;
    }

    return $query->first();
}

// Usage
$product = $this->findProduct(sku: 'ABC-123');
$product = $this->findProduct(ean13: '5901234123457');
$product = $this->findProduct(prestashopId: 456);
```

### Example 3: Get Category Tree

**Scenario:** Pobierz pełną hierarchię kategorii jako nested array

```php
public function getCategoryTree(?int $parentId = null): array
{
    $categories = DB::connection('prestashop')
        ->table('ps_category as c')
        ->join('ps_category_lang as cl', function ($join) {
            $join->on('c.id_category', '=', 'cl.id_category')
                 ->where('cl.id_shop', config('prestashop.shop_id'))
                 ->where('cl.id_lang', config('prestashop.default_lang_id'));
        })
        ->join('ps_category_shop as cs', function ($join) {
            $join->on('c.id_category', '=', 'cs.id_category')
                 ->where('cs.id_shop', config('prestashop.shop_id'));
        })
        ->where('c.active', 1)
        ->where('c.deleted', 0)
        ->when($parentId, function ($query, $parentId) {
            $query->where('c.id_parent', $parentId);
        }, function ($query) {
            // Root level (exclude Home category id=1,2)
            $query->whereIn('c.id_parent', [1, 2]);
        })
        ->orderBy('cs.position')
        ->select([
            'c.id_category',
            'c.id_parent',
            'c.nleft',
            'c.nright',
            'cl.name',
            'cs.position'
        ])
        ->get();

    $tree = [];

    foreach ($categories as $category) {
        $categoryArray = (array) $category;

        // Recursively get children
        $categoryArray['children'] = $this->getCategoryTree($category->id_category);

        $tree[] = $categoryArray;
    }

    return $tree;
}

// Usage
$tree = $this->getCategoryTree(); // Full tree from root
$subtree = $this->getCategoryTree(5); // Subtree from category 5
```

---

## 🔧 Troubleshooting

### Problem: Duplicate rows w results

**Diagnoza:** Brak `id_shop` filter w multilang/multistore join

**Rozwiązanie:**
```sql
-- Add AND id_shop = 1 to ALL joins:
JOIN ps_product_lang pl
  ON p.id_product = pl.id_product
  AND pl.id_shop = 1  -- ← ADD THIS
  AND pl.id_lang = 1
```

### Problem: Query bardzo wolny (>1s)

**Diagnoza:** Brak indexów, missing WHERE conditions

**Rozwiązanie:**
```sql
-- 1. Check EXPLAIN
EXPLAIN SELECT ... ;

-- 2. Add indexes if needed (but usually PrestaShop has good indexes)
-- 3. Ensure you filter on indexed columns:
WHERE p.deleted = 0      -- indexed
  AND p.active = 1       -- indexed
  AND p.reference = '...' -- indexed
```

### Problem: Empty result mimo że widzę dane w phpMyAdmin

**Diagnoza:**
- Wrong `id_shop` value
- Wrong `id_lang` value
- Product is soft-deleted (`deleted = 1`)

**Rozwiązanie:**
```sql
-- Verify bez filters:
SELECT p.*, pl.*, ps.*
FROM ps_product p
LEFT JOIN ps_product_lang pl ON p.id_product = pl.id_product
LEFT JOIN ps_product_shop ps ON p.id_product = ps.id_product
WHERE p.reference = 'ABC-123';

-- Check actual id_shop and id_lang values present
SELECT DISTINCT id_shop FROM ps_product_lang WHERE id_product = 123;
SELECT DISTINCT id_lang FROM ps_product_lang WHERE id_product = 123;
```

---

## 📊 System Uczenia Się (Automatyczny)

### Tracking Informacji
Ten skill automatycznie zbiera:
- Częstotliwość odwołań do dokumentacji
- Najczęściej używane tabele
- Common error patterns

### Metryki Sukcesu
- **Clarity:** Czy dokumentacja była zrozumiała?
- **Completeness:** Czy znalazłeś potrzebną informację?
- **Time to Solution:** Jak długo zajęło znalezienie odpowiedzi?

### Historia Ulepszeń
<!-- Auto-generated przez skill-creator -->

#### v1.0.0 (2025-11-05)
- [INIT] Początkowa wersja skill
- [ADDED] Quick Reference z core patterns
- [ADDED] Core Tables Reference
- [ADDED] Common Operations examples
- [ADDED] Full integration examples
- [ADDED] Troubleshooting section
- Bazowane na dokumentacji: _DOCS/PRESTASHOP_DATABASE_STRUCTURE.md (Context7 + DevDocs)

---

**Sukcesu z PrestaShop Database! 🗄️**
