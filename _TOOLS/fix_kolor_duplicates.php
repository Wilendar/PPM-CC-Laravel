<?php
/**
 * Fix Script: Merge duplicate Kolor attribute types
 *
 * UPDATED: Handle duplicate values with same label but different codes
 *
 * Problem:
 * - Kolor (ID: 20, code: olor) - 15 values (actually used)
 * - Kolor (ID: 21, code: kolor) - 0 values (empty duplicate)
 * - Some values have duplicates (e.g., two "Żółty" with codes 'ty' and 'zolty')
 *
 * Solution:
 * 1. Identify duplicate values (same label)
 * 2. Merge duplicates (update variant_attributes, delete extra)
 * 3. Fix corrupted codes
 * 4. Migrate all values to type 21
 * 5. Delete type 20
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\AttributeType;
use App\Models\AttributeValue;
use App\Models\VariantAttribute;
use Illuminate\Support\Facades\DB;

echo "=== FIX KOLOR DUPLICATES v2 ===\n\n";

// Find the duplicate types
$oldType = AttributeType::find(20); // code: olor (corrupted)
$newType = AttributeType::find(21); // code: kolor (correct)

if (!$oldType || !$newType) {
    echo "ERROR: Cannot find both Kolor types!\n";
    exit(1);
}

echo "Old Type: ID={$oldType->id}, code={$oldType->code}\n";
echo "New Type: ID={$newType->id}, code={$newType->code}\n\n";

// Get all values for old type
$oldValues = AttributeValue::where('attribute_type_id', $oldType->id)->get();

// Find duplicates by label
echo "=== ANALYZING DUPLICATES ===\n";
$valuesByLabel = $oldValues->groupBy('label');
$duplicates = [];
$uniqueValues = [];

foreach ($valuesByLabel as $label => $values) {
    if ($values->count() > 1) {
        $duplicates[$label] = $values;
        echo "DUPLICATE: {$label}\n";
        foreach ($values as $v) {
            echo "  - ID={$v->id}, code={$v->code}\n";
        }
    } else {
        $uniqueValues[$label] = $values->first();
    }
}

// Code fixes map
$codeFixes = [
    'ty' => 'zolty',
    'iebieski' => 'niebieski',
    'ielony' => 'zielony',
    'omara_czowy' => 'pomaranczowy',
    'zerwony' => 'czerwony',
    'owy' => 'rozowy',
    'omaranczowy' => 'pomaranczowy',
    'ozowy' => 'rozowy',
    'ialy' => 'bialy',
    'ioletowy' => 'fioletowy',
    'zarny' => 'czarny',
    'ranatowy' => 'granatowy',
    'ielony_luo' => 'zielony_fluo',
    'omaranczowy_luo' => 'pomaranczowy_fluo',
];

// For duplicates, determine which one to keep (prefer correct code)
echo "\n=== DUPLICATE RESOLUTION PLAN ===\n";
$keepValues = [];
$deleteValues = [];

foreach ($duplicates as $label => $values) {
    // Find the one with correct/better code
    $keep = null;
    $delete = [];

    foreach ($values as $v) {
        $hasCorrectCode = !isset($codeFixes[$v->code]); // Not in fix list = already correct
        if ($hasCorrectCode && !$keep) {
            $keep = $v;
        } else {
            $delete[] = $v;
        }
    }

    // If no correct code found, keep first one
    if (!$keep) {
        $keep = $values->first();
        $delete = $values->slice(1)->values()->all();
    }

    echo "Keep: ID={$keep->id} ({$keep->code}), Delete: " . implode(', ', array_map(fn($v) => $v->id, $delete)) . "\n";
    $keepValues[$label] = $keep;
    $deleteValues = array_merge($deleteValues, $delete);
}

// Add unique values to keep list
foreach ($uniqueValues as $label => $value) {
    $keepValues[$label] = $value;
}

echo "\n=== EXECUTION PLAN ===\n";
echo "Values to keep: " . count($keepValues) . "\n";
echo "Values to delete (duplicates): " . count($deleteValues) . "\n";
echo "Variant attributes to migrate: " . VariantAttribute::where('attribute_type_id', $oldType->id)->count() . "\n";

// Check if script should actually execute
if (!isset($argv[1]) || $argv[1] !== '--execute') {
    echo "\nTo execute changes, run: php fix_kolor_duplicates.php --execute\n";
    exit(0);
}

echo "\n=== EXECUTING CHANGES ===\n\n";

DB::beginTransaction();
try {
    // 1. Merge duplicate values - update variant_attributes to point to keep value
    echo "1. Merging duplicate values...\n";
    foreach ($duplicates as $label => $values) {
        $keepValue = $keepValues[$label];

        foreach ($values as $v) {
            if ($v->id === $keepValue->id) continue;

            // Move variant_attributes from delete value to keep value
            $movedCount = VariantAttribute::where('value_id', $v->id)
                ->update(['value_id' => $keepValue->id]);
            echo "   Moved {$movedCount} variant_attrs from ID={$v->id} to ID={$keepValue->id}\n";
        }
    }

    // 2. Delete duplicate values
    echo "\n2. Deleting duplicate values...\n";
    foreach ($deleteValues as $v) {
        echo "   Deleting ID={$v->id} ({$v->label})\n";
        $v->delete();
    }

    // 3. Fix corrupted codes on remaining values
    echo "\n3. Fixing corrupted codes...\n";
    foreach ($keepValues as $label => $v) {
        $v->refresh(); // Reload from DB
        $oldCode = $v->code;
        $newCode = $codeFixes[$oldCode] ?? $oldCode;
        if ($oldCode !== $newCode) {
            $v->code = $newCode;
            $v->save();
            echo "   Fixed: {$label} '{$oldCode}' -> '{$newCode}'\n";
        }
    }

    // 4. Migrate values to new type
    echo "\n4. Migrating values to type 21...\n";
    $migratedCount = AttributeValue::where('attribute_type_id', $oldType->id)
        ->update(['attribute_type_id' => $newType->id]);
    echo "   Migrated {$migratedCount} values\n";

    // 5. Update variant_attributes to new type
    echo "\n5. Updating variant_attributes...\n";
    $updated = VariantAttribute::where('attribute_type_id', $oldType->id)
        ->update(['attribute_type_id' => $newType->id]);
    echo "   Updated {$updated} records\n";

    // 6. Delete old type
    echo "\n6. Deleting old type...\n";
    $oldType->delete();
    echo "   Type 20 deleted\n";

    // 7. Fix code on type 21 (from 'kolor' to 'color' - standard)
    echo "\n7. Updating type code...\n";
    $newType->code = 'color';
    $newType->save();
    echo "   Type code changed from 'kolor' to 'color'\n";

    DB::commit();
    echo "\n=== SUCCESS! ===\n";
    echo "All changes committed. Please verify in admin panel.\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n=== ERROR ===\n";
    echo "Rollback executed. Error: " . $e->getMessage() . "\n";
    exit(1);
}
