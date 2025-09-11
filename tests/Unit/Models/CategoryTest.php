<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Database\Eloquent\Collection;

/**
 * CategoryTest - Unit tests dla Category model
 * 
 * Test coverage:
 * - Tree structure operations
 * - Self-referencing relationships 
 * - Path materialization i level calculation
 * - Business logic methods (move, ancestors, descendants)
 * - Query scopes dla tree operations
 * - Validation rules i constraints
 * 
 * @package Tests\Unit\Models
 * @version 1.0
 * @since FAZA A - Core Models Implementation
 */
class CategoryTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations
        $this->artisan('migrate');
    }

    /*
    |--------------------------------------------------------------------------
    | BASIC MODEL TESTS
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_can_create_a_category()
    {
        $category = Category::factory()->create([
            'name' => 'Test Category',
            'parent_id' => null
        ]);

        $this->assertInstanceOf(Category::class, $category);
        $this->assertEquals('Test Category', $category->name);
        $this->assertNull($category->parent_id);
        $this->assertEquals(0, $category->level);
        $this->assertNull($category->path);
        $this->assertTrue($category->is_active);
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $fillable = [
            'parent_id', 'name', 'slug', 'description',
            'sort_order', 'is_active', 'icon',
            'meta_title', 'meta_description'
        ];

        $category = new Category();
        $this->assertEquals($fillable, $category->getFillable());
    }

    /** @test */
    public function it_has_correct_casts()
    {
        $category = new Category();
        $casts = $category->getCasts();

        $this->assertEquals('integer', $casts['parent_id']);
        $this->assertEquals('integer', $casts['level']);
        $this->assertEquals('integer', $casts['sort_order']);
        $this->assertEquals('boolean', $casts['is_active']);
    }

    /*
    |--------------------------------------------------------------------------
    | TREE STRUCTURE TESTS
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function root_category_has_level_zero_and_null_path()
    {
        $category = Category::factory()->root()->create();

        $this->assertEquals(0, $category->level);
        $this->assertNull($category->path);
        $this->assertNull($category->parent_id);
    }

    /** @test */
    public function child_category_has_correct_level_and_path()
    {
        $parent = Category::factory()->root()->create();
        $child = Category::factory()->create([
            'parent_id' => $parent->id
        ]);

        $this->assertEquals(1, $child->level);
        $this->assertEquals('/' . $parent->id, $child->path);
        $this->assertEquals($parent->id, $child->parent_id);
    }

    /** @test */
    public function grandchild_category_has_correct_level_and_path()
    {
        $grandparent = Category::factory()->root()->create();
        $parent = Category::factory()->create(['parent_id' => $grandparent->id]);
        $grandchild = Category::factory()->create(['parent_id' => $parent->id]);

        $this->assertEquals(2, $grandchild->level);
        $this->assertEquals('/' . $grandparent->id . '/' . $parent->id, $grandchild->path);
    }

    /** @test */
    public function category_cannot_exceed_max_level()
    {
        // Create 5-level deep category (0,1,2,3,4 = MAX_LEVEL)
        $level0 = Category::factory()->root()->create();
        $level1 = Category::factory()->create(['parent_id' => $level0->id]);
        $level2 = Category::factory()->create(['parent_id' => $level1->id]);
        $level3 = Category::factory()->create(['parent_id' => $level2->id]);
        $level4 = Category::factory()->create(['parent_id' => $level3->id]);

        $this->assertEquals(4, $level4->level);
        $this->assertEquals(Category::MAX_LEVEL, $level4->level);

        // Try to create level 5 (should throw exception)
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Maximum category depth exceeded');

        Category::factory()->create(['parent_id' => $level4->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIP TESTS
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_belongs_to_parent_category()
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $this->assertInstanceOf(Category::class, $child->parent);
        $this->assertEquals($parent->id, $child->parent->id);
    }

    /** @test */
    public function it_has_many_children_categories()
    {
        $parent = Category::factory()->create();
        $child1 = Category::factory()->create(['parent_id' => $parent->id, 'sort_order' => 2]);
        $child2 = Category::factory()->create(['parent_id' => $parent->id, 'sort_order' => 1]);

        $children = $parent->children;

        $this->assertInstanceOf(Collection::class, $children);
        $this->assertCount(2, $children);
        
        // Should be ordered by sort_order
        $this->assertEquals($child2->id, $children->first()->id);
        $this->assertEquals($child1->id, $children->last()->id);
    }

    /** @test */
    public function it_belongs_to_many_products()
    {
        $category = Category::factory()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        $category->products()->attach($product1->id, ['is_primary' => true]);
        $category->products()->attach($product2->id, ['is_primary' => false]);

        $this->assertCount(2, $category->products);
        $this->assertTrue($category->products->contains($product1));
        $this->assertTrue($category->products->contains($product2));
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSOR TESTS - Tree Operations
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function ancestors_accessor_returns_all_parent_categories()
    {
        $grandparent = Category::factory()->create(['name' => 'Grandparent']);
        $parent = Category::factory()->create(['parent_id' => $grandparent->id, 'name' => 'Parent']);
        $child = Category::factory()->create(['parent_id' => $parent->id, 'name' => 'Child']);

        $ancestors = $child->ancestors;

        $this->assertInstanceOf(Collection::class, $ancestors);
        $this->assertCount(2, $ancestors);
        
        // Should be ordered from root to direct parent
        $this->assertEquals($grandparent->id, $ancestors->first()->id);
        $this->assertEquals($parent->id, $ancestors->last()->id);
    }

    /** @test */
    public function descendants_accessor_returns_all_child_categories()
    {
        $parent = Category::factory()->create(['name' => 'Parent']);
        $child1 = Category::factory()->create(['parent_id' => $parent->id, 'name' => 'Child 1']);
        $child2 = Category::factory()->create(['parent_id' => $parent->id, 'name' => 'Child 2']);
        $grandchild = Category::factory()->create(['parent_id' => $child1->id, 'name' => 'Grandchild']);

        $descendants = $parent->descendants;

        $this->assertInstanceOf(Collection::class, $descendants);
        $this->assertCount(3, $descendants);
        
        $descendantIds = $descendants->pluck('id')->toArray();
        $this->assertContains($child1->id, $descendantIds);
        $this->assertContains($child2->id, $descendantIds);
        $this->assertContains($grandchild->id, $descendantIds);
    }

    /** @test */
    public function breadcrumb_accessor_returns_navigation_array()
    {
        $grandparent = Category::factory()->create(['name' => 'Motors', 'slug' => 'motors']);
        $parent = Category::factory()->create(['parent_id' => $grandparent->id, 'name' => 'Motorcycles', 'slug' => 'motorcycles']);
        $child = Category::factory()->create(['parent_id' => $parent->id, 'name' => '125cc', 'slug' => '125cc']);

        $breadcrumb = $child->breadcrumb;

        $this->assertIsArray($breadcrumb);
        $this->assertCount(3, $breadcrumb);
        
        $this->assertEquals('Motors', $breadcrumb[0]['name']);
        $this->assertEquals('Motorcycles', $breadcrumb[1]['name']);
        $this->assertEquals('125cc', $breadcrumb[2]['name']);
        
        $this->assertEquals('motors', $breadcrumb[0]['slug']);
        $this->assertEquals('motorcycles', $breadcrumb[1]['slug']);
        $this->assertEquals('125cc', $breadcrumb[2]['slug']);
    }

    /** @test */
    public function full_name_accessor_returns_hierarchical_name()
    {
        $grandparent = Category::factory()->create(['name' => 'Motors']);
        $parent = Category::factory()->create(['parent_id' => $grandparent->id, 'name' => 'Motorcycles']);
        $child = Category::factory()->create(['parent_id' => $parent->id, 'name' => '125cc']);

        $fullName = $child->full_name;

        $this->assertEquals('Motors > Motorcycles > 125cc', $fullName);
    }

    /** @test */
    public function is_root_accessor_identifies_root_categories()
    {
        $root = Category::factory()->root()->create();
        $child = Category::factory()->create(['parent_id' => $root->id]);

        $this->assertTrue($root->is_root);
        $this->assertFalse($child->is_root);
    }

    /** @test */
    public function is_leaf_accessor_identifies_leaf_categories()
    {
        $parent = Category::factory()->create();
        $leaf = Category::factory()->create(['parent_id' => $parent->id]);

        $this->assertFalse($parent->is_leaf);
        $this->assertTrue($leaf->is_leaf);
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPE TESTS
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function active_scope_returns_only_active_categories()
    {
        Category::factory()->create(['is_active' => true, 'name' => 'Active']);
        Category::factory()->create(['is_active' => false, 'name' => 'Inactive']);

        $activeCategories = Category::active()->get();

        $this->assertCount(1, $activeCategories);
        $this->assertTrue($activeCategories->every(fn($c) => $c->is_active));
    }

    /** @test */
    public function root_categories_scope_returns_only_root_level()
    {
        $root1 = Category::factory()->root()->create();
        $root2 = Category::factory()->root()->create();
        Category::factory()->create(['parent_id' => $root1->id]);

        $rootCategories = Category::rootCategories()->get();

        $this->assertCount(2, $rootCategories);
        $this->assertTrue($rootCategories->every(fn($c) => is_null($c->parent_id)));
    }

    /** @test */
    public function by_level_scope_filters_by_tree_level()
    {
        $level0 = Category::factory()->root()->create();
        $level1a = Category::factory()->create(['parent_id' => $level0->id]);
        $level1b = Category::factory()->create(['parent_id' => $level0->id]);
        Category::factory()->create(['parent_id' => $level1a->id]);

        $level1Categories = Category::byLevel(1)->get();

        $this->assertCount(2, $level1Categories);
        $this->assertTrue($level1Categories->every(fn($c) => $c->level === 1));
    }

    /** @test */
    public function descendants_scope_finds_all_children()
    {
        $parent = Category::factory()->create();
        $child1 = Category::factory()->create(['parent_id' => $parent->id]);
        $child2 = Category::factory()->create(['parent_id' => $parent->id]);
        $grandchild = Category::factory()->create(['parent_id' => $child1->id]);
        Category::factory()->create(); // Unrelated category

        $descendants = Category::descendants($parent->id)->get();

        $this->assertCount(3, $descendants);
        $descendantIds = $descendants->pluck('id')->toArray();
        $this->assertContains($child1->id, $descendantIds);
        $this->assertContains($child2->id, $descendantIds);
        $this->assertContains($grandchild->id, $descendantIds);
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS LOGIC TESTS
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function move_to_updates_tree_structure()
    {
        $oldParent = Category::factory()->create(['name' => 'Old Parent']);
        $newParent = Category::factory()->create(['name' => 'New Parent']);
        $child = Category::factory()->create(['parent_id' => $oldParent->id, 'name' => 'Child']);

        $result = $child->moveTo($newParent->id);

        $this->assertTrue($result);
        $child->refresh();
        $this->assertEquals($newParent->id, $child->parent_id);
        $this->assertEquals(1, $child->level);
        $this->assertEquals('/' . $newParent->id, $child->path);
    }

    /** @test */
    public function move_to_root_level_works()
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $result = $child->moveTo(null);

        $this->assertTrue($result);
        $child->refresh();
        $this->assertNull($child->parent_id);
        $this->assertEquals(0, $child->level);
        $this->assertNull($child->path);
    }

    /** @test */
    public function move_to_prevents_circular_references()
    {
        $parent = Category::factory()->create(['name' => 'Parent']);
        $child = Category::factory()->create(['parent_id' => $parent->id, 'name' => 'Child']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot move category to its descendant');

        $parent->moveTo($child->id);
    }

    /** @test */
    public function move_to_prevents_exceeding_max_depth()
    {
        // Create 4-level deep tree
        $level0 = Category::factory()->create();
        $level1 = Category::factory()->create(['parent_id' => $level0->id]);
        $level2 = Category::factory()->create(['parent_id' => $level1->id]);
        $level3 = Category::factory()->create(['parent_id' => $level2->id]);

        // Create separate category with child
        $source = Category::factory()->create();
        $sourceChild = Category::factory()->create(['parent_id' => $source->id]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Move would exceed maximum tree depth');

        // Try to move source (with child) under level3 - would create level 5
        $source->moveTo($level3->id);
    }

    /** @test */
    public function get_tree_options_returns_formatted_array()
    {
        $parent = Category::factory()->create(['name' => 'Parent']);
        $child = Category::factory()->create(['parent_id' => $parent->id, 'name' => 'Child']);

        $options = Category::getTreeOptions();

        $this->assertIsArray($options);
        $this->assertArrayHasKey($parent->id, $options);
        $this->assertArrayHasKey($child->id, $options);
        $this->assertEquals('Parent', $options[$parent->id]);
        $this->assertEquals('— Child', $options[$child->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION TESTS
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function validate_business_rules_checks_max_level()
    {
        $category = Category::factory()->make(['level' => Category::MAX_LEVEL + 1]);
        
        $errors = $category->validateBusinessRules();
        
        $this->assertContains('Category level cannot exceed ' . Category::MAX_LEVEL, $errors);
    }

    /** @test */
    public function validate_business_rules_checks_empty_name()
    {
        $category = Category::factory()->make(['name' => '   ']);
        
        $errors = $category->validateBusinessRules();
        
        $this->assertContains('Category name is required', $errors);
    }

    /*
    |--------------------------------------------------------------------------
    | SLUG GENERATION TESTS
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function slug_is_auto_generated_on_create()
    {
        $category = Category::factory()->create([
            'name' => 'Test Category',
            'slug' => null
        ]);

        $this->assertNotNull($category->slug);
        $this->assertStringStartsWith('test-category', $category->slug);
    }

    /** @test */
    public function slug_generation_ensures_uniqueness()
    {
        Category::factory()->create(['slug' => 'same-slug']);

        $category2 = Category::factory()->create([
            'name' => 'Same Slug',
            'slug' => null
        ]);

        $this->assertNotEquals('same-slug', $category2->slug);
        $this->assertStringStartsWith('same-slug-', $category2->slug);
    }

    /*
    |--------------------------------------------------------------------------
    | SOFT DELETE TESTS
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function deleting_category_soft_deletes_descendants()
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);
        $grandchild = Category::factory()->create(['parent_id' => $child->id]);

        $parent->delete();

        $this->assertSoftDeleted('categories', ['id' => $parent->id]);
        $this->assertSoftDeleted('categories', ['id' => $child->id]);
        $this->assertSoftDeleted('categories', ['id' => $grandchild->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | ROUTE MODEL BINDING TESTS
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function route_key_name_is_slug()
    {
        $category = new Category();
        
        $this->assertEquals('slug', $category->getRouteKeyName());
    }

    /** @test */
    public function resolve_route_binding_finds_by_slug_first()
    {
        $category = Category::factory()->create(['slug' => 'test-category']);
        
        $resolved = (new Category())->resolveRouteBinding('test-category');
        
        $this->assertInstanceOf(Category::class, $resolved);
        $this->assertEquals($category->id, $resolved->id);
    }
}