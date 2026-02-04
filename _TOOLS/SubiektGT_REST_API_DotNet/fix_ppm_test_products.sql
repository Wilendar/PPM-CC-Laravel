-- FIX SQL Script for PPM-TEST products not visible in Subiekt GT GUI
-- Date: 2026-01-23
-- Products: PPM-TEST-001 (ID: 23224), PPM-TEST-002 (ID: 23225)

-- Step 1: Fix tw__Towar field values
UPDATE tw__Towar
SET
    tw_JakPrzySp = 1,              -- CRITICAL: Was 0, must be 1
    tw_SklepInternet = 1,          -- CRITICAL: Was 0, must be 1
    tw_KomunikatDokumenty = 3,     -- CRITICAL: Was 0, must be 3
    tw_GrupaJpkVat = -1,           -- CRITICAL: Was 0, must be -1
    tw_CzasDostawy = 0,            -- Was NULL, must be 0
    tw_DniWaznosc = 0,             -- Was NULL, must be 0
    tw_JednMiary = 'szt.',         -- Was 'szt', must be 'szt.'
    tw_JednMiaryZak = 'szt.',      -- Same
    tw_JednMiarySprz = 'szt.',     -- Same
    tw_IdVatSp = 100001,           -- VAT 23% for sales
    tw_IdVatZak = 100001           -- VAT 23% for purchase
WHERE tw_Id IN (23224, 23225);

-- Step 2: Check if tw_Stan records exist, if not - create them
-- For product 23224 (PPM-TEST-001)
IF NOT EXISTS (SELECT 1 FROM tw_Stan WHERE st_TowId = 23224 AND st_MagId = 1)
BEGIN
    INSERT INTO tw_Stan (st_TowId, st_MagId, st_Stan, st_StanMin, st_StanRez, st_StanMax)
    VALUES (23224, 1, 0, 0, 0, 0);
END

IF NOT EXISTS (SELECT 1 FROM tw_Stan WHERE st_TowId = 23224 AND st_MagId = 4)
BEGIN
    INSERT INTO tw_Stan (st_TowId, st_MagId, st_Stan, st_StanMin, st_StanRez, st_StanMax)
    VALUES (23224, 4, 0, 0, 0, 0);
END

-- For product 23225 (PPM-TEST-002)
IF NOT EXISTS (SELECT 1 FROM tw_Stan WHERE st_TowId = 23225 AND st_MagId = 1)
BEGIN
    INSERT INTO tw_Stan (st_TowId, st_MagId, st_Stan, st_StanMin, st_StanRez, st_StanMax)
    VALUES (23225, 1, 0, 0, 0, 0);
END

IF NOT EXISTS (SELECT 1 FROM tw_Stan WHERE st_TowId = 23225 AND st_MagId = 4)
BEGIN
    INSERT INTO tw_Stan (st_TowId, st_MagId, st_Stan, st_StanMin, st_StanRez, st_StanMax)
    VALUES (23225, 4, 0, 0, 0, 0);
END

-- Step 3: Verify fix
SELECT
    tw_Id,
    tw_Symbol,
    tw_Nazwa,
    tw_JakPrzySp,
    tw_SklepInternet,
    tw_KomunikatDokumenty,
    tw_GrupaJpkVat,
    tw_CzasDostawy,
    tw_DniWaznosc,
    tw_JednMiary,
    tw_IdVatSp,
    tw_IdVatZak
FROM tw__Towar
WHERE tw_Id IN (23224, 23225);

-- Check tw_Stan records
SELECT st_TowId, st_MagId, st_Stan
FROM tw_Stan
WHERE st_TowId IN (23224, 23225);

PRINT 'Fix completed! Please restart Subiekt GT and check if products are now visible.';
