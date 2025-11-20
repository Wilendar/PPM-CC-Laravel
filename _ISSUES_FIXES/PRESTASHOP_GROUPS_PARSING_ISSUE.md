# PrestaShop Customer Groups API Parsing Issue

**Date**: 2025-11-13
**Severity**: HIGH (Blocks shop configuration)
**Status**: ✅ RESOLVED
**Bug ID**: BUG11B

## Problem Summary

błąd parsowania odpowiedzi PrestaShop API dla endpoint `/api/groups?display=full` powodujący `Undefined array key "group"` w kroku 4 kreatora dodawania sklepu (mapowanie grup cenowych).

## Error Details

**Error Message:**
```
Błąd pobierania grup cenowych: Undefined array key "group"
```

**Location:**
`app/Http/Livewire/Admin/Shops/AddShop.php:500`

**Production Logs:**
```
[2025-11-13 09:11:29] production.INFO: PrestaShop API Request {
    "method":"GET",
    "url":"https://dev.mpptrade.pl/api/groups?display=full",
    "status_code":200,
    "response_size_bytes":5214
}
[2025-11-13 09:11:29] production.ERROR: Failed to fetch PrestaShop price groups {
    "shop_url":"https://dev.mpptrade.pl/",
    "error":"Undefined array key \"group\""
}
```

## Root Cause Analysis

### Original Problematic Code

```php
foreach ($groups as $group) {
    // Extract group data
    $groupData = is_array($group['group']) ? $group['group'] : $group;
    // ❌ PROBLEM: Accesses $group['group'] WITHOUT checking if 'group' key exists
    // ❌ This causes "Undefined array key" when PrestaShop returns DIRECT structure
}
```

### Why It Failed

**ASSUMPTION**: Kod zakładał **WRAPPED structure** (XML style):
```json
{
    "groups": [
        {
            "group": {
                "id": 1,
                "name": "Guest",
                "price_display_method": 0,
                "reduction": 0
            }
        }
    ]
}
```

**REALITY**: PrestaShop API z `Output-Format: JSON` zwraca **DIRECT structure**:
```json
{
    "groups": [
        {
            "id": 1,
            "name": "Guest",
            "price_display_method": 0,
            "reduction": 0
        },
        {
            "id": 2,
            "name": "Customer",
            "price_display_method": 0,
            "reduction": 0
        }
    ]
}
```

**Key Difference:**
- XML parsing może zwracać `['group' => [...]]` (wrapper key)
- JSON response zwraca bezpośrednio dane bez wrappera
- Kod sprawdzał `is_array($group['group'])` PRZED `isset($group['group'])`
- PHP 8.x zgłasza "Undefined array key" warning jako error

## Solution Implemented

### Defensive Parsing Pattern

```php
foreach ($groups as $group) {
    // DEFENSIVE PARSING: Support both wrapped and direct structures
    // Check if 'group' key exists BEFORE accessing it (prevents "Undefined array key")
    if (isset($group['group'])) {
        // Wrapped structure: ['group' => ['id' => 1, 'name' => 'Guest']]
        $groupData = is_array($group['group']) ? $group['group'] : $group;
    } else {
        // Direct structure: ['id' => 1, 'name' => 'Guest'] (standard JSON from PrestaShop)
        $groupData = $group;
    }

    // Extract ID with multiple fallback strategies
    $id = $groupData['id']
        ?? $groupData['@attributes']['id']
        ?? $groupData['@id']
        ?? null;

    // Extract name with multilingual fallback
    if (is_array($groupData['name'] ?? null)) {
        $name = $groupData['name']['language']
            ?? $groupData['name'][0]
            ?? current($groupData['name'])
            ?? 'Unknown';
    } else {
        $name = $groupData['name'] ?? 'Unknown';
    }

    // Only add groups with valid ID
    if ($id !== null) {
        $this->prestashopPriceGroups[] = [
            'id' => $id,
            'name' => $name,
            'price_display_method' => $groupData['price_display_method'] ?? 0,
            'reduction' => $groupData['reduction'] ?? 0,
        ];
    }
}
```

### Key Improvements

1. ✅ **Defensive Key Check**: `isset($group['group'])` BEFORE accessing
2. ✅ **Dual Structure Support**: Handles both wrapped and direct JSON
3. ✅ **Multiple Fallbacks**: ID extraction (`id`, `@attributes.id`, `@id`)
4. ✅ **Multilingual Support**: Name extraction with language fallback
5. ✅ **Null Safety**: Skip groups without valid ID
6. ✅ **PHP 8.x Compatible**: No undefined array key warnings

## PrestaShop API Response Structures

### Structure A: Direct (Standard JSON) ✅ Most Common

```json
{
    "groups": [
        {"id": 1, "name": "Guest"},
        {"id": 2, "name": "Customer"}
    ]
}
```

**Usage:** PrestaShop 8.x/9.x with `Output-Format: JSON` header

### Structure B: Wrapped (XML Conversion)

```json
{
    "groups": [
        {"group": {"id": 1, "name": "Guest"}},
        {"group": {"id": 2, "name": "Customer"}}
    ]
}
```

**Usage:** Rare - może występować przy konwersji XML→JSON w starszych wersjach

### Structure C: Single Group (Edge Case)

```json
{
    "groups": {"id": 1, "name": "Guest"}
}
```

**Usage:** Edge case - kod obsługuje przez `is_array($response['groups']) ? ... : [$response['groups']]`

## Testing Recommendations

### Unit Test Case

```php
public function test_parseGroupsResponse_handlesDirectStructure()
{
    $response = [
        'groups' => [
            ['id' => 1, 'name' => 'Guest', 'price_display_method' => 0, 'reduction' => 0],
            ['id' => 2, 'name' => 'Customer', 'price_display_method' => 0, 'reduction' => 0],
        ],
    ];

    // Should not throw "Undefined array key" error
    $component = Livewire::test(AddShop::class);
    // ... test logic
}

public function test_parseGroupsResponse_handlesWrappedStructure()
{
    $response = [
        'groups' => [
            ['group' => ['id' => 1, 'name' => 'Guest', 'price_display_method' => 0]],
            ['group' => ['id' => 2, 'name' => 'Customer', 'price_display_method' => 0]],
        ],
    ];

    // Should handle wrapped structure gracefully
    $component = Livewire::test(AddShop::class);
    // ... test logic
}
```

### Manual Testing

1. Navigate to `/admin/shops/create`
2. Fill in shop details (steps 1-3)
3. Click "Pobierz Grupy Cenowe" in step 4
4. Verify groups load without errors
5. Check Laravel logs for successful fetch

## Related Code Locations

- **Fixed File**: `app/Http/Livewire/Admin/Shops/AddShop.php:498-534`
- **API Client**: `app/Services/PrestaShop/PrestaShop8Client.php:165-168` (getPriceGroups)
- **API Client**: `app/Services/PrestaShop/PrestaShop9Client.php:205-208` (getPriceGroups)
- **Base Client**: `app/Services/PrestaShop/BasePrestaShopClient.php:90-159` (makeRequest with JSON header)

## Prevention Measures

### Coding Standard: Defensive API Parsing

**RULE**: Always check `isset()` BEFORE accessing array keys from external APIs

```php
// ❌ BAD: Assumes key exists
$value = $array['key'] ? $array['key'] : $default;

// ✅ GOOD: Defensive check
$value = isset($array['key']) ? $array['key'] : $default;

// ✅ BEST: Null coalescing operator
$value = $array['key'] ?? $default;
```

### Documentation Reference

PrestaShop Web Services API documentation:
- [PrestaShop 8.x Webservice](https://devdocs.prestashop-project.org/8/webservice/)
- [PrestaShop 9.x Admin API](https://github.com/prestashop/docs/blob/9.x/admin-api/)

**Key Headers:**
- `Output-Format: JSON` - Forces JSON response (default: XML)
- `Accept: application/json` - Preferred response format
- `Content-Type: application/json` - Request body format

## Impact Assessment

**Before Fix:**
- ❌ Unable to complete shop configuration wizard (step 4 blocked)
- ❌ Price group mapping unavailable
- ❌ Cannot add new PrestaShop shops to system

**After Fix:**
- ✅ Shop wizard completes successfully
- ✅ Price groups fetched and displayed
- ✅ Supports both PrestaShop 8.x and 9.x response formats
- ✅ Handles edge cases (single group, multilingual names, wrapped structure)

## Deployment Notes

**Files Modified:**
1. `app/Http/Livewire/Admin/Shops/AddShop.php` - Defensive parsing logic

**Deployment Steps:**
```powershell
# Upload fixed file
pscp -i $HostidoKey -P 64321 `
  "app/Http/Livewire/Admin/Shops/AddShop.php" `
  host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Admin/Shops/

# Clear cache
plink ... -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan view:clear"
```

**Verification:**
```bash
# Test shop wizard with real PrestaShop instance
curl -X GET "https://ppm.mpptrade.pl/admin/shops/create"
# Navigate to step 4 and click "Pobierz Grupy Cenowe"
```

## Lessons Learned

1. **Never assume API structure** - Always implement defensive parsing
2. **PrestaShop JSON vs XML** - Different structures despite same endpoint
3. **PHP 8.x strictness** - Undefined key warnings treated as errors
4. **isset() before access** - Critical for external data sources
5. **Multiple fallbacks** - Resilience against API variations across versions

## Related Issues

- Similar pattern may exist in other API parsers (categories, manufacturers, attributes)
- Consider audit of all PrestaShop API response parsing code
- Potential skill: `prestashop-defensive-parsing` for future implementations

## References

- Production Error Logs: `storage/logs/laravel.log` (2025-11-13 09:11:29)
- PrestaShop Test Instance: `https://dev.mpptrade.pl/`
- Diagnostic Script: `_TEMP/diagnose_groups_response.php`
- Fix Script: `_TEMP/fix_groups_parsing.ps1`
