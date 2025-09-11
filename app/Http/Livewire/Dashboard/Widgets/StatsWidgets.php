<?php

namespace App\Http\Livewire\Dashboard\Widgets;

use Livewire\Component;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use App\Models\ProductStock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatsWidgets extends Component
{
    public $refreshInterval = 30; // seconds
    public $showTrends = true;
    
    protected $listeners = [
        'refreshStats' => '$refresh',
        'toggleTrends' => 'toggleTrends'
    ];

    public function mount($refreshInterval = 30)
    {
        $this->refreshInterval = $refreshInterval;
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.stats-widgets', [
            'totalProducts' => $this->getTotalProducts(),
            'activeUsers' => $this->getActiveUsers(),
            'categoriesStats' => $this->getCategoriesStats(),
            'stockAlerts' => $this->getStockAlerts(),
            'todayActivity' => $this->getTodayActivity()
        ]);
    }

    public function toggleTrends()
    {
        $this->showTrends = !$this->showTrends;
    }

    protected function getTotalProducts()
    {
        return Cache::remember('widget_total_products', $this->refreshInterval, function () {
            $current = Product::count();
            $yesterday = Product::whereDate('created_at', '<', today())->count();
            $trend = $current - $yesterday;
            
            return [
                'total' => $current,
                'trend' => $trend,
                'trend_percentage' => $yesterday > 0 ? round(($trend / $yesterday) * 100, 1) : 0,
                'trend_direction' => $trend >= 0 ? 'up' : 'down'
            ];
        });
    }

    protected function getActiveUsers()
    {
        return Cache::remember('widget_active_users', $this->refreshInterval, function () {
            $today = User::whereDate('last_login_at', today())->count();
            $yesterday = User::whereDate('last_login_at', yesterday())->count();
            $thisWeek = User::where('last_login_at', '>=', now()->startOfWeek())->count();
            $total = User::count();
            
            return [
                'today' => $today,
                'yesterday' => $yesterday,
                'week' => $thisWeek,
                'total' => $total,
                'trend' => $today - $yesterday,
                'active_percentage' => $total > 0 ? round(($today / $total) * 100, 1) : 0
            ];
        });
    }

    protected function getCategoriesStats()
    {
        return Cache::remember('widget_categories_stats', $this->refreshInterval, function () {
            $totalCategories = Category::count();
            $categoriesWithProducts = Category::has('products')->count();
            $emptyCategories = $totalCategories - $categoriesWithProducts;
            
            // Top categories by product count
            $topCategories = Category::withCount('products')
                ->orderByDesc('products_count')
                ->limit(5)
                ->get()
                ->map(function ($category) {
                    return [
                        'name' => $category->name,
                        'count' => $category->products_count,
                        'percentage' => $category->products_count > 0 ? 
                            round(($category->products_count / Product::count()) * 100, 1) : 0
                    ];
                });

            return [
                'total' => $totalCategories,
                'with_products' => $categoriesWithProducts,
                'empty' => $emptyCategories,
                'utilization_rate' => $totalCategories > 0 ? 
                    round(($categoriesWithProducts / $totalCategories) * 100, 1) : 0,
                'top_categories' => $topCategories
            ];
        });
    }

    protected function getStockAlerts()
    {
        return Cache::remember('widget_stock_alerts', $this->refreshInterval, function () {
            // Products with low stock (less than 10 units)
            $lowStock = ProductStock::where('quantity', '<', 10)
                ->where('quantity', '>', 0)
                ->count();
                
            // Products out of stock
            $outOfStock = ProductStock::where('quantity', '<=', 0)->count();
            
            // Products with high stock (more than 1000 units)
            $overStocked = ProductStock::where('quantity', '>', 1000)->count();
            
            // Total stock value (mock calculation)
            $totalStockValue = ProductStock::sum('quantity');
            
            return [
                'low_stock' => $lowStock,
                'out_of_stock' => $outOfStock,
                'over_stocked' => $overStocked,
                'total_value' => $totalStockValue,
                'alerts_count' => $lowStock + $outOfStock,
                'status' => $outOfStock > 0 ? 'critical' : ($lowStock > 0 ? 'warning' : 'good')
            ];
        });
    }

    protected function getTodayActivity()
    {
        return Cache::remember('widget_today_activity', $this->refreshInterval, function () {
            $today = today();
            
            $productsAdded = Product::whereDate('created_at', $today)->count();
            $productsUpdated = Product::whereDate('updated_at', $today)
                ->where('created_at', '<', $today)
                ->count();
            
            $usersLoggedIn = User::whereDate('last_login_at', $today)->count();
            
            // Mock integration sync data
            $syncEvents = rand(15, 45);
            
            return [
                'products_added' => $productsAdded,
                'products_updated' => $productsUpdated,
                'users_logged_in' => $usersLoggedIn,
                'sync_events' => $syncEvents,
                'total_activity' => $productsAdded + $productsUpdated + $usersLoggedIn + $syncEvents
            ];
        });
    }
}