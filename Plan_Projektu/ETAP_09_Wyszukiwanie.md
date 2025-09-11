# ❌ ETAP 09: SYSTEM WYSZUKIWANIA

**Szacowany czas realizacji:** 35 godzin  
**Priorytet:** 🟢 ŚREDNI  
**Odpowiedzialny:** Claude Code AI + Kamil Wiliński  
**Wymagane zasoby:** Laravel Scout, MySQL Full-Text Search, Elasticsearch (opcjonalnie), Redis  

---

## 🎯 CEL ETAPU

Implementacja zaawansowanego, inteligentnego systemu wyszukiwania produktów z funkcjami autosugestii, tolerancji błędów, wielokryterialnego filtrowania i trybu dokładnego wyszukiwania. System musi być szybki, intuicyjny i dostosowany do różnych poziomów uprawnień użytkowników.

### Kluczowe rezultaty:
- ✅ Inteligentna wyszukiwarka z autosugestiami w czasie rzeczywistym
- ✅ Tolerancja błędów ortograficznych i literówek
- ✅ Wyszukiwanie wielokriteryczne (SKU, nazwa, kategoria, cechy)
- ✅ System filtrów zaawansowanych z faceted search
- ✅ Tryb dokładny i rozmyty wyszukiwania
- ✅ Wyszukiwanie pełnotekstowe w opisach produktów
- ✅ Historia i zapisane wyszukiwania użytkownika
- ✅ Wyszukiwanie głosowe (opcjonalnie)
- ✅ API search dla aplikacji zewnętrznych

---

## ❌ 9.1 ANALIZA I ARCHITEKTURA WYSZUKIWANIA

### ❌ 9.1.1 Wymagania funkcjonalne wyszukiwarki
#### ❌ 9.1.1.1 Analiza przypadków użycia
- ❌ 9.1.1.1.1 Szybkie wyszukiwanie po SKU (dokładne dopasowanie)
- ❌ 9.1.1.1.2 Wyszukiwanie po nazwie produktu (rozmyte dopasowanie)
- ❌ 9.1.1.1.3 Wyszukiwanie po kategorii i podkategorii
- ❌ 9.1.1.1.4 Wyszukiwanie po cechach i parametrach technicznych
- ❌ 9.1.1.1.5 Wyszukiwanie po opisie i słowach kluczowych

#### ❌ 9.1.1.2 Wymagania wydajnościowe
- ❌ 9.1.1.2.1 Czas odpowiedzi < 200ms dla prostych zapytań
- ❌ 9.1.1.2.2 Czas odpowiedzi < 500ms dla złożonych zapytań
- ❌ 9.1.1.2.3 Obsługa 100+ jednoczesnych wyszukiwań
- ❌ 9.1.1.2.4 Autocomplete < 100ms
- ❌ 9.1.1.2.5 Indeksowanie w czasie rzeczywistym

#### ❌ 9.1.1.3 Wymagania bezpieczeństwa i uprawnień
- ❌ 9.1.1.3.1 Filtrowanie wyników według uprawnień użytkownika
- ❌ 9.1.1.3.2 Ukrywanie cen dla nieuprawnionych użytkowników
- ❌ 9.1.1.3.3 Logowanie zapytań wyszukiwania
- ❌ 9.1.1.3.4 Rate limiting dla API search
- ❌ 9.1.1.3.5 Walidacja i sanityzacja inputów

### ❌ 9.1.2 Wybór technologii wyszukiwania
#### ❌ 9.1.2.1 MySQL Full-Text Search (podstawowy)
- ❌ 9.1.2.1.1 Konfiguracja full-text indexes
- ❌ 9.1.2.1.2 MATCH() AGAINST() queries
- ❌ 9.1.2.1.3 Boolean mode vs Natural language
- ❌ 9.1.2.1.4 Minimum word length i stop words
- ❌ 9.1.2.1.5 Relevance scoring

#### ❌ 9.1.2.2 Laravel Scout z database driver
- ❌ 9.1.2.2.1 Model searchable configuration
- ❌ 9.1.2.2.2 Custom search engine setup
- ❌ 9.1.2.2.3 Search index maintenance
- ❌ 9.1.2.2.4 Pagination i sorting
- ❌ 9.1.2.2.5 Search query builders

#### ❌ 9.1.2.3 Redis dla cache i autosugestii
- ❌ 9.1.2.3.1 Sorted sets dla popularnych zapytań
- ❌ 9.1.2.3.2 Hash tables dla cache wyników
- ❌ 9.1.2.3.3 TTL management dla cache
- ❌ 9.1.2.3.4 Memory optimization
- ❌ 9.1.2.3.5 Cache invalidation strategies

### ❌ 9.1.3 Architektura systemu wyszukiwania
#### ❌ 9.1.3.1 Search Service Layer
- ❌ 9.1.3.1.1 ProductSearchService - główny serwis wyszukiwania
- ❌ 9.1.3.1.2 AutocompleteService - serwis autosugestii
- ❌ 9.1.3.1.3 SearchQueryParser - parser zapytań
- ❌ 9.1.3.1.4 SearchResultFormatter - formatowanie wyników
- ❌ 9.1.3.1.5 SearchAnalyticsService - analityka wyszukiwań

#### ❌ 9.1.3.2 Search Strategies Pattern
- ❌ 9.1.3.2.1 ExactSearchStrategy - dokładne wyszukiwanie
- ❌ 9.1.3.2.2 FuzzySearchStrategy - rozmyte wyszukiwanie
- ❌ 9.1.3.2.3 FullTextSearchStrategy - pełnotekstowe wyszukiwanie
- ❌ 9.1.3.2.4 CategorySearchStrategy - wyszukiwanie po kategoriach
- ❌ 9.1.3.2.5 AttributeSearchStrategy - wyszukiwanie po cechach

#### ❌ 9.1.3.3 Search Index Management
- ❌ 9.1.3.3.1 SearchIndexManager - zarządzanie indeksami
- ❌ 9.1.3.3.2 IndexUpdateJob - aktualizacje indeksów
- ❌ 9.1.3.3.3 IndexRebuildCommand - przebudowa indeksów
- ❌ 9.1.3.3.4 IndexHealthCheck - kontrola stanu indeksów
- ❌ 9.1.3.3.5 IndexOptimization - optymalizacja indeksów

---

## ❌ 9.2 MODELE I MIGRACJE WYSZUKIWANIA

### ❌ 9.2.1 Tabele wyszukiwania i indeksów
#### ❌ 9.2.1.1 Tabela search_indexes
```sql
CREATE TABLE search_indexes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    indexable_type VARCHAR(255) NOT NULL, -- Product, Category, etc.
    indexable_id BIGINT UNSIGNED NOT NULL,
    
    -- Search data
    search_content LONGTEXT NOT NULL, -- Combined searchable content
    keywords TEXT NULL, -- Additional keywords
    search_vector TEXT NULL, -- For advanced search algorithms
    
    -- Metadata
    language VARCHAR(10) DEFAULT 'pl',
    boost_factor DECIMAL(3,2) DEFAULT 1.00, -- Search ranking boost
    is_featured BOOLEAN DEFAULT FALSE,
    
    -- Timestamps
    last_indexed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_indexable (indexable_type, indexable_id),
    FULLTEXT idx_search_content (search_content),
    FULLTEXT idx_keywords (keywords),
    FULLTEXT idx_combined_search (search_content, keywords),
    INDEX idx_boost_featured (boost_factor, is_featured),
    INDEX idx_last_indexed (last_indexed_at)
);
```

#### ❌ 9.2.1.2 Tabela search_queries
```sql
CREATE TABLE search_queries (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    session_id VARCHAR(255) NULL,
    
    -- Query data
    query VARCHAR(500) NOT NULL,
    normalized_query VARCHAR(500) NOT NULL, -- Lowercased, trimmed, normalized
    query_hash VARCHAR(64) NOT NULL, -- MD5 hash for quick lookups
    
    -- Context
    search_type ENUM('exact', 'fuzzy', 'fulltext', 'autocomplete') DEFAULT 'fuzzy',
    filters JSON NULL, -- Applied filters
    sort_by VARCHAR(100) NULL,
    sort_direction ENUM('asc', 'desc') DEFAULT 'asc',
    
    -- Results
    results_count INT UNSIGNED DEFAULT 0,
    execution_time_ms INT UNSIGNED NULL,
    found_exact_match BOOLEAN DEFAULT FALSE,
    
    -- User context
    ip_address INET6 NULL,
    user_agent TEXT NULL,
    referer VARCHAR(500) NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_queries (user_id, created_at),
    INDEX idx_session_queries (session_id, created_at),
    INDEX idx_query_hash (query_hash),
    INDEX idx_popular_queries (normalized_query, results_count),
    INDEX idx_execution_time (execution_time_ms),
    FULLTEXT idx_query_search (query, normalized_query)
);
```

#### ❌ 9.2.1.3 Tabela search_suggestions
```sql
CREATE TABLE search_suggestions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    suggestion VARCHAR(255) NOT NULL,
    normalized_suggestion VARCHAR(255) NOT NULL,
    suggestion_type ENUM('product', 'category', 'brand', 'keyword') NOT NULL,
    
    -- Popularity metrics
    search_count INT UNSIGNED DEFAULT 0,
    success_rate DECIMAL(5,2) DEFAULT 0.00, -- % queries that returned results
    last_searched_at TIMESTAMP NULL,
    
    -- Suggestion source
    source_type ENUM('product_name', 'category_name', 'attribute_value', 'manual') DEFAULT 'product_name',
    source_id BIGINT UNSIGNED NULL,
    
    -- Metadata
    language VARCHAR(10) DEFAULT 'pl',
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    boost_score INT DEFAULT 0, -- Higher = more prominent in suggestions
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_suggestion (normalized_suggestion, suggestion_type),
    INDEX idx_suggestion_type (suggestion_type, is_active),
    INDEX idx_popularity (search_count, success_rate),
    INDEX idx_featured (is_featured, boost_score),
    INDEX idx_last_searched (last_searched_at),
    INDEX idx_source (source_type, source_id),
    FULLTEXT idx_suggestion_search (suggestion, normalized_suggestion)
);
```

### ❌ 9.2.2 Tabele filtrów i faceted search
#### ❌ 9.2.2.1 Tabela search_filters
```sql
CREATE TABLE search_filters (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    label VARCHAR(255) NOT NULL,
    filter_type ENUM('range', 'select', 'multiselect', 'checkbox', 'text', 'date_range') NOT NULL,
    
    -- Filter configuration
    data_source ENUM('product_field', 'product_attribute', 'category', 'custom') NOT NULL,
    source_field VARCHAR(255) NULL, -- Database field or attribute code
    
    -- UI configuration
    display_order INT DEFAULT 0,
    is_collapsible BOOLEAN DEFAULT TRUE,
    is_expanded_default BOOLEAN DEFAULT FALSE,
    show_count BOOLEAN DEFAULT TRUE, -- Show result count per option
    
    -- Permissions
    required_permission VARCHAR(255) NULL, -- Required permission to see filter
    user_roles JSON NULL, -- Specific roles that can use this filter
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_filter_name (name),
    INDEX idx_active_order (is_active, display_order),
    INDEX idx_data_source (data_source, source_field)
);
```

#### ❌ 9.2.2.2 Tabela search_filter_options
```sql
CREATE TABLE search_filter_options (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    filter_id BIGINT UNSIGNED NOT NULL,
    
    value VARCHAR(255) NOT NULL,
    label VARCHAR(255) NOT NULL,
    
    -- Metadata
    result_count INT UNSIGNED DEFAULT 0, -- How many products match this option
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    
    -- Range filters (for numeric/date ranges)
    min_value DECIMAL(15,4) NULL,
    max_value DECIMAL(15,4) NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (filter_id) REFERENCES search_filters(id) ON DELETE CASCADE,
    UNIQUE KEY unique_filter_option (filter_id, value),
    INDEX idx_filter_active (filter_id, is_active, display_order),
    INDEX idx_result_count (result_count)
);
```

---

## ❌ 9.3 SEARCH SERVICE LAYER

### ❌ 9.3.1 ProductSearchService - główny serwis wyszukiwania
#### ❌ 9.3.1.1 Klasa ProductSearchService
```php
<?php
namespace App\Services\Search;

use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\SearchIndex;
use App\Services\Search\Strategies\SearchStrategyInterface;
use App\Services\Search\Strategies\ExactSearchStrategy;
use App\Services\Search\Strategies\FuzzySearchStrategy;
use App\Services\Search\Strategies\FullTextSearchStrategy;
use App\Services\Search\QueryParser\SearchQueryParser;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductSearchService
{
    protected SearchQueryParser $queryParser;
    protected array $strategies = [];
    protected array $defaultStrategies = [
        'exact' => ExactSearchStrategy::class,
        'fuzzy' => FuzzySearchStrategy::class,
        'fulltext' => FullTextSearchStrategy::class
    ];
    
    public function __construct(SearchQueryParser $queryParser)
    {
        $this->queryParser = $queryParser;
        $this->loadStrategies();
    }
    
    public function search(string $query, array $filters = [], array $options = []): array
    {
        $startTime = microtime(true);
        
        // Parse and normalize query
        $parsedQuery = $this->queryParser->parse($query);
        $normalizedQuery = $this->queryParser->normalize($query);
        
        // Determine search strategy
        $strategy = $this->determineStrategy($parsedQuery, $options);
        
        // Build cache key
        $cacheKey = $this->buildCacheKey($normalizedQuery, $filters, $options);
        
        // Try to get from cache first
        if ($options['use_cache'] ?? true) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                $this->logSearch($query, $normalizedQuery, $cached['total'], microtime(true) - $startTime, true);
                return $cached;
            }
        }
        
        // Execute search
        $results = $strategy->search($parsedQuery, $filters, $options);
        
        // Apply security filters
        $results = $this->applySecurityFilters($results, auth()->user());
        
        // Format results
        $formattedResults = $this->formatResults($results, $options);
        
        // Cache results
        if ($options['use_cache'] ?? true) {
            Cache::put($cacheKey, $formattedResults, now()->addMinutes(15));
        }
        
        // Log search
        $executionTime = microtime(true) - $startTime;
        $this->logSearch($query, $normalizedQuery, $formattedResults['total'], $executionTime);
        
        return $formattedResults;
    }
    
    protected function determineStrategy($parsedQuery, array $options): SearchStrategyInterface
    {
        // User explicitly specified strategy
        if (!empty($options['strategy']) && isset($this->strategies[$options['strategy']])) {
            return $this->strategies[$options['strategy']];
        }
        
        // Auto-detect based on query characteristics
        if ($parsedQuery['is_sku']) {
            return $this->strategies['exact'];
        }
        
        if ($parsedQuery['is_quoted'] || $parsedQuery['has_operators']) {
            return $this->strategies['fulltext'];
        }
        
        if (strlen($parsedQuery['clean_query']) < 3) {
            return $this->strategies['exact'];
        }
        
        // Default to fuzzy search
        return $this->strategies['fuzzy'];
    }
    
    protected function applySecurityFilters($results, $user)
    {
        if (!$user) {
            // Anonymous users - filter out non-public products
            return $results->where('is_public', true);
        }
        
        // Apply role-based filtering
        $userRoles = $user->roles->pluck('name')->toArray();
        
        if (in_array('admin', $userRoles) || in_array('manager', $userRoles)) {
            return $results; // No filtering for admin/manager
        }
        
        // Other roles see only active products
        return $results->where('is_active', true);
    }
    
    protected function formatResults($results, array $options): array
    {
        $perPage = $options['per_page'] ?? 20;
        $page = $options['page'] ?? 1;
        
        // Get total count
        $total = $results->count();
        
        // Paginate results
        $paginatedResults = $results->forPage($page, $perPage);
        
        // Load necessary relationships
        $paginatedResults->load([
            'category',
            'images' => function ($query) {
                $query->where('is_main', true)->first();
            },
            'prices' => function ($query) use ($options) {
                if (!empty($options['price_group'])) {
                    $query->where('price_group', $options['price_group']);
                }
            }
        ]);
        
        return [
            'results' => $paginatedResults->values()->toArray(),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'facets' => $this->buildFacets($results, $options),
            'suggestions' => $this->getSuggestions($options['original_query'] ?? '', $total)
        ];
    }
    
    protected function buildFacets($results, array $options): array
    {
        // Build faceted search data
        $facets = [];
        
        // Categories facet
        $facets['categories'] = $results->groupBy('category_id')
            ->map(function ($products, $categoryId) {
                return [
                    'id' => $categoryId,
                    'name' => $products->first()->category->name ?? 'Unknown',
                    'count' => $products->count()
                ];
            })->values()->toArray();
        
        // Price ranges facet
        $prices = $results->pluck('prices')->flatten()->pluck('price')->filter();
        if ($prices->count() > 0) {
            $minPrice = $prices->min();
            $maxPrice = $prices->max();
            
            $facets['price_ranges'] = $this->buildPriceRanges($minPrice, $maxPrice, $results);
        }
        
        // Brands facet (if brand attribute exists)
        $brands = $results->flatMap(function ($product) {
            return $product->attributeValues->where('attribute.code', 'brand');
        });
        
        if ($brands->count() > 0) {
            $facets['brands'] = $brands->groupBy('value')
                ->map(function ($items, $brand) {
                    return [
                        'name' => $brand,
                        'count' => $items->count()
                    ];
                })->values()->toArray();
        }
        
        return $facets;
    }
    
    protected function getSuggestions(string $query, int $resultCount): array
    {
        // If search returned 0 results, provide suggestions
        if ($resultCount === 0 && strlen($query) > 2) {
            return app(AutocompleteService::class)->getSuggestions($query, 5);
        }
        
        return [];
    }
    
    protected function buildCacheKey(string $query, array $filters, array $options): string
    {
        $keyData = [
            'query' => $query,
            'filters' => $filters,
            'options' => array_intersect_key($options, array_flip(['strategy', 'per_page', 'page', 'sort'])),
            'user_id' => auth()->id(),
            'roles' => auth()->user()?->roles->pluck('name')->toArray() ?? []
        ];
        
        return 'search_' . md5(json_encode($keyData));
    }
    
    protected function logSearch(string $query, string $normalizedQuery, int $resultCount, float $executionTime, bool $fromCache = false): void
    {
        try {
            SearchQuery::create([
                'user_id' => auth()->id(),
                'session_id' => session()->getId(),
                'query' => $query,
                'normalized_query' => $normalizedQuery,
                'query_hash' => md5($normalizedQuery),
                'results_count' => $resultCount,
                'execution_time_ms' => round($executionTime * 1000),
                'found_exact_match' => $resultCount === 1,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'referer' => request()->header('referer')
            ]);
            
            Log::channel('search')->info('Search executed', [
                'query' => $query,
                'results' => $resultCount,
                'time_ms' => round($executionTime * 1000),
                'from_cache' => $fromCache,
                'user_id' => auth()->id()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to log search', ['error' => $e->getMessage()]);
        }
    }
    
    protected function loadStrategies(): void
    {
        foreach ($this->defaultStrategies as $name => $class) {
            $this->strategies[$name] = app($class);
        }
    }
}
```

### ❌ 9.3.2 AutocompleteService - serwis autosugestii
#### ❌ 9.3.2.1 Klasa AutocompleteService
```php
<?php
namespace App\Services\Search;

use App\Models\SearchSuggestion;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AutocompleteService
{
    protected int $maxSuggestions = 10;
    protected array $suggestionTypes = ['product', 'category', 'brand'];
    
    public function getSuggestions(string $query, int $limit = null): array
    {
        $limit = $limit ?? $this->maxSuggestions;
        $normalizedQuery = $this->normalizeQuery($query);
        
        if (strlen($normalizedQuery) < 2) {
            return [];
        }
        
        $cacheKey = "autocomplete_{$normalizedQuery}_{$limit}";
        
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($normalizedQuery, $limit) {
            return $this->buildSuggestions($normalizedQuery, $limit);
        });
    }
    
    protected function buildSuggestions(string $query, int $limit): array
    {
        $suggestions = [];
        
        // Get stored suggestions first (most relevant)
        $storedSuggestions = $this->getStoredSuggestions($query, $limit);
        $suggestions = array_merge($suggestions, $storedSuggestions);
        
        $remaining = $limit - count($suggestions);
        
        if ($remaining > 0) {
            // Get product name suggestions
            $productSuggestions = $this->getProductSuggestions($query, $remaining);
            $suggestions = array_merge($suggestions, $productSuggestions);
            
            $remaining = $limit - count($suggestions);
        }
        
        if ($remaining > 0) {
            // Get category suggestions
            $categorySuggestions = $this->getCategorySuggestions($query, $remaining);
            $suggestions = array_merge($suggestions, $categorySuggestions);
        }
        
        // Remove duplicates and limit
        $unique = collect($suggestions)->unique('suggestion')->take($limit)->values();
        
        return $unique->toArray();
    }
    
    protected function getStoredSuggestions(string $query, int $limit): array
    {
        return SearchSuggestion::where('normalized_suggestion', 'LIKE', $query . '%')
            ->where('is_active', true)
            ->orderByDesc('search_count')
            ->orderByDesc('success_rate')
            ->orderByDesc('boost_score')
            ->limit($limit)
            ->get()
            ->map(function ($suggestion) {
                return [
                    'suggestion' => $suggestion->suggestion,
                    'type' => $suggestion->suggestion_type,
                    'popularity' => $suggestion->search_count,
                    'success_rate' => $suggestion->success_rate
                ];
            })
            ->toArray();
    }
    
    protected function getProductSuggestions(string $query, int $limit): array
    {
        $products = Product::select(['name', 'sku'])
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', '%' . $query . '%')
                  ->orWhere('sku', 'LIKE', $query . '%');
            })
            ->where('is_active', true)
            ->limit($limit * 2) // Get more to filter out duplicates
            ->get();
            
        return $products->map(function ($product) {
            return [
                'suggestion' => $product->name,
                'type' => 'product',
                'sku' => $product->sku,
                'popularity' => 0
            ];
        })->unique('suggestion')->take($limit)->values()->toArray();
    }
    
    protected function getCategorySuggestions(string $query, int $limit): array
    {
        $categories = Category::select(['name'])
            ->where('name', 'LIKE', '%' . $query . '%')
            ->where('is_active', true)
            ->limit($limit)
            ->get();
            
        return $categories->map(function ($category) {
            return [
                'suggestion' => $category->name,
                'type' => 'category',
                'popularity' => 0
            ];
        })->toArray();
    }
    
    public function recordSuggestionUsage(string $suggestion, bool $hadResults): void
    {
        $normalizedSuggestion = $this->normalizeQuery($suggestion);
        
        SearchSuggestion::updateOrCreate(
            [
                'normalized_suggestion' => $normalizedSuggestion,
                'suggestion_type' => 'keyword'
            ],
            [
                'suggestion' => $suggestion,
                'source_type' => 'manual'
            ]
        )->increment('search_count');
        
        if ($hadResults) {
            // Update success rate
            $suggestion = SearchSuggestion::where('normalized_suggestion', $normalizedSuggestion)
                ->where('suggestion_type', 'keyword')
                ->first();
                
            if ($suggestion) {
                $successfulSearches = floor($suggestion->search_count * $suggestion->success_rate / 100) + 1;
                $newSuccessRate = ($successfulSearches / $suggestion->search_count) * 100;
                $suggestion->update(['success_rate' => $newSuccessRate]);
            }
        }
    }
    
    protected function normalizeQuery(string $query): string
    {
        return mb_strtolower(trim($query));
    }
}
```

---

## ❌ 9.4 SEARCH STRATEGIES

### ❌ 9.4.1 SearchStrategyInterface
#### ❌ 9.4.1.1 Interface dla strategii wyszukiwania
```php
<?php
namespace App\Services\Search\Strategies;

use Illuminate\Database\Eloquent\Collection;

interface SearchStrategyInterface
{
    public function search(array $parsedQuery, array $filters = [], array $options = []): Collection;
    public function supports(array $parsedQuery): bool;
    public function getName(): string;
}
```

### ❌ 9.4.2 ExactSearchStrategy
#### ❌ 9.4.2.1 Dokładne wyszukiwanie (SKU, kody)
```php
<?php
namespace App\Services\Search\Strategies;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ExactSearchStrategy implements SearchStrategyInterface
{
    public function search(array $parsedQuery, array $filters = [], array $options = []): Collection
    {
        $query = Product::query();
        
        $searchTerm = $parsedQuery['clean_query'];
        
        // Search by SKU first (most common exact search)
        $query->where(function ($q) use ($searchTerm) {
            $q->where('sku', $searchTerm)
              ->orWhere('ean', $searchTerm)
              ->orWhere('manufacturer_code', $searchTerm)
              ->orWhere('name', $searchTerm);
        });
        
        // Apply filters
        $this->applyFilters($query, $filters);
        
        // Apply sorting
        $this->applySorting($query, $options);
        
        return $query->get();
    }
    
    public function supports(array $parsedQuery): bool
    {
        return $parsedQuery['is_sku'] || 
               $parsedQuery['is_code'] || 
               strlen($parsedQuery['clean_query']) < 3;
    }
    
    public function getName(): string
    {
        return 'exact';
    }
    
    protected function applyFilters($query, array $filters): void
    {
        if (!empty($filters['categories'])) {
            $query->whereIn('category_id', $filters['categories']);
        }
        
        if (!empty($filters['price_from'])) {
            $query->whereHas('prices', function ($q) use ($filters) {
                $q->where('price', '>=', $filters['price_from']);
            });
        }
        
        if (!empty($filters['price_to'])) {
            $query->whereHas('prices', function ($q) use ($filters) {
                $q->where('price', '<=', $filters['price_to']);
            });
        }
        
        if (!empty($filters['in_stock'])) {
            $query->whereHas('stock', function ($q) {
                $q->where('quantity', '>', 0);
            });
        }
        
        if (!empty($filters['brands'])) {
            $query->whereHas('attributeValues', function ($q) use ($filters) {
                $q->whereHas('attribute', function ($attr) {
                    $attr->where('code', 'brand');
                })->whereIn('value', $filters['brands']);
            });
        }
    }
    
    protected function applySorting($query, array $options): void
    {
        $sortBy = $options['sort_by'] ?? 'relevance';
        $sortDirection = $options['sort_direction'] ?? 'asc';
        
        switch ($sortBy) {
            case 'name':
                $query->orderBy('name', $sortDirection);
                break;
            case 'price':
                $query->leftJoin('product_prices', 'products.id', '=', 'product_prices.product_id')
                      ->orderBy('product_prices.price', $sortDirection);
                break;
            case 'created_at':
                $query->orderBy('created_at', $sortDirection);
                break;
            case 'sku':
                $query->orderBy('sku', $sortDirection);
                break;
            default: // relevance
                $query->orderByDesc('is_featured')
                      ->orderBy('name');
        }
    }
}
```

### ❌ 9.4.3 FuzzySearchStrategy
#### ❌ 9.4.3.1 Rozmyte wyszukiwanie z tolerancją błędów
```php
<?php
namespace App\Services\Search\Strategies;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FuzzySearchStrategy implements SearchStrategyInterface
{
    protected array $commonTypos = [
        'th' => 't',
        'ph' => 'f',
        'ck' => 'k',
        'qu' => 'kw'
    ];
    
    public function search(array $parsedQuery, array $filters = [], array $options = []): Collection
    {
        $searchTerm = $parsedQuery['clean_query'];
        $searchWords = $parsedQuery['words'];
        
        $query = Product::query();
        
        // Multi-tier fuzzy search
        $this->buildFuzzyQuery($query, $searchTerm, $searchWords);
        
        // Apply filters
        $this->applyFilters($query, $filters);
        
        // Order by relevance
        $this->applyRelevanceOrdering($query, $searchWords);
        
        return $query->get();
    }
    
    protected function buildFuzzyQuery($query, string $searchTerm, array $words): void
    {
        $query->where(function ($mainQuery) use ($searchTerm, $words) {
            // Tier 1: Exact substring match (highest priority)
            $mainQuery->orWhere('name', 'LIKE', '%' . $searchTerm . '%')
                     ->orWhere('description', 'LIKE', '%' . $searchTerm . '%');
            
            // Tier 2: All words must be present
            if (count($words) > 1) {
                $mainQuery->orWhere(function ($allWordsQuery) use ($words) {
                    foreach ($words as $word) {
                        $allWordsQuery->where(function ($wordQuery) use ($word) {
                            $wordQuery->where('name', 'LIKE', '%' . $word . '%')
                                     ->orWhere('description', 'LIKE', '%' . $word . '%');
                        });
                    }
                });
            }
            
            // Tier 3: Any word match
            $mainQuery->orWhere(function ($anyWordQuery) use ($words) {
                foreach ($words as $word) {
                    $anyWordQuery->orWhere('name', 'LIKE', '%' . $word . '%')
                                ->orWhere('description', 'LIKE', '%' . $word . '%');
                }
            });
            
            // Tier 4: Soundex matching (for Polish phonetic similarity)
            $mainQuery->orWhere(function ($soundexQuery) use ($words) {
                foreach ($words as $word) {
                    $soundexQuery->orWhereRaw('SOUNDEX(name) = SOUNDEX(?)', [$word]);
                }
            });
            
            // Tier 5: Levenshtein distance (for typos)
            $this->addLevenshteinMatching($mainQuery, $searchTerm);
        });
    }
    
    protected function addLevenshteinMatching($query, string $searchTerm): void
    {
        // For MySQL, we'll use a simpler approach since LEVENSHTEIN is not built-in
        // Generate common typo variations
        $variations = $this->generateTypoVariations($searchTerm);
        
        if (!empty($variations)) {
            $query->orWhere(function ($typoQuery) use ($variations) {
                foreach ($variations as $variation) {
                    $typoQuery->orWhere('name', 'LIKE', '%' . $variation . '%');
                }
            });
        }
    }
    
    protected function generateTypoVariations(string $term): array
    {
        $variations = [];
        
        // Apply common typo corrections
        foreach ($this->commonTypos as $wrong => $correct) {
            if (strpos($term, $wrong) !== false) {
                $variations[] = str_replace($wrong, $correct, $term);
            }
            if (strpos($term, $correct) !== false) {
                $variations[] = str_replace($correct, $wrong, $term);
            }
        }
        
        // Character substitution (simple approach)
        $polishChars = [
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l',
            'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z'
        ];
        
        foreach ($polishChars as $polish => $latin) {
            if (strpos($term, $polish) !== false) {
                $variations[] = str_replace($polish, $latin, $term);
            }
            if (strpos($term, $latin) !== false) {
                $variations[] = str_replace($latin, $polish, $term);
            }
        }
        
        return array_unique($variations);
    }
    
    protected function applyRelevanceOrdering($query, array $words): void
    {
        // Create relevance score using SQL CASE statements
        $relevanceSelect = 'products.*, (CASE ';
        
        // Exact name match = highest score
        $relevanceSelect .= 'WHEN name = "' . implode(' ', $words) . '" THEN 1000 ';
        
        // Name starts with search term = high score  
        $relevanceSelect .= 'WHEN name LIKE "' . implode(' ', $words) . '%" THEN 800 ';
        
        // All words in name = medium-high score
        foreach ($words as $index => $word) {
            $relevanceSelect .= 'WHEN name LIKE "%' . $word . '%" THEN ' . (600 - $index * 50) . ' ';
        }
        
        // Featured products get bonus
        $relevanceSelect .= 'WHEN is_featured = 1 THEN 100 ';
        
        $relevanceSelect .= 'ELSE 1 END) as relevance_score';
        
        $query->selectRaw($relevanceSelect)
              ->orderByDesc('relevance_score')
              ->orderBy('name');
    }
    
    public function supports(array $parsedQuery): bool
    {
        return !$parsedQuery['is_sku'] && 
               !$parsedQuery['has_operators'] &&
               strlen($parsedQuery['clean_query']) >= 3;
    }
    
    public function getName(): string
    {
        return 'fuzzy';
    }
    
    protected function applyFilters($query, array $filters): void
    {
        // Same as ExactSearchStrategy - could be extracted to a trait
        if (!empty($filters['categories'])) {
            $query->whereIn('category_id', $filters['categories']);
        }
        
        if (!empty($filters['price_from'])) {
            $query->whereHas('prices', function ($q) use ($filters) {
                $q->where('price', '>=', $filters['price_from']);
            });
        }
        
        // ... other filters
    }
}
```

---

## ❌ 9.5 QUERY PARSER I SEARCH ANALYZER

### ❌ 9.5.1 SearchQueryParser
#### ❌ 9.5.1.1 Parser zapytań wyszukiwania
```php
<?php
namespace App\Services\Search\QueryParser;

class SearchQueryParser
{
    protected array $stopWords = ['i', 'a', 'w', 'z', 'na', 'do', 'od', 'po', 'dla'];
    protected array $skuPatterns = [
        '/^[A-Z0-9\-_]{5,}$/',  // Typical SKU pattern
        '/^[0-9]{8,13}$/',      // EAN/UPC pattern
        '/^\d{2,4}[A-Z]{2,4}\d{2,6}$/' // Mixed pattern
    ];
    
    public function parse(string $query): array
    {
        $normalizedQuery = $this->normalize($query);
        
        return [
            'original' => $query,
            'normalized' => $normalizedQuery,
            'clean_query' => $this->removeStopWords($normalizedQuery),
            'words' => $this->extractWords($normalizedQuery),
            'is_quoted' => $this->isQuoted($query),
            'is_sku' => $this->isSKU($normalizedQuery),
            'is_code' => $this->isProductCode($normalizedQuery),
            'has_operators' => $this->hasSearchOperators($query),
            'operators' => $this->extractOperators($query),
            'filters' => $this->extractInlineFilters($query)
        ];
    }
    
    public function normalize(string $query): string
    {
        // Convert to lowercase
        $normalized = mb_strtolower($query);
        
        // Remove extra whitespace
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        
        // Trim
        $normalized = trim($normalized);
        
        // Convert Polish characters for better matching
        $polishChars = [
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l',
            'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z'
        ];
        
        return strtr($normalized, $polishChars);
    }
    
    protected function removeStopWords(string $query): string
    {
        $words = explode(' ', $query);
        $filteredWords = array_filter($words, function ($word) {
            return !in_array($word, $this->stopWords) && strlen($word) > 1;
        });
        
        return implode(' ', $filteredWords);
    }
    
    protected function extractWords(string $query): array
    {
        return array_filter(explode(' ', $query), function ($word) {
            return strlen($word) > 1;
        });
    }
    
    protected function isQuoted(string $query): bool
    {
        return (str_starts_with($query, '"') && str_ends_with($query, '"')) ||
               (str_starts_with($query, "'") && str_ends_with($query, "'"));
    }
    
    protected function isSKU(string $query): bool
    {
        foreach ($this->skuPatterns as $pattern) {
            if (preg_match($pattern, $query)) {
                return true;
            }
        }
        
        return false;
    }
    
    protected function isProductCode(string $query): bool
    {
        // Check if it looks like a manufacturer code or EAN
        return preg_match('/^[A-Z0-9\-\.]{4,}$/', strtoupper($query)) ||
               preg_match('/^\d{8,13}$/', $query);
    }
    
    protected function hasSearchOperators(string $query): bool
    {
        return strpos($query, '+') !== false ||
               strpos($query, '-') !== false ||
               strpos($query, '*') !== false ||
               strpos($query, 'AND') !== false ||
               strpos($query, 'OR') !== false ||
               strpos($query, 'NOT') !== false;
    }
    
    protected function extractOperators(string $query): array
    {
        $operators = [];
        
        if (strpos($query, '+') !== false) {
            $operators['required'] = true;
        }
        
        if (strpos($query, '-') !== false) {
            $operators['excluded'] = true;
        }
        
        if (strpos($query, '*') !== false) {
            $operators['wildcard'] = true;
        }
        
        return $operators;
    }
    
    protected function extractInlineFilters(string $query): array
    {
        $filters = [];
        
        // Extract category filters: category:electronics
        if (preg_match('/category:(\w+)/', $query, $matches)) {
            $filters['category'] = $matches[1];
        }
        
        // Extract price filters: price:100-500
        if (preg_match('/price:(\d+)-(\d+)/', $query, $matches)) {
            $filters['price_from'] = $matches[1];
            $filters['price_to'] = $matches[2];
        }
        
        // Extract brand filters: brand:honda
        if (preg_match('/brand:(\w+)/', $query, $matches)) {
            $filters['brand'] = $matches[1];
        }
        
        return $filters;
    }
}
```

---

## ❌ 9.6 LIVEWIRE SEARCH COMPONENTS

### ❌ 9.6.1 SearchComponent
#### ❌ 9.6.1.1 Główny komponent wyszukiwania
```php
<?php
namespace App\Livewire\Search;

use App\Services\Search\ProductSearchService;
use App\Services\Search\AutocompleteService;
use Livewire\Component;
use Livewire\WithPagination;

class SearchComponent extends Component
{
    use WithPagination;
    
    public string $query = '';
    public array $filters = [];
    public string $sortBy = 'relevance';
    public string $sortDirection = 'desc';
    public string $searchMode = 'fuzzy'; // exact, fuzzy, fulltext
    public int $perPage = 20;
    
    public array $suggestions = [];
    public bool $showSuggestions = false;
    public array $searchResults = [];
    public int $totalResults = 0;
    public bool $isSearching = false;
    
    protected $queryString = [
        'query' => ['except' => ''],
        'filters' => ['except' => []],
        'sortBy' => ['except' => 'relevance'],
        'sortDirection' => ['except' => 'desc'],
        'searchMode' => ['except' => 'fuzzy']
    ];
    
    public function render()
    {
        return view('livewire.search.search-component');
    }
    
    public function updatedQuery()
    {
        if (strlen($this->query) >= 2) {
            $this->getSuggestions();
        } else {
            $this->suggestions = [];
            $this->showSuggestions = false;
        }
        
        // Reset pagination when query changes
        $this->resetPage();
    }
    
    public function getSuggestions()
    {
        $autocompleteService = app(AutocompleteService::class);
        $this->suggestions = $autocompleteService->getSuggestions($this->query, 8);
        $this->showSuggestions = !empty($this->suggestions);
    }
    
    public function selectSuggestion(string $suggestion)
    {
        $this->query = $suggestion;
        $this->showSuggestions = false;
        $this->search();
    }
    
    public function search()
    {
        if (empty($this->query)) {
            $this->searchResults = [];
            $this->totalResults = 0;
            return;
        }
        
        $this->isSearching = true;
        $this->showSuggestions = false;
        
        try {
            $searchService = app(ProductSearchService::class);
            
            $options = [
                'strategy' => $this->searchMode,
                'page' => $this->getPage(),
                'per_page' => $this->perPage,
                'sort_by' => $this->sortBy,
                'sort_direction' => $this->sortDirection,
                'original_query' => $this->query
            ];
            
            $results = $searchService->search($this->query, $this->filters, $options);
            
            $this->searchResults = $results['results'];
            $this->totalResults = $results['total'];
            
            // Update suggestions based on search success
            $autocompleteService = app(AutocompleteService::class);
            $autocompleteService->recordSuggestionUsage($this->query, $this->totalResults > 0);
            
        } catch (\Exception $e) {
            $this->addError('search', 'Wystąpił błąd podczas wyszukiwania: ' . $e->getMessage());
        } finally {
            $this->isSearching = false;
        }
    }
    
    public function addFilter(string $type, $value)
    {
        if (!isset($this->filters[$type])) {
            $this->filters[$type] = [];
        }
        
        if (!in_array($value, $this->filters[$type])) {
            $this->filters[$type][] = $value;
            $this->resetPage();
            $this->search();
        }
    }
    
    public function removeFilter(string $type, $value = null)
    {
        if ($value === null) {
            unset($this->filters[$type]);
        } else {
            if (isset($this->filters[$type])) {
                $this->filters[$type] = array_filter($this->filters[$type], function ($item) use ($value) {
                    return $item !== $value;
                });
                
                if (empty($this->filters[$type])) {
                    unset($this->filters[$type]);
                }
            }
        }
        
        $this->resetPage();
        $this->search();
    }
    
    public function clearFilters()
    {
        $this->filters = [];
        $this->resetPage();
        $this->search();
    }
    
    public function changeSearchMode(string $mode)
    {
        $this->searchMode = $mode;
        $this->resetPage();
        $this->search();
    }
    
    public function changeSorting(string $sortBy, string $direction = 'asc')
    {
        $this->sortBy = $sortBy;
        $this->sortDirection = $direction;
        $this->resetPage();
        $this->search();
    }
    
    public function mount()
    {
        if (!empty($this->query)) {
            $this->search();
        }
    }
    
    public function hideSuggestions()
    {
        $this->showSuggestions = false;
    }
}
```

### ❌ 9.6.2 Blade Template dla Search Component
#### ❌ 9.6.2.1 Widok wyszukiwarki
```blade
<!-- resources/views/livewire/search/search-component.blade.php -->
<div class="search-container">
    <!-- Search Input -->
    <div class="relative">
        <div class="search-input-wrapper">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="query"
                wire:focus="getSuggestions"
                placeholder="Wyszukaj produkty (SKU, nazwa, kategoria...)"
                class="w-full px-4 py-3 pr-12 text-lg border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring focus:ring-blue-200"
                autocomplete="off"
            >
            
            <div class="absolute right-3 top-3">
                @if($isSearching)
                    <svg class="animate-spin h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                @else
                    <button wire:click="search" class="text-gray-500 hover:text-blue-500">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                @endif
            </div>
        </div>
        
        <!-- Autocomplete Suggestions -->
        @if($showSuggestions && !empty($suggestions))
        <div class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg">
            @foreach($suggestions as $suggestion)
            <div 
                wire:click="selectSuggestion('{{ $suggestion['suggestion'] }}')"
                class="px-4 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0"
            >
                <div class="flex justify-between items-center">
                    <span class="text-gray-900">{{ $suggestion['suggestion'] }}</span>
                    <div class="flex items-center space-x-2">
                        <span class="text-xs text-gray-500 capitalize">{{ $suggestion['type'] }}</span>
                        @if($suggestion['popularity'] > 0)
                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">{{ $suggestion['popularity'] }}</span>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    
    <!-- Search Mode Selector -->
    <div class="mt-4 flex space-x-4">
        <button 
            wire:click="changeSearchMode('exact')"
            class="px-3 py-1 text-sm rounded {{ $searchMode === 'exact' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700' }}"
        >
            Dokładne
        </button>
        <button 
            wire:click="changeSearchMode('fuzzy')"
            class="px-3 py-1 text-sm rounded {{ $searchMode === 'fuzzy' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700' }}"
        >
            Rozmyte
        </button>
        <button 
            wire:click="changeSearchMode('fulltext')"
            class="px-3 py-1 text-sm rounded {{ $searchMode === 'fulltext' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700' }}"
        >
            Pełnotekstowe
        </button>
    </div>
    
    <!-- Active Filters -->
    @if(!empty($filters))
    <div class="mt-4 flex flex-wrap gap-2">
        <span class="text-sm font-medium text-gray-700">Filtry:</span>
        @foreach($filters as $filterType => $filterValues)
            @if(is_array($filterValues))
                @foreach($filterValues as $value)
                <span class="inline-flex items-center px-3 py-1 text-sm bg-blue-100 text-blue-800 rounded-full">
                    {{ $filterType }}: {{ $value }}
                    <button wire:click="removeFilter('{{ $filterType }}', '{{ $value }}')" class="ml-2 text-blue-600 hover:text-blue-800">×</button>
                </span>
                @endforeach
            @else
            <span class="inline-flex items-center px-3 py-1 text-sm bg-blue-100 text-blue-800 rounded-full">
                {{ $filterType }}: {{ $filterValues }}
                <button wire:click="removeFilter('{{ $filterType }}')" class="ml-2 text-blue-600 hover:text-blue-800">×</button>
            </span>
            @endif
        @endforeach
        <button wire:click="clearFilters" class="text-sm text-red-600 hover:text-red-800">Wyczyść wszystkie</button>
    </div>
    @endif
    
    <!-- Search Results -->
    @if(!empty($query))
    <div class="mt-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">
                Wyniki wyszukiwania 
                @if($totalResults > 0)
                <span class="text-gray-500">({{ $totalResults }})</span>
                @endif
            </h3>
            
            <!-- Sorting -->
            <div class="flex space-x-2">
                <select wire:model.live="sortBy" class="text-sm border border-gray-300 rounded px-2 py-1">
                    <option value="relevance">Trafność</option>
                    <option value="name">Nazwa</option>
                    <option value="price">Cena</option>
                    <option value="created_at">Data dodania</option>
                </select>
                
                <button 
                    wire:click="changeSorting('{{ $sortBy }}', '{{ $sortDirection === 'asc' ? 'desc' : 'asc' }}')"
                    class="text-sm px-2 py-1 border border-gray-300 rounded"
                >
                    {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                </button>
            </div>
        </div>
        
        <!-- Product Results -->
        @if($totalResults > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($searchResults as $product)
            <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                <!-- Product image -->
                @if(!empty($product['images']))
                <img src="{{ $product['images'][0]['url'] }}" alt="{{ $product['name'] }}" class="w-full h-48 object-cover rounded mb-3">
                @endif
                
                <!-- Product details -->
                <h4 class="font-semibold text-gray-900 mb-2">{{ $product['name'] }}</h4>
                <p class="text-sm text-gray-500 mb-2">SKU: {{ $product['sku'] }}</p>
                
                @if(!empty($product['prices']) && auth()->user()?->can('view-prices'))
                <p class="text-lg font-bold text-green-600 mb-2">
                    {{ number_format($product['prices'][0]['price'], 2) }} zł
                </p>
                @endif
                
                <div class="flex justify-between items-center">
                    <a href="{{ route('products.show', $product['id']) }}" class="text-blue-600 hover:text-blue-800">
                        Zobacz szczegóły
                    </a>
                    @if($product['is_featured'])
                    <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">Polecane</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        
        <!-- Pagination -->
        <div class="mt-6">
            {{ $this->paginateView() }}
        </div>
        
        @else
        <div class="text-center py-8">
            <p class="text-gray-500 mb-4">Nie znaleziono produktów dla zapytania "{{ $query }}"</p>
            
            <!-- Search suggestions for no results -->
            @if(!empty($searchResults['suggestions'] ?? []))
            <div class="mt-4">
                <p class="text-sm text-gray-600 mb-2">Może chodziło Ci o:</p>
                <div class="flex flex-wrap justify-center gap-2">
                    @foreach($searchResults['suggestions'] as $suggestion)
                    <button 
                        wire:click="selectSuggestion('{{ $suggestion['suggestion'] }}')"
                        class="text-sm bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded"
                    >
                        {{ $suggestion['suggestion'] }}
                    </button>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif
    </div>
    @endif
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('searchComponent', () => ({
            init() {
                // Hide suggestions when clicking outside
                document.addEventListener('click', (e) => {
                    if (!this.$el.contains(e.target)) {
                        @this.hideSuggestions();
                    }
                });
                
                // Enter key to search
                this.$el.querySelector('input').addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        @this.search();
                    }
                });
            }
        }));
    });
</script>
```

---

## ❌ 9.7 SEARCH JOBS I INDEXOWANIE

### ❌ 9.7.1 UpdateSearchIndex Job
#### ❌ 9.7.1.1 Job do aktualizacji indeksu wyszukiwania
```php
<?php
namespace App\Jobs\Search;

use App\Models\Product;
use App\Models\SearchIndex;
use App\Services\Search\SearchIndexManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateSearchIndex implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected Product $product;
    
    public int $tries = 3;
    public int $timeout = 60;
    
    public function __construct(Product $product)
    {
        $this->product = $product;
    }
    
    public function handle(SearchIndexManager $indexManager): void
    {
        $indexManager->updateProductIndex($this->product);
    }
    
    public function failed(\Throwable $exception): void
    {
        \Log::error('Search index update failed', [
            'product_id' => $this->product->id,
            'error' => $exception->getMessage()
        ]);
    }
}
```

### ❌ 9.7.2 SearchIndexManager
#### ❌ 9.7.2.1 Zarządzanie indeksami wyszukiwania
```php
<?php
namespace App\Services\Search;

use App\Models\Product;
use App\Models\SearchIndex;
use App\Models\SearchSuggestion;
use Illuminate\Support\Facades\Log;

class SearchIndexManager
{
    public function updateProductIndex(Product $product): void
    {
        try {
            $searchContent = $this->buildSearchContent($product);
            $keywords = $this->extractKeywords($product);
            
            SearchIndex::updateOrCreate(
                [
                    'indexable_type' => Product::class,
                    'indexable_id' => $product->id
                ],
                [
                    'search_content' => $searchContent,
                    'keywords' => $keywords,
                    'boost_factor' => $product->is_featured ? 1.5 : 1.0,
                    'is_featured' => $product->is_featured,
                    'last_indexed_at' => now()
                ]
            );
            
            // Update suggestions
            $this->updateSuggestions($product);
            
            Log::info('Product search index updated', ['product_id' => $product->id]);
            
        } catch (\Exception $e) {
            Log::error('Failed to update product search index', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    protected function buildSearchContent(Product $product): string
    {
        $content = [];
        
        // Basic product info
        $content[] = $product->name;
        $content[] = $product->sku;
        $content[] = $product->ean ?? '';
        $content[] = $product->manufacturer_code ?? '';
        $content[] = strip_tags($product->description ?? '');
        $content[] = strip_tags($product->short_description ?? '');
        
        // Category info
        if ($product->category) {
            $content[] = $product->category->name;
            
            // Parent categories
            $parent = $product->category->parent;
            while ($parent) {
                $content[] = $parent->name;
                $parent = $parent->parent;
            }
        }
        
        // Attributes
        foreach ($product->attributeValues as $attributeValue) {
            $content[] = $attributeValue->attribute->name;
            $content[] = $attributeValue->value;
        }
        
        // Variants
        foreach ($product->variants as $variant) {
            $content[] = $variant->sku;
            $content[] = $variant->name ?? '';
        }
        
        return implode(' ', array_filter($content));
    }
    
    protected function extractKeywords(Product $product): string
    {
        $keywords = [];
        
        // Brand
        $brand = $product->attributeValues()
            ->whereHas('attribute', fn($q) => $q->where('code', 'brand'))
            ->first();
        if ($brand) {
            $keywords[] = $brand->value;
        }
        
        // Model
        $model = $product->attributeValues()
            ->whereHas('attribute', fn($q) => $q->where('code', 'model'))
            ->first();
        if ($model) {
            $keywords[] = $model->value;
        }
        
        // Extract keywords from name (common product terms)
        $commonTerms = $this->extractCommonTerms($product->name);
        $keywords = array_merge($keywords, $commonTerms);
        
        return implode(' ', array_unique(array_filter($keywords)));
    }
    
    protected function extractCommonTerms(string $name): array
    {
        // Simple keyword extraction - could be enhanced with NLP
        $terms = [];
        $words = explode(' ', strtolower($name));
        
        foreach ($words as $word) {
            // Skip short words and common terms
            if (strlen($word) < 3) continue;
            
            // Add meaningful terms
            if (preg_match('/^(części|part|motor|engine|filter|filtr)/', $word)) {
                $terms[] = $word;
            }
        }
        
        return $terms;
    }
    
    protected function updateSuggestions(Product $product): void
    {
        // Add product name as suggestion
        SearchSuggestion::updateOrCreate(
            [
                'normalized_suggestion' => mb_strtolower($product->name),
                'suggestion_type' => 'product'
            ],
            [
                'suggestion' => $product->name,
                'source_type' => 'product_name',
                'source_id' => $product->id,
                'is_active' => $product->is_active
            ]
        );
        
        // Add category as suggestion
        if ($product->category) {
            SearchSuggestion::updateOrCreate(
                [
                    'normalized_suggestion' => mb_strtolower($product->category->name),
                    'suggestion_type' => 'category'
                ],
                [
                    'suggestion' => $product->category->name,
                    'source_type' => 'category_name',
                    'source_id' => $product->category->id,
                    'is_active' => $product->category->is_active
                ]
            );
        }
        
        // Add brand as suggestion
        $brand = $product->attributeValues()
            ->whereHas('attribute', fn($q) => $q->where('code', 'brand'))
            ->first();
            
        if ($brand) {
            SearchSuggestion::updateOrCreate(
                [
                    'normalized_suggestion' => mb_strtolower($brand->value),
                    'suggestion_type' => 'brand'
                ],
                [
                    'suggestion' => $brand->value,
                    'source_type' => 'attribute_value',
                    'source_id' => $brand->id,
                    'is_active' => true
                ]
            );
        }
    }
    
    public function rebuildAllIndexes(): void
    {
        Log::info('Starting full search index rebuild');
        
        Product::chunk(100, function ($products) {
            foreach ($products as $product) {
                $this->updateProductIndex($product);
            }
        });
        
        Log::info('Full search index rebuild completed');
    }
    
    public function deleteProductIndex(Product $product): void
    {
        SearchIndex::where('indexable_type', Product::class)
            ->where('indexable_id', $product->id)
            ->delete();
            
        Log::info('Product search index deleted', ['product_id' => $product->id]);
    }
}
```

---

## ❌ 9.8 API SEARCH ENDPOINTS

### ❌ 9.8.1 SearchController API
#### ❌ 9.8.1.1 API endpoints dla wyszukiwania
```php
<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Search\ProductSearchService;
use App\Services\Search\AutocompleteService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    protected ProductSearchService $searchService;
    protected AutocompleteService $autocompleteService;
    
    public function __construct(
        ProductSearchService $searchService,
        AutocompleteService $autocompleteService
    ) {
        $this->searchService = $searchService;
        $this->autocompleteService = $autocompleteService;
    }
    
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:1|max:500',
            'filters' => 'array',
            'sort_by' => 'string|in:relevance,name,price,created_at',
            'sort_direction' => 'string|in:asc,desc',
            'strategy' => 'string|in:exact,fuzzy,fulltext',
            'per_page' => 'integer|min:1|max:100',
            'page' => 'integer|min:1'
        ]);
        
        try {
            $results = $this->searchService->search(
                $request->query,
                $request->filters ?? [],
                [
                    'strategy' => $request->strategy ?? 'fuzzy',
                    'sort_by' => $request->sort_by ?? 'relevance',
                    'sort_direction' => $request->sort_direction ?? 'desc',
                    'per_page' => $request->per_page ?? 20,
                    'page' => $request->page ?? 1,
                    'original_query' => $request->query
                ]
            );
            
            return response()->json([
                'status' => 'success',
                'data' => $results
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function autocomplete(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2|max:100',
            'limit' => 'integer|min:1|max:20'
        ]);
        
        try {
            $suggestions = $this->autocompleteService->getSuggestions(
                $request->query,
                $request->limit ?? 8
            );
            
            return response()->json([
                'status' => 'success',
                'data' => $suggestions
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Autocomplete failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function filters(Request $request): JsonResponse
    {
        try {
            // Return available filters with their options
            $filters = [
                'categories' => \App\Models\Category::active()
                    ->select('id', 'name')
                    ->get()
                    ->map(fn($cat) => ['value' => $cat->id, 'label' => $cat->name]),
                    
                'price_ranges' => [
                    ['value' => '0-100', 'label' => '0 - 100 zł'],
                    ['value' => '100-500', 'label' => '100 - 500 zł'],
                    ['value' => '500-1000', 'label' => '500 - 1000 zł'],
                    ['value' => '1000+', 'label' => 'Powyżej 1000 zł']
                ],
                
                'brands' => \App\Models\AttributeValue::whereHas('attribute', fn($q) => $q->where('code', 'brand'))
                    ->distinct()
                    ->pluck('value')
                    ->map(fn($brand) => ['value' => $brand, 'label' => $brand])
            ];
            
            return response()->json([
                'status' => 'success',
                'data' => $filters
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load filters: ' . $e->getMessage()
            ], 500);
        }
    }
}
```

---

## ❌ 9.9 TESTY I DOKUMENTACJA

### ❌ 9.9.1 Testy wyszukiwania
#### ❌ 9.9.1.1 ProductSearchTest
```php
<?php
namespace Tests\Feature\Search;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Services\Search\ProductSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;
    
    protected ProductSearchService $searchService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->searchService = app(ProductSearchService::class);
    }
    
    public function testCanSearchByExactSKU()
    {
        $product = Product::factory()->create(['sku' => 'TEST123']);
        
        $results = $this->searchService->search('TEST123');
        
        $this->assertCount(1, $results['results']);
        $this->assertEquals($product->id, $results['results'][0]['id']);
    }
    
    public function testCanSearchByProductName()
    {
        $product = Product::factory()->create(['name' => 'Test Product Name']);
        
        $results = $this->searchService->search('test product');
        
        $this->assertGreaterThan(0, $results['total']);
        $this->assertContains($product->id, collect($results['results'])->pluck('id'));
    }
    
    public function testFuzzySearchHandlesTypos()
    {
        $product = Product::factory()->create(['name' => 'Motorcycle Parts']);
        
        // Search with typo
        $results = $this->searchService->search('motrocycle part', [], ['strategy' => 'fuzzy']);
        
        $this->assertGreaterThan(0, $results['total']);
    }
    
    public function testSearchRespectsSecurity()
    {
        $publicProduct = Product::factory()->create(['is_active' => true]);
        $inactiveProduct = Product::factory()->create(['is_active' => false]);
        
        // Anonymous user search
        $results = $this->searchService->search($publicProduct->name);
        
        $resultIds = collect($results['results'])->pluck('id');
        $this->assertContains($publicProduct->id, $resultIds);
        $this->assertNotContains($inactiveProduct->id, $resultIds);
    }
}
```

---

## ❌ 9.10 COMMANDS I MAINTENANCE

### ❌ 9.10.1 Search Commands
#### ❌ 9.10.1.1 RebuildSearchIndex Command
```php
<?php
namespace App\Console\Commands;

use App\Services\Search\SearchIndexManager;
use Illuminate\Console\Command;

class RebuildSearchIndex extends Command
{
    protected $signature = 'search:rebuild-index';
    protected $description = 'Rebuild the full search index';
    
    public function handle(SearchIndexManager $indexManager)
    {
        $this->info('Starting search index rebuild...');
        
        $indexManager->rebuildAllIndexes();
        
        $this->info('Search index rebuild completed successfully!');
    }
}
```

---

## 📊 METRYKI ETAPU

**Szacowany czas realizacji:** 35 godzin  
**Liczba plików do utworzenia:** ~20  
**Liczba testów:** ~10  
**Liczba tabel MySQL:** 4 główne + indeksy full-text  
**API endpoints:** ~3  

---

## 🔍 DEFINICJA GOTOWOŚCI (DoD)

Etap zostanie uznany za ukończony gdy:

- ✅ Wszystkie zadania mają status ✅
- ✅ Inteligentna wyszukiwarka działa ze wszystkimi strategiami
- ✅ Autosugestie działają w czasie rzeczywistym
- ✅ System toleruje błędy ortograficzne i literówki
- ✅ Filtry zaawansowane działają poprawnie
- ✅ API search endpoints działają prawidłowo
- ✅ Indeksowanie produktów jest automatyczne
- ✅ Wszystkie testy przechodzą poprawnie
- ✅ Kod przesłany na serwer produkcyjny i przetestowany
- ✅ Dokumentacja jest kompletna

---

**Autor:** Claude Code AI  
**Data utworzenia:** 2025-09-05  
**Ostatnia aktualizacja:** 2025-09-05  
**Status:** ❌ NIEROZPOCZĘTY