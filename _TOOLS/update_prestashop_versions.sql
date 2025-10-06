-- Update PrestaShop Shops Version Field
-- Fixes: "Unsupported PrestaShop version: . Supported versions: 8, 9" error
-- Date: 2025-10-03
-- Issue: Empty version field prevents PrestaShop API client initialization

-- First check current state
SELECT id, shop_name, shop_url, version, api_key
FROM prestashop_shops
ORDER BY id;

-- Update all shops with empty version to version 8 (default)
UPDATE prestashop_shops
SET version = '8'
WHERE version IS NULL OR version = '' OR TRIM(version) = '';

-- Verify update
SELECT id, shop_name, shop_url, version
FROM prestashop_shops
ORDER BY id;

-- Expected result: All shops should now have version = '8'
