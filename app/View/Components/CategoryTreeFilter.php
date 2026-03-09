<?php

namespace App\View\Components;

use App\Models\Category;
use Illuminate\View\Component;

class CategoryTreeFilter extends Component
{
    public string $wireModel;
    public string $label;
    public array $categoryTree;

    public function __construct(string $wireModel, string $label = 'Kategoria')
    {
        $this->wireModel = $wireModel;
        $this->label = $label;
        $this->categoryTree = $this->buildTree();
    }

    private function buildTree(): array
    {
        $categories = Category::select(['id', 'name', 'parent_id', 'level'])
            ->where('is_active', true)
            ->orderBy('level', 'asc')
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        $categoryMap = [];

        foreach ($categories as $cat) {
            $categoryMap[$cat->id] = [
                'id' => $cat->id,
                'name' => $cat->name,
                'parent_id' => $cat->parent_id,
                'level' => $cat->level,
                'children' => [],
            ];
        }

        $tree = [];
        foreach ($categoryMap as $id => &$node) {
            if ($node['parent_id'] && isset($categoryMap[$node['parent_id']])) {
                $categoryMap[$node['parent_id']]['children'][] = &$node;
            } elseif (!$node['parent_id']) {
                $tree[] = &$node;
            }
        }
        unset($node);

        return $tree;
    }

    public function render()
    {
        return view('components.category-tree-filter');
    }
}
