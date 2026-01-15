<?php
/**
 * Fix Script: Merge duplicate Kolor attribute type (ID 23 -> ID 21)
 * Created: 2025-12-15
 *
 * Problem: Import created duplicate "Kolor" group (ID 23, code: olor)
 *          instead of using existing (ID 21, code: color)
 *
 * Solution:
 * 1. Migrate values from type 23 to type 21 (match by label or create)
 * 2. Update variant_attributes to point to type 21
 * 3. Delete type 23
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\AttributeType;
use App\Models\AttributeValue;
use App\Models\VariantAttribute;
use Illuminate\Support\Facades\DB;

echo "=== FIX KOLOR DUPLICATE (2025-12-15) ===\n\n";

$oldTypeId = 23; // Duplicate: code = olor
$newTypeId = 21; // Correct: code = color

$oldType = AttributeType::find($oldTypeId);
$newType = AttributeType::find($newTypeId);

if (!$oldType || !$newType) {
    echo "ERROR: Cannot find both types!\n";
    echo "Old type (ID {$oldTypeId}): " . ($oldType ? "exists" : "NOT FOUND") . "\n";
    echo "New type (ID {$newTypeId}): " . ($newType ? "exists" : "NOT FOUND") . "\n";
    exit(1);
}

echo "Old Type (to delete): ID={$oldType->id}, code={$oldType->code}, name={$oldType->name}\n";
echo "New Type (to keep):   ID={$newType->id}, code={$newType->code}, name={$newType->name}\n\n";

// Get values from old type
$oldValues = AttributeValue::where('attribute_type_id', $oldTypeId)->get();
echo "Values in old type: " . $oldValues->count() . "\n";

// Get values from new type (for matching)
$newValues = AttributeValue::where('attribute_type_id', $newTypeId)->get();
echo "Values in new type: " . $newValues->count() . "\n\n";

// Build mapping: old value -> new value
$valueMapping = [];
foreach ($oldValues as $oldValue) {
    // Try to find matching value in new type by label (case-insensitive)
    $matchingNew = $newValues->first(function ($v) use ($oldValue) {
        return strtolower($v->label) === strtolower($oldValue->label);
    });

    if ($matchingNew) {
        $valueMapping[$oldValue->id] = $matchingNew->id;
        echo "MATCH: '{$oldValue->label}' (old: {$oldValue->id}) -> (new: {$matchingNew->id})\n";
    } else {
        echo "NO MATCH: '{$oldValue->label}' (old: {$oldValue->id}) - will migrate to new type\n";
        $valueMapping[$oldValue->id] = 'migrate'; // Will move this value to new type
    }
}

// Count variant_attributes affected
$variantAttrsCount = VariantAttribute::where('attribute_type_id', $oldTypeId)->count();
echo "\nVariant attributes to update: {$variantAttrsCount}\n";

// Check if we should execute
if (!isset($argv[1]) || $argv[1] !== '--execute') {
    echo "\n=== DRY RUN MODE ===\n";
    echo "To execute changes, run: php fix_kolor_duplicate_dec15.php --execute\n";
    exit(0);
}

echo "\n=== EXECUTING CHANGES ===\n\n";

DB::beginTransaction();
try {
    // 1. Update variant_attributes - change value_id to matching new value
    echo "1. Updating variant_attributes...\n";
    foreach ($valueMapping as $oldValueId => $target) {
        if ($target === 'migrate') {
            continue; // Will handle after migration
        }

        $updated = VariantAttribute::where('attribute_type_id', $oldTypeId)
            ->where('value_id', $oldValueId)
            ->update([
                'attribute_type_id' => $newTypeId,
                'value_id' => $target
            ]);
        echo "   Updated {$updated} records: value {$oldValueId} -> {$target}\n";
    }

    // 2. Migrate unmatched values to new type
    echo "\n2. Migrating unmatched values...\n";
    foreach ($valueMapping as $oldValueId => $target) {
        if ($target !== 'migrate') {
            continue;
        }

        $oldValue = AttributeValue::find($oldValueId);
        if (!$oldValue) continue;

        // Move the value to new type
        $oldValue->attribute_type_id = $newTypeId;
        $oldValue->save();
        echo "   Migrated value ID {$oldValueId} ('{$oldValue->label}') to type {$newTypeId}\n";

        // Update variant_attributes for this value
        $updated = VariantAttribute::where('value_id', $oldValueId)
            ->update(['attribute_type_id' => $newTypeId]);
        echo "   Updated {$updated} variant_attributes\n";
    }

    // 3. Delete remaining values in old type (duplicates that were mapped)
    echo "\n3. Deleting old values (duplicates)...\n";
    foreach ($valueMapping as $oldValueId => $target) {
        if ($target === 'migrate') {
            continue; // Already migrated
        }

        $deleted = AttributeValue::where('id', $oldValueId)
            ->where('attribute_type_id', $oldTypeId)
            ->delete();
        if ($deleted) {
            echo "   Deleted old value ID {$oldValueId}\n";
        }
    }

    // 4. Delete old type
    echo "\n4. Deleting old attribute type...\n";
    $oldType->delete();
    echo "   Deleted type ID {$oldTypeId}\n";

    DB::commit();
    echo "\n=== SUCCESS! ===\n";
    echo "Duplicate attribute type merged. Please verify in admin panel.\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n=== ERROR ===\n";
    echo "Rollback executed. Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
