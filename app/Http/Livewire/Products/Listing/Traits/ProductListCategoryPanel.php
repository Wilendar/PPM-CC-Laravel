<?php

namespace App\Http\Livewire\Products\Listing\Traits;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

trait ProductListCategoryPanel
{
    /**
     * Build hierarchical category tree for panel (L2+ only, skip Baza/Wszystko).
     * Cached for 1 hour, invalidated by CategoryTree changes.
     */
    #[Computed]
    public function categoryTreeForPanel(): array
    {
        return Cache::remember('category_panel_tree', 3600, function () {
            $categories = Category::select('id', 'name', 'parent_id', 'level', 'path', 'sort_order')
                ->where('is_active', true)
                ->where('level', '>=', 2)
                ->orderBy('level')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            return $this->buildPanelTree($categories);
        });
    }

    /**
     * Get product-to-category mapping for current page products.
     * Called from Alpine via $wire.call() after pagination changes.
     */
    public function getProductCategoryMapForPanel(): array
    {
        // Get current page product IDs from the products computed property
        $productIds = $this->products->pluck('id')->toArray();

        if (empty($productIds)) {
            return [];
        }

        $relations = DB::table('product_categories')
            ->whereIn('product_id', $productIds)
            ->whereNull('shop_id')
            ->select('product_id', 'category_id', 'is_primary')
            ->get();

        $map = [];
        foreach ($relations as $rel) {
            $pid = $rel->product_id;
            if (!isset($map[$pid])) {
                $map[$pid] = ['primary' => null, 'categories' => []];
            }
            $map[$pid]['categories'][] = $rel->category_id;
            if ($rel->is_primary) {
                $map[$pid]['primary'] = $rel->category_id;
            }
        }

        return $map;
    }

    /**
     * Build hierarchical tree from flat category collection.
     */
    private function buildPanelTree($categories): array
    {
        $byParent = [];
        $byId = [];

        foreach ($categories as $cat) {
            $node = [
                'id' => $cat->id,
                'name' => $cat->name,
                'parentId' => $cat->parent_id,
                'level' => $cat->level,
                'children' => [],
            ];
            $byId[$cat->id] = $node;
            $byParent[$cat->parent_id][] = $cat->id;
        }

        // Build tree bottom-up
        foreach ($byId as $id => &$node) {
            if (isset($byParent[$id])) {
                foreach ($byParent[$id] as $childId) {
                    $node['children'][] = &$byId[$childId];
                }
            }
        }
        unset($node);

        // Return only root nodes (L2 categories whose parent is L1)
        $roots = [];
        foreach ($byId as &$node) {
            if ($node['level'] == 2) {
                $roots[] = &$node;
            }
        }

        return $roots;
    }
}
