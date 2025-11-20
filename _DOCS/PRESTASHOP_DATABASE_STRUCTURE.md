# PRESTASHOP DATABASE STRUCTURE REFERENCE

**Version:** PrestaShop 8.x / 9.x
**Last Updated:** 2025-11-05
**Source:** DevDocs PrestaShop, Context7 Library, GitHub Repository

---

## 📖 OVERVIEW

PrestaShop używa relacyjnej bazy danych MySQL/MariaDB z **spójnymi konwencjami nazewnictwa** i **wzorcami projektowymi** dla multilang, multistore i associations.

**Key Concepts:**
- **Prefix:** Domyślny `ps_` (konfigurowalny podczas instalacji - **ZALECANE: custom prefix dla bezpieczeństwa**)
- **ObjectModel Pattern:** ORM-like approach dla encji (klasa `ObjectModel`)
- **Multilang Pattern:** Tabele `_lang` dla tłumaczeń
- **Multistore Pattern:** Tabele `_shop` dla danych per-shop
- **Association Pattern:** Tabele łączące z nazwami obu encji
- **Nested Set Model:** Dla hierarchii kategorii (nleft, nright)

**Database Location:**
- Installation schema: `install/data/db_structure.sql` (używany raz podczas instalacji)
- Default data: `install/data/xml/` (jeden plik XML per encja)
- Doctrine entities: `src/PrestaShopBundle/Entity/` (nowoczesne ORM)
- Migration scripts: `upgrade/sql/[version].sql` (np. `8.0.0.sql`, `9.0.0.sql`)

---

## 🔑 TABLE NAMING CONVENTIONS

### Podstawowy Format

```
[prefix]_[entity_name]
```

**Przykłady:**
- `ps_product` - Produkty
- `ps_category` - Kategorie
- `ps_customer` - Klienci
- `ps_manufacturer` - Producenci
- `ps_product_comment` - Komentarze produktów

**Reguły:**
- ✅ **Lowercase** z underscores separating words
- ✅ **Singular form** dla entity name (`product`, nie `products`)
- ✅ **Descriptive compound names** (`product_attribute`, `category_product`)
- ⚠️ **Security:** Nigdy nie używaj domyślnego prefix `ps_` - zmień podczas instalacji!

---

### Special Suffixes

#### 1. `_lang` Suffix - Multilingual Content

Tabele przechowujące tłumaczenia kończą się na `_lang`:

```sql
-- Main table
ps_product (
    id_product INT PRIMARY KEY,
    price DECIMAL(10,2),
    active TINYINT(1),
    date_add DATETIME,
    date_upd DATETIME
)

-- Translation table
ps_product_lang (
    id_product INT,
    id_shop INT,
    id_lang INT,
    name VARCHAR(255),
    description TEXT,
    description_short TEXT,
    link_rewrite VARCHAR(255),
    meta_title VARCHAR(255),
    meta_description VARCHAR(512),
    meta_keywords VARCHAR(255),
    PRIMARY KEY (id_product, id_shop, id_lang),
    FOREIGN KEY (id_product) REFERENCES ps_product(id_product) ON DELETE CASCADE
)
```

**Common `_lang` Tables:**
- `ps_product_lang` - Product names, descriptions
- `ps_category_lang` - Category names, descriptions
- `ps_cms_lang` - CMS page content
- `ps_feature_lang` - Feature names
- `ps_attribute_lang` - Attribute names
- `ps_manufacturer_lang` - Manufacturer descriptions
- `ps_supplier_lang` - Supplier descriptions
- `ps_carrier_lang` - Carrier delay messages

**Pattern:**
- Composite primary key: `(id_entity, id_shop, id_lang)`
- Foreign key to main table with `ON DELETE CASCADE`
- Always includes `id_lang` field (1=English, 2=French, etc.)
- May include `id_shop` for multistore support

---

#### 2. `_shop` Suffix - Multistore Associations

Tabele łączące encje z konkretnymi sklepami:

```sql
ps_category_shop (
    id_category INT,
    id_shop INT,
    position INT,
    PRIMARY KEY (id_category, id_shop),
    FOREIGN KEY (id_category) REFERENCES ps_category(id_category) ON DELETE CASCADE,
    FOREIGN KEY (id_shop) REFERENCES ps_shop(id_shop) ON DELETE CASCADE
)
```

**Common `_shop` Tables:**
- `ps_product_shop` - Product visibility, pricing per shop
- `ps_category_shop` - Category position per shop
- `ps_carrier_shop` - Carrier availability per shop
- `ps_cms_shop` - CMS page associations
- `ps_image_shop` - Image shop associations (cover image per shop)

**Pattern:**
- Composite primary key: `(id_entity, id_shop)`
- Foreign keys to both entity and shop tables with `ON DELETE CASCADE`
- Often includes shop-specific fields: `position`, `active`, `visibility`, etc.

---

#### 3. Association Tables - Many-to-Many

Tabele łączące dwie encje używają nazw obu:

```sql
-- Products ↔ Categories
ps_category_product (
    id_category INT,
    id_product INT,
    position INT,
    PRIMARY KEY (id_category, id_product),
    INDEX (id_product)
)

-- Products ↔ Accessories
ps_accessory (
    id_product_1 INT,
    id_product_2 INT,
    PRIMARY KEY (id_product_1, id_product_2)
)
```

**Common Association Tables:**
- `ps_category_product` - Product category assignments
- `ps_accessory` - Product accessories
- `ps_product_tag` - Product tags
- `ps_feature_product` - Product features
- `ps_image_shop` - Image shop associations
- `ps_customer_group` - Customer group assignments

---

## 🗂️ CORE TABLES REFERENCE

### Products

| Table | Purpose | Key Fields | Primary Key |
|-------|---------|------------|-------------|
| `ps_product` | Main product data | id_product, id_supplier, id_manufacturer, reference, ean13, price, active | id_product |
| `ps_product_lang` | Multilang fields | id_product, id_lang, name, description, description_short, link_rewrite | (id_product, id_shop, id_lang) |
| `ps_product_shop` | Per-shop data | id_product, id_shop, price, active, visibility, id_category_default | (id_product, id_shop) |
| `ps_product_attribute` | Product combinations/variants | id_product_attribute, id_product, reference, ean13, price (differential) | id_product_attribute |
| `ps_product_attribute_combination` | Attribute values for combinations | id_attribute, id_product_attribute | (id_attribute, id_product_attribute) |
| `ps_stock_available` | Stock levels | id_product, id_product_attribute, id_shop, quantity, depends_on_stock | (id_product, id_product_attribute, id_shop, id_shop_group) |
| `ps_category_product` | Category associations | id_category, id_product, position | (id_category, id_product) |
| `ps_image` | Product images | id_image, id_product, position, cover | id_image |
| `ps_specific_price` | Special prices/discounts | id_product, id_shop, price, reduction, from/to dates | id_specific_price |
| `ps_feature_product` | Product features | id_feature, id_product, id_feature_value | (id_feature, id_product, id_feature_value) |

**Product Reference Fields:**
- `reference` - SKU/internal reference (VARCHAR 64)
- `ean13` - EAN13 barcode (VARCHAR 13)
- `isbn` - ISBN code (VARCHAR 32)
- `upc` - UPC barcode (VARCHAR 12)
- `mpn` - Manufacturer Part Number (VARCHAR 40)

---

### Categories

| Table | Purpose | Key Fields | Primary Key |
|-------|---------|------------|-------------|
| `ps_category` | Main category data | id_category, id_parent, level_depth, nleft, nright, active | id_category |
| `ps_category_lang` | Multilang fields | id_category, id_lang, name, description, link_rewrite, meta_title | (id_category, id_shop, id_lang) |
| `ps_category_shop` | Shop associations | id_category, id_shop, position | (id_category, id_shop) |
| `ps_category_product` | Product assignments | id_category, id_product, position | (id_category, id_product) |

**Nested Set Model Fields:**
- `id_parent` - Parent category ID
- `level_depth` - Depth in category tree (0 = root)
- `nleft` - Left boundary for nested set
- `nright` - Right boundary for nested set

**Query all subcategories:**
```sql
SELECT c2.*
FROM ps_category c1
INNER JOIN ps_category c2 ON c2.nleft >= c1.nleft AND c2.nright <= c1.nright
WHERE c1.id_category = 2;  -- All children of category 2
```

---

### Customers

| Table | Purpose | Key Fields | Primary Key |
|-------|---------|------------|-------------|
| `ps_customer` | Customer accounts | id_customer, email, passwd, firstname, lastname, active, deleted | id_customer |
| `ps_address` | Customer addresses | id_address, id_customer, alias, address1, city, postcode, id_country | id_address |
| `ps_customer_group` | Group assignments | id_customer, id_group | (id_customer, id_group) |
| `ps_customer_thread` | Support tickets | id_customer_thread, id_customer, email, status, id_order | id_customer_thread |

---

### Orders

| Table | Purpose | Key Fields | Primary Key |
|-------|---------|------------|-------------|
| `ps_orders` | Main order data | id_order, reference, id_customer, total_paid, current_state, id_cart | id_order |
| `ps_order_detail` | Order line items | id_order_detail, id_order, product_id, product_quantity, unit_price_tax_incl | id_order_detail |
| `ps_order_state` | Order statuses | id_order_state, color, paid, shipped, delivered | id_order_state |
| `ps_order_state_lang` | Status translations | id_order_state, id_lang, name | (id_order_state, id_lang) |
| `ps_order_history` | State changes | id_order_history, id_order, id_order_state, date_add | id_order_history |
| `ps_order_payment` | Payment records | id_order_payment, order_reference, amount, payment_method, date_add | id_order_payment |
| `ps_cart` | Shopping carts | id_cart, id_customer, date_add, date_upd, checkout_session_data | id_cart |

---

### Manufacturers & Suppliers

| Table | Purpose | Key Fields | Primary Key |
|-------|---------|------------|-------------|
| `ps_manufacturer` | Manufacturers | id_manufacturer, name, active, date_add | id_manufacturer |
| `ps_manufacturer_lang` | Multilang fields | id_manufacturer, id_lang, description, meta_title, meta_description | (id_manufacturer, id_lang) |
| `ps_supplier` | Suppliers | id_supplier, name, active | id_supplier |
| `ps_supplier_lang` | Multilang fields | id_supplier, id_lang, description, meta_title | (id_supplier, id_lang) |

---

### Attributes (Variants/Combinations)

| Table | Purpose | Key Fields | Primary Key |
|-------|---------|------------|-------------|
| `ps_attribute_group` | Attribute types (Color, Size) | id_attribute_group, group_type, position | id_attribute_group |
| `ps_attribute_group_lang` | Type translations | id_attribute_group, id_lang, name, public_name | (id_attribute_group, id_lang) |
| `ps_attribute` | Attribute values (Red, XL) | id_attribute, id_attribute_group, color (hex), position | id_attribute |
| `ps_attribute_lang` | Value translations | id_attribute, id_lang, name | (id_attribute, id_lang) |
| `ps_product_attribute` | Product combinations | id_product_attribute, id_product, reference, price, ean13 | id_product_attribute |
| `ps_product_attribute_combination` | Combination details | id_attribute, id_product_attribute | (id_attribute, id_product_attribute) |

**Combination Example:**
Product "T-Shirt" ma 2 atrybuty:
- Color: Red (id_attribute=1), Blue (id_attribute=2)
- Size: S (id_attribute=10), M (id_attribute=11)

Kombinacje w `ps_product_attribute_combination`:
- T-Shirt Red S: id_product_attribute=100 → (id_attribute=1, id_product_attribute=100), (id_attribute=10, id_product_attribute=100)
- T-Shirt Red M: id_product_attribute=101 → (id_attribute=1, id_product_attribute=101), (id_attribute=11, id_product_attribute=101)
- T-Shirt Blue S: id_product_attribute=102 → (id_attribute=2, id_product_attribute=102), (id_attribute=10, id_product_attribute=102)

---

### Features (Product Properties)

| Table | Purpose | Key Fields | Primary Key |
|-------|---------|------------|-------------|
| `ps_feature` | Feature types (Weight, Material) | id_feature, position | id_feature |
| `ps_feature_lang` | Type translations | id_feature, id_lang, name | (id_feature, id_lang) |
| `ps_feature_value` | Feature values (100g, Cotton) | id_feature_value, id_feature, custom | id_feature_value |
| `ps_feature_value_lang` | Value translations | id_feature_value, id_lang, value | (id_feature_value, id_lang) |
| `ps_feature_product` | Product assignments | id_feature, id_product, id_feature_value | (id_feature, id_product, id_feature_value) |

**Features vs Attributes:**
- **Attributes** = Combinations (affect SKU, price, stock) - Color, Size
- **Features** = Properties (no combinations, informational) - Weight, Material, Warranty

---

### Shops (Multistore)

| Table | Purpose | Key Fields | Primary Key |
|-------|---------|------------|-------------|
| `ps_shop` | Shop instances | id_shop, id_shop_group, name, active, id_category, theme_name | id_shop |
| `ps_shop_group` | Shop groups | id_shop_group, name, share_customer, share_order, share_stock | id_shop_group |
| `ps_shop_url` | Shop URLs | id_shop_url, id_shop, domain, domain_ssl, physical_uri, virtual_uri | id_shop_url |

**Multistore Sharing Options (ps_shop_group):**
- `share_customer` - Share customers between shops
- `share_order` - Share orders between shops
- `share_stock` - Share stock between shops

---

### Configuration

| Table | Purpose | Key Fields | Primary Key |
|-------|---------|------------|-------------|
| `ps_configuration` | System settings | id_configuration, name, value, id_shop, id_shop_group, date_add | id_configuration |
| `ps_configuration_lang` | Multilang settings | id_configuration, id_lang, value, date_upd | (id_configuration, id_lang) |

**Query Configuration:**
```sql
-- Get global configuration
SELECT value FROM ps_configuration
WHERE name = 'PS_SHOP_EMAIL' AND id_shop IS NULL AND id_shop_group IS NULL;

-- Get shop-specific configuration
SELECT value FROM ps_configuration
WHERE name = 'PS_SHOP_EMAIL' AND id_shop = 1;
```

---

### Images

| Table | Purpose | Key Fields | Primary Key |
|-------|---------|------------|-------------|
| `ps_image` | Product images | id_image, id_product, position, cover | id_image |
| `ps_image_lang` | Image alt texts | id_image, id_lang, legend | (id_image, id_lang) |
| `ps_image_shop` | Shop associations | id_image, id_shop, cover | (id_image, id_shop) |
| `ps_image_type` | Image sizes | id_image_type, name, width, height, products, categories, manufacturers | id_image_type |

**Cover Image Pattern:**
- Main table `ps_image.cover` = global default cover
- Shop table `ps_image_shop.cover` = per-shop cover (can override global)

---

## 🔐 STANDARD FIELD CONVENTIONS

### Common Field Names

| Field | Type | Purpose | Default | Nullable |
|-------|------|---------|---------|----------|
| `id_[entity]` | INT UNSIGNED AUTO_INCREMENT | Primary key | - | NO |
| `id_lang` | INT UNSIGNED | Language ID (1=EN, 2=FR) | 1 | NO |
| `id_shop` | INT UNSIGNED | Shop ID for multistore | 1 | NO |
| `id_shop_group` | INT UNSIGNED | Shop group ID | NULL | YES |
| `active` | TINYINT(1) | Enabled/disabled (0 or 1) | 0 | NO |
| `deleted` | TINYINT(1) | Soft delete flag (0 or 1) | 0 | NO |
| `position` | INT UNSIGNED | Display order | 0 | NO |
| `date_add` | DATETIME | Creation timestamp | NOW() | NO |
| `date_upd` | DATETIME | Last update timestamp | NOW() | NO |

**Important:**
- `active` = 0 (disabled), 1 (enabled) - ALWAYS check in queries!
- `deleted` = 0 (visible), 1 (soft deleted) - Some tables use soft deletes instead of hard DELETE
- Timestamps are **DATETIME**, not TIMESTAMP (no automatic updates!)

---

### Composite Primary Keys

**Multilang Tables:**
```sql
PRIMARY KEY (id_entity, id_shop, id_lang)
```

**Shop Association Tables:**
```sql
PRIMARY KEY (id_entity, id_shop)
```

**Many-to-Many Tables:**
```sql
PRIMARY KEY (id_entity1, id_entity2)
```

**With Position (ordered associations):**
```sql
PRIMARY KEY (id_feature, id_product, id_feature_value)
```

---

## 🔗 FOREIGN KEY PATTERNS

PrestaShop stosuje foreign keys z konwencją nazewnictwa:

```sql
CONSTRAINT fk_[source_table]_[target_table]
    FOREIGN KEY (id_target_entity)
    REFERENCES ps_target_table(id_target_entity)
    ON DELETE CASCADE|SET NULL|RESTRICT
    ON UPDATE RESTRICT
```

**Przykład:**
```sql
ALTER TABLE ps_product
    ADD CONSTRAINT fk_product_manufacturer
    FOREIGN KEY (id_manufacturer)
    REFERENCES ps_manufacturer(id_manufacturer)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT;
```

**Common DELETE behaviors:**
- `CASCADE` - Usuń powiązane rekordy (używane dla `_lang`, `_shop`, `_combination`)
- `RESTRICT` - Blokuj usunięcie jeśli są powiązania (main entities)
- `SET NULL` - Ustaw NULL przy usunięciu parent (optional relationships)

**Standard Pattern:**
- Main entity → child entities: `ON DELETE CASCADE` (lang, shop, images)
- Cross-entity references: `ON DELETE RESTRICT` (manufacturer, supplier, category)
- Optional references: `ON DELETE SET NULL` (id_supplier in ps_product)

---

## 📊 INDEXES

PrestaShop używa indexes dla performance optimization:

**Types of Indexes:**

1. **PRIMARY KEY** - Unique identifier (always indexed)
   ```sql
   PRIMARY KEY (id_product)
   ```

2. **UNIQUE** - Enforce uniqueness
   ```sql
   UNIQUE KEY email_unique (email)
   UNIQUE KEY reference_unique (reference)
   ```

3. **INDEX** - Speed up lookups (non-unique)
   ```sql
   INDEX id_category (id_category)
   INDEX active (active)
   INDEX id_manufacturer (id_manufacturer)
   ```

4. **FULLTEXT** - Text search (MySQL 5.6+)
   ```sql
   FULLTEXT KEY product_search (name, description)
   ```

5. **COMPOSITE INDEX** - Multiple columns
   ```sql
   INDEX product_shop (id_product, id_shop)
   INDEX active_deleted (active, deleted)
   ```

**Common Indexed Fields:**
- **Foreign keys:** `id_category`, `id_manufacturer`, `id_supplier`, `id_customer`
- **Status fields:** `active`, `deleted`
- **Reference fields:** `reference`, `ean13`, `upc`, `email`
- **Dates:** `date_add`, `date_upd`
- **Multistore:** `id_shop`, `id_shop_group`

**Performance Tips:**
- ✅ Index columns used in WHERE clauses
- ✅ Index columns used in JOIN conditions
- ✅ Index columns used in ORDER BY
- ❌ Don't over-index (slows down INSERT/UPDATE)
- ❌ Avoid indexing low-cardinality columns (active, deleted) unless combined

---

## 🔄 OBJECTMODEL PATTERN

PrestaShop używa `ObjectModel` class jako ORM layer:

**ObjectModel Definition:**
```php
<?php
class Product extends ObjectModel
{
    public $id_product;
    public $id_supplier;
    public $id_manufacturer;
    public $reference;
    public $ean13;
    public $price;
    public $active;

    public static $definition = [
        'table' => 'product',
        'primary' => 'id_product',
        'multilang' => true,
        'multishop' => true,
        'fields' => [
            'id_supplier' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_manufacturer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'reference' => ['type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => 64],
            'ean13' => ['type' => self::TYPE_STRING, 'validate' => 'isEan13', 'size' => 13],
            'price' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],

            // Multilang fields
            'name' => [
                'type' => self::TYPE_STRING,
                'lang' => true,
                'validate' => 'isGenericName',
                'required' => true,
                'size' => 255
            ],
            'description' => [
                'type' => self::TYPE_HTML,
                'lang' => true,
                'validate' => 'isCleanHtml'
            ],
        ]
    ];
}
```

**Usage:**
```php
// Create
$product = new Product();
$product->reference = 'ABC-123';
$product->price = 99.99;
$product->name = ['1' => 'Product Name EN', '2' => 'Nom du Produit FR'];
$product->save();

// Read
$product = new Product(123);  // Load by ID
echo $product->reference;

// Update
$product->price = 129.99;
$product->update();

// Delete
$product->delete();
```

---

## 🗺️ COMMON QUERIES REFERENCE

### Get Product with All Data

```sql
SELECT
    p.id_product,
    p.reference,
    p.ean13,
    p.price AS base_price,
    ps.price AS shop_price,
    ps.active,
    pl.name,
    pl.description,
    sa.quantity AS stock,
    m.name AS manufacturer_name,
    GROUP_CONCAT(DISTINCT c.id_category) AS categories
FROM ps_product p
INNER JOIN ps_product_shop ps
    ON p.id_product = ps.id_product AND ps.id_shop = 1
INNER JOIN ps_product_lang pl
    ON p.id_product = pl.id_product AND pl.id_lang = 1 AND pl.id_shop = 1
LEFT JOIN ps_stock_available sa
    ON p.id_product = sa.id_product
    AND sa.id_product_attribute = 0
    AND sa.id_shop = 1
LEFT JOIN ps_manufacturer m
    ON p.id_manufacturer = m.id_manufacturer
LEFT JOIN ps_category_product cp
    ON p.id_product = cp.id_product
LEFT JOIN ps_category c
    ON cp.id_category = c.id_category AND c.active = 1
WHERE ps.active = 1 AND p.deleted = 0
GROUP BY p.id_product;
```

### Get Product Combinations with Attributes

```sql
SELECT
    pa.id_product_attribute,
    pa.reference AS combination_reference,
    pa.ean13 AS combination_ean,
    pa.price AS price_impact,
    GROUP_CONCAT(
        CONCAT(agl.public_name, ': ', al.name)
        ORDER BY ag.position
        SEPARATOR ' - '
    ) AS attributes,
    sa.quantity AS stock
FROM ps_product_attribute pa
INNER JOIN ps_product_attribute_combination pac
    ON pa.id_product_attribute = pac.id_product_attribute
INNER JOIN ps_attribute a
    ON pac.id_attribute = a.id_attribute
INNER JOIN ps_attribute_lang al
    ON a.id_attribute = al.id_attribute AND al.id_lang = 1
INNER JOIN ps_attribute_group ag
    ON a.id_attribute_group = ag.id_attribute_group
INNER JOIN ps_attribute_group_lang agl
    ON ag.id_attribute_group = agl.id_attribute_group AND agl.id_lang = 1
LEFT JOIN ps_stock_available sa
    ON pa.id_product_attribute = sa.id_product_attribute
    AND sa.id_shop = 1
WHERE pa.id_product = 123
GROUP BY pa.id_product_attribute
ORDER BY pa.id_product_attribute;
```

### Get Category Tree with Product Counts

```sql
SELECT
    c.id_category,
    c.id_parent,
    c.level_depth,
    c.nleft,
    c.nright,
    cl.name,
    cs.position,
    COUNT(DISTINCT cp.id_product) AS product_count
FROM ps_category c
INNER JOIN ps_category_lang cl
    ON c.id_category = cl.id_category AND cl.id_lang = 1 AND cl.id_shop = 1
INNER JOIN ps_category_shop cs
    ON c.id_category = cs.id_category AND cs.id_shop = 1
LEFT JOIN ps_category_product cp
    ON c.id_category = cp.id_category
LEFT JOIN ps_product p
    ON cp.id_product = p.id_product AND p.active = 1 AND p.deleted = 0
WHERE c.active = 1
GROUP BY c.id_category
ORDER BY c.nleft;
```

---

## ⚠️ COMMON PITFALLS

### 1. Forgetting Multistore Context

❌ **Wrong:**
```sql
SELECT * FROM ps_product WHERE active = 1;
```

✅ **Correct:**
```sql
SELECT p.*, ps.price, ps.active
FROM ps_product p
INNER JOIN ps_product_shop ps
    ON p.id_product = ps.id_product
WHERE ps.active = 1
  AND ps.id_shop = 1  -- CRITICAL!
  AND p.deleted = 0;
```

### 2. Missing Language Join

❌ **Wrong:**
```sql
SELECT id_product, name FROM ps_product;  -- name doesn't exist!
```

✅ **Correct:**
```sql
SELECT p.id_product, pl.name, pl.description
FROM ps_product p
INNER JOIN ps_product_lang pl
    ON p.id_product = pl.id_product
WHERE pl.id_lang = 1   -- CRITICAL!
  AND pl.id_shop = 1;  -- CRITICAL for multistore!
```

### 3. Ignoring Soft Deletes

❌ **Wrong:**
```sql
SELECT * FROM ps_product;
```

✅ **Correct:**
```sql
SELECT * FROM ps_product WHERE deleted = 0;  -- Exclude soft-deleted
```

### 4. Not Using Indexes Properly

❌ **Slow (full table scan):**
```sql
SELECT * FROM ps_product WHERE reference LIKE '%ABC%';
```

✅ **Fast (uses index):**
```sql
SELECT * FROM ps_product WHERE reference = 'ABC-123';
-- OR
SELECT * FROM ps_product WHERE reference LIKE 'ABC%';  -- Prefix search uses index
```

### 5. Missing Stock Join for Combinations

❌ **Wrong (gets simple product stock only):**
```sql
SELECT p.*, sa.quantity
FROM ps_product p
LEFT JOIN ps_stock_available sa ON p.id_product = sa.id_product;
```

✅ **Correct (gets both simple and combination stock):**
```sql
SELECT p.*, pa.id_product_attribute, sa.quantity
FROM ps_product p
LEFT JOIN ps_product_attribute pa ON p.id_product = pa.id_product
LEFT JOIN ps_stock_available sa
    ON p.id_product = sa.id_product
    AND (sa.id_product_attribute = IFNULL(pa.id_product_attribute, 0))
    AND sa.id_shop = 1;
```

---

## 🔧 BEST PRACTICES

### 1. Always Use Table Prefix Variable

```php
// ✅ CORRECT
$prefix = _DB_PREFIX_;
$sql = "SELECT * FROM {$prefix}product WHERE id_product = 1";

// ❌ WRONG (hardcoded prefix)
$sql = "SELECT * FROM ps_product WHERE id_product = 1";
```

### 2. Use ObjectModel When Possible

```php
// ✅ CORRECT (uses ObjectModel)
$product = new Product(123);
$product->price = 99.99;
$product->update();

// ⚠️ ACCEPTABLE (raw SQL with Db wrapper)
Db::getInstance()->update('product',
    ['price' => 99.99],
    'id_product = 123'
);

// ❌ WRONG (direct MySQL query)
mysql_query("UPDATE ps_product SET price = 99.99 WHERE id_product = 123");
```

### 3. Consider Multistore Context

```php
// ✅ CORRECT
$shopId = Context::getContext()->shop->id;
$langId = Context::getContext()->language->id;

$sql = "
    SELECT p.*, pl.name, ps.price
    FROM `"._DB_PREFIX_."product` p
    INNER JOIN `"._DB_PREFIX_."product_lang` pl
        ON p.id_product = pl.id_product
        AND pl.id_lang = ".(int)$langId."
        AND pl.id_shop = ".(int)$shopId."
    INNER JOIN `"._DB_PREFIX_."product_shop` ps
        ON p.id_product = ps.id_product
        AND ps.id_shop = ".(int)$shopId."
    WHERE ps.active = 1 AND p.deleted = 0
";
```

### 4. Use Db Component for Queries

```php
// ✅ CORRECT
$result = Db::getInstance()->executeS('
    SELECT * FROM `'._DB_PREFIX_.'product`
    WHERE id_product = '.(int)$productId.'
      AND deleted = 0
');

// ✅ CORRECT (with _PS_USE_SQL_SLAVE_)
$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);  // Read-only query

// ✅ CORRECT (DbQuery builder)
$query = new DbQuery();
$query->select('*');
$query->from('product', 'p');
$query->where('p.id_product = '.(int)$productId);
$query->where('p.deleted = 0');
$result = Db::getInstance()->executeS($query);
```

### 5. Optimize Joins

```sql
-- ✅ CORRECT: Use INNER JOIN when relationship is required
SELECT p.*, pl.name
FROM ps_product p
INNER JOIN ps_product_lang pl ON p.id_product = pl.id_product  -- Required
WHERE pl.id_lang = 1;

-- ✅ CORRECT: Use LEFT JOIN when relationship is optional
SELECT p.*, m.name AS manufacturer_name
FROM ps_product p
LEFT JOIN ps_manufacturer m ON p.id_manufacturer = m.id_manufacturer  -- Optional
WHERE p.active = 1;
```

### 6. Index Foreign Keys

```sql
-- ✅ ALWAYS index foreign key columns
ALTER TABLE ps_product ADD INDEX idx_manufacturer (id_manufacturer);
ALTER TABLE ps_product ADD INDEX idx_supplier (id_supplier);
ALTER TABLE ps_category_product ADD INDEX idx_product (id_product);
```

---

## 📚 RESOURCES

- **Installation Schema:** `install/data/db_structure.sql`
- **Default Data:** `install/data/xml/[entity].xml`
- **Doctrine Entities:** `src/PrestaShopBundle/Entity/`
- **Migration Scripts:** `upgrade/sql/[version].sql`
- **ObjectModel:** `classes/ObjectModel.php`
- **Db Component:** `classes/db/Db.php`
- **DbQuery Builder:** `classes/db/DbQuery.php`

**Online Resources:**
- **DevDocs:** https://devdocs.prestashop-project.org/8/development/database/
- **GitHub:** https://github.com/PrestaShop/PrestaShop
- **Context7 Library:** `/prestashop/docs` (Trust Score: 8.2)

---

**Last Updated:** 2025-11-05
**PrestaShop Version:** 8.x / 9.x
**Documentation Status:** ✅ Comprehensive reference for PPM integration based on official sources
