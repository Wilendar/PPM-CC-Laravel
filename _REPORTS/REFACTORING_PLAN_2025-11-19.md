# Database Refactoring & Optimization Plan
**Date:** 2025-11-19
**Status:** Draft
**Author:** Antigravity Agent

## 1. Executive Summary
This plan outlines the necessary steps to optimize the PPM database structure by removing redundancies, unifying data models, and improving indexing. The primary focus is on consolidating variant-specific tables into the main product tables and cleaning up legacy structures.

## 2. Identified Issues & Redundancies

### 2.1. Redundant Variant Tables
The following tables store data that is also supported by the main product tables (which now support `product_variant_id`):
- **`variant_prices`**: Redundant with `product_prices` (which has `product_variant_id`).
- **`variant_stock`**: Redundant with `product_stock` (which has `product_variant_id`).
- **`variant_images`**: Redundant with `media` (polymorphic `mediable_type` = `App\Models\ProductVariant`).

**Impact:** Data fragmentation, increased maintenance complexity, potential for data inconsistency.

### 2.2. Redundant Indexes
- **Table:** `product_shop_data`
- **Index:** `idx_product_shop_lookup` (`product_id`, `shop_id`)
- **Issue:** Redundant with `unique_product_per_shop` (`product_id`, `shop_id`). The unique constraint already provides the index functionality.

### 2.3. Large Data Columns
- **Table:** `oauth_audit_logs`
- **Columns:** `oauth_request_data`, `oauth_response_data`
- **Issue:** Storing full JSON payloads can lead to rapid database growth.
- **Recommendation:** Implement a pruning policy or archive strategy for logs older than X days.

## 3. Proposed Refactoring Steps

### Phase 1: Immediate Optimizations (Low Risk)
1.  **Drop Redundant Index:**
    ```sql
    ALTER TABLE product_shop_data DROP INDEX idx_product_shop_lookup;
    ```
2.  **Code Cleanup (Completed):**
    - Removed `ProductShopCategory` model and references (Done).

### Phase 2: Variant Data Consolidation (Medium/High Risk)
**Goal:** Migrate all variant data to unified tables and drop `variant_*` tables.

#### Step 2.1: Migrate Prices
1.  Create migration to copy data from `variant_prices` to `product_prices`.
    - Map `variant_id` -> `product_variant_id`.
    - Map `price_group_id`, `price`, `special_price` etc.
2.  Update `ProductVariant` model:
    - Change `prices()` relationship to use `ProductPrice`.
3.  Verify data integrity.
4.  Drop `variant_prices` table.

#### Step 2.2: Migrate Stock
1.  Create migration to copy data from `variant_stock` to `product_stock`.
    - Map `variant_id` -> `product_variant_id`.
    - Map `warehouse_id`, `quantity`, `reserved`.
2.  Update `ProductVariant` model:
    - Change `stock()` relationship to use `ProductStock`.
3.  Verify data integrity.
4.  Drop `variant_stock` table.

#### Step 2.3: Migrate Images
1.  Create migration to copy data from `variant_images` to `media`.
    - Set `mediable_type` = `App\Models\ProductVariant`.
    - Set `mediable_id` = `variant_id`.
    - Map paths and metadata.
2.  Update `ProductVariant` model:
    - Change `images()` relationship to use `Media` (polymorphic).
3.  Verify data integrity.
4.  Drop `variant_images` table.

### Phase 3: Maintenance & Archiving
1.  **OAuth Logs Pruning:**
    - Create a scheduled job (`PruneOAuthLogs`) to delete logs older than 90 days (or archive them).

## 4. Implementation Priority
1.  **Phase 1:** Immediate. Can be done in the next sprint.
2.  **Phase 2:** Scheduled refactoring. Requires careful testing as it affects core pricing and stock logic.
3.  **Phase 3:** Low priority, can be added to backlog.

## 5. Verification
- **Automated Tests:** Run full suite after each migration step.
- **Manual Verification:** Verify pricing and stock levels for variants in the UI.
