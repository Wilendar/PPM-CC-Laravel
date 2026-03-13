<?php

namespace App\Http\Livewire\Admin\Parameters\Traits;

/**
 * LocationFiltersTrait - Filtering methods for location hierarchy tree
 *
 * Extracted from LocationManager to keep the main component lean.
 * Provides pattern-based, occupancy-based, and search-based filtering
 * of the hierarchical location tree (Zone > Row > Shelf > Bin).
 */
trait LocationFiltersTrait
{
    /**
     * Filter hierarchy tree by pattern_type
     */
    private function filterTreeByPattern(array $tree, string $pattern): array
    {
        return array_values(array_filter(
            array_map(function (array $zone) use ($pattern) {
                if (isset($zone['pattern_type']) && $zone['pattern_type'] === $pattern) {
                    return $zone;
                }

                // Filter children recursively
                $zone['children'] = $this->filterChildrenByPattern($zone['children'] ?? [], $pattern);

                if (!empty($zone['children']) || (isset($zone['pattern_type']) && $zone['pattern_type'] === $pattern)) {
                    return $zone;
                }

                return null;
            }, $tree)
        ));
    }

    /**
     * Recursively filter children nodes by pattern_type
     */
    private function filterChildrenByPattern(array $children, string $pattern): array
    {
        return array_values(array_filter(
            array_map(function (array $child) use ($pattern) {
                // Leaf node with id
                if (isset($child['id'])) {
                    return (isset($child['pattern_type']) && $child['pattern_type'] === $pattern)
                        ? $child
                        : null;
                }

                // Intermediate node - filter its children
                $child['children'] = $this->filterChildrenByPattern($child['children'] ?? [], $pattern);

                return !empty($child['children']) ? $child : null;
            }, $children)
        ));
    }

    /**
     * Filter hierarchy tree by occupancy (occupied/empty)
     */
    private function filterTreeByOccupancy(array $tree, string $occupancy): array
    {
        $showOccupied = ($occupancy === 'occupied');

        return array_values(array_filter(
            array_map(function (array $zone) use ($showOccupied) {
                $zone['children'] = $this->filterChildrenByOccupancy($zone['children'] ?? [], $showOccupied);

                // Keep zone if it has matching children or matching product_count
                $zoneMatch = $showOccupied
                    ? ($zone['product_count'] ?? 0) > 0
                    : ($zone['product_count'] ?? 0) === 0;

                if (!empty($zone['children']) || $zoneMatch) {
                    return $zone;
                }

                return null;
            }, $tree)
        ));
    }

    /**
     * Recursively filter children by occupancy
     */
    private function filterChildrenByOccupancy(array $children, bool $showOccupied): array
    {
        return array_values(array_filter(
            array_map(function (array $child) use ($showOccupied) {
                if (isset($child['id'])) {
                    $occupied = ($child['product_count'] ?? 0) > 0;
                    return ($occupied === $showOccupied) ? $child : null;
                }

                $child['children'] = $this->filterChildrenByOccupancy($child['children'] ?? [], $showOccupied);
                return !empty($child['children']) ? $child : null;
            }, $children)
        ));
    }

    /**
     * Filter hierarchy tree by search term (matches code)
     */
    private function filterTreeBySearch(array $tree, string $searchTerm): array
    {
        $term = mb_strtolower($searchTerm);

        return array_values(array_filter(
            array_map(function (array $zone) use ($term) {
                // Check if zone label matches
                $zoneMatch = str_contains(mb_strtolower($zone['label'] ?? ''), $term)
                    || str_contains(mb_strtolower($zone['zone'] ?? ''), $term);

                $zone['children'] = $this->filterChildrenBySearch($zone['children'] ?? [], $term);

                if ($zoneMatch || !empty($zone['children'])) {
                    return $zone;
                }

                return null;
            }, $tree)
        ));
    }

    /**
     * Recursively filter children by search term
     */
    private function filterChildrenBySearch(array $children, string $term): array
    {
        return array_values(array_filter(
            array_map(function (array $child) use ($term) {
                if (isset($child['id'])) {
                    return str_contains(mb_strtolower($child['code'] ?? ''), $term)
                        ? $child
                        : null;
                }

                // Check label/row_code match
                $labelMatch = str_contains(mb_strtolower($child['label'] ?? ''), $term)
                    || str_contains(mb_strtolower($child['row_code'] ?? ''), $term);

                $child['children'] = $this->filterChildrenBySearch($child['children'] ?? [], $term);

                if ($labelMatch || !empty($child['children'])) {
                    return $child;
                }

                return null;
            }, $children)
        ));
    }
}
