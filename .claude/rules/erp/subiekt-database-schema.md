# ERP: Subiekt GT Database Schema (MANDATORY)

## Critical Rule
**BEFORE** creating, configuring, or modifying ANY Subiekt GT API operations, you **MUST** read the database schema documentation!

## MANDATORY Reading

When working with Subiekt GT integration:

```
1. READ: _DOCS/SUBIEKT_GT_DATABASE_SCHEMA.md (human-readable)
2. READ: _DOCS/SUBIEKT_GT_DATABASE_SCHEMA.json (machine-parseable)
```

## When This Rule Applies

**ALWAYS read schema docs when:**
- Creating new API endpoints
- Modifying existing API queries
- Adding new data sync features
- Debugging data mapping issues
- Creating SQL queries for Subiekt GT
- Mapping PPM fields to Subiekt fields
- Implementing price/stock/product sync

## Key Tables Quick Reference

### Products (tw__Towar)
| Column | Type | Description |
|--------|------|-------------|
| `tw_Id` | INT | Primary key |
| `tw_Symbol` | VARCHAR | SKU/Symbol |
| `tw_Nazwa` | VARCHAR | Product name |
| `tw_Opis` | TEXT | Description |
| `tw_Aktywny` | BIT | Is active |
| `tw_EAN` | VARCHAR | EAN barcode |

### Stock (tw_Stan)
| Column | Type | Description |
|--------|------|-------------|
| `st_TowId` | INT | FK to tw__Towar |
| `st_MagId` | INT | FK to sl_Magazyn |
| `st_Stan` | DECIMAL | Quantity in stock |
| `st_StanRez` | DECIMAL | Reserved quantity |

### Prices (tw_Cena)
| Column | Type | Description |
|--------|------|-------------|
| `tc_TowId` | INT | FK to tw__Towar |
| `tc_CenaNetto0..10` | DECIMAL | Net prices (11 price levels) |
| `tc_CenaBrutto0..10` | DECIMAL | Gross prices (11 price levels) |

### Price Level Names (tw_Parametr) - IMPORTANT!
| Column | Type | Description |
|--------|------|-------------|
| `twp_Id` | INT | Primary key (always 1) |
| `twp_NazwaCeny1` | VARCHAR | Name for price level 0 (tc_CenaNetto0) |
| `twp_NazwaCeny2` | VARCHAR | Name for price level 1 (tc_CenaNetto1) |
| `twp_NazwaCeny3..10` | VARCHAR | Names for price levels 2-9 |

**MAPPING:** `twp_NazwaCeny[N]` → `tc_CenaNetto[N-1]` (offset by 1!)

### Warehouses (sl_Magazyn)
| Column | Type | Description |
|--------|------|-------------|
| `mag_Id` | INT | Primary key |
| `mag_Symbol` | VARCHAR | Warehouse code |
| `mag_Nazwa` | VARCHAR | Warehouse name |
| `mag_Aktywny` | BIT | Is active |

### Contractors (kh__Kontrahent)
| Column | Type | Description |
|--------|------|-------------|
| `kh_Id` | INT | Primary key |
| `kh_Symbol` | VARCHAR | Contractor code |
| `kh_Nazwa` | VARCHAR | Company name |
| `kh_Nip` | VARCHAR | Tax ID (NIP) |

## SQL Query Patterns

### Products with Prices and Stock
```sql
SELECT
    t.tw_Id, t.tw_Symbol, t.tw_Nazwa,
    c.tc_CenaNetto0, c.tc_CenaBrutto0,
    ISNULL(s.st_Stan, 0) as stock
FROM tw__Towar t
LEFT JOIN tw_Cena c ON t.tw_Id = c.tc_TowId
LEFT JOIN tw_Stan s ON t.tw_Id = s.st_TowId AND s.st_MagId = 1
WHERE t.tw_Aktywny = 1
```

### Price Types with Names
```sql
SELECT rc_Id as id, rc_Nazwa as name, rc_Opis as description
FROM sl_RodzCeny
WHERE rc_Aktywny = 1
ORDER BY rc_Id
```

### Warehouses
```sql
SELECT mag_Id as id, mag_Symbol as symbol, mag_Nazwa as name
FROM sl_Magazyn
WHERE mag_Aktywny = 1
```

## Critical Warnings

### NEVER Do This
- **NEVER** use `MAX(id)+1` for new IDs - use `spIdentyfikator` stored procedure
- **NEVER** INSERT/UPDATE directly without Sfera API - breaks integrity
- **NEVER** assume column names - always check schema docs

### ALWAYS Do This
- **ALWAYS** check `*_Aktywny` flag for active records
- **ALWAYS** use proper JOINs with correct FK relationships
- **ALWAYS** handle NULL values with ISNULL/COALESCE

## Files Location
- Schema (MD): `_DOCS/SUBIEKT_GT_DATABASE_SCHEMA.md`
- Schema (JSON): `_DOCS/SUBIEKT_GT_DATABASE_SCHEMA.json`
- API Client: `app/Services/ERP/SubiektGT/SubiektRestApiClient.php`
- Service: `app/Services/ERP/SubiektGTService.php`
- Skill: `.claude/skills/subiekt-gt-integration/`

## Verification Checklist

Before implementing Subiekt GT API operations:
- [ ] Read `_DOCS/SUBIEKT_GT_DATABASE_SCHEMA.md`
- [ ] Verify table/column names match schema
- [ ] Check data types match expectations
- [ ] Confirm FK relationships are correct
- [ ] Test query on sample data
