<?php
/**
 * Subiekt GT REST API Wrapper - Main Entry Point
 *
 * Lightweight REST API wrapper for Subiekt GT database.
 * Designed to run on Windows Server with SQL Server access.
 *
 * Endpoints:
 * - GET  /api/health              - Connection test
 * - GET  /api/products            - List products with pagination
 * - GET  /api/products/{id}       - Single product by ID
 * - GET  /api/products/sku/{sku}  - Product by SKU
 * - GET  /api/stock               - Stock levels
 * - GET  /api/stock/{id}          - Stock for single product
 * - GET  /api/stock/sku/{sku}     - Stock for product by SKU
 * - GET  /api/prices/{id}         - Prices for single product
 * - GET  /api/prices/sku/{sku}    - Prices for product by SKU
 * - GET  /api/warehouses          - Available warehouses
 * - GET  /api/price-types         - Price type definitions
 * - GET  /api/vat-rates           - VAT rates
 * - GET  /api/manufacturers       - Manufacturers
 * - GET  /api/product-groups      - Product categories
 * - GET  /api/units               - Measurement units
 *
 * @package SubiektGT_REST_Wrapper
 * @version 1.0.0
 */

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Headers
header('Content-Type: application/json; charset=utf-8');

// Load dependencies
require_once __DIR__ . '/SubiektRepository.php';

/**
 * Simple Logger
 */
class Logger
{
    private string $logPath;
    private string $level;
    private bool $enabled;

    public function __construct(array $config)
    {
        $this->enabled = $config['enabled'] ?? false;
        $this->logPath = $config['path'] ?? __DIR__ . '/storage/logs/';
        $this->level = $config['level'] ?? 'info';

        if ($this->enabled && !is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    public function log(string $level, string $message, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $levels = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];
        if (($levels[$level] ?? 0) < ($levels[$this->level] ?? 0)) {
            return;
        }

        $logFile = $this->logPath . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';

        file_put_contents(
            $logFile,
            "[{$timestamp}] [{$level}] {$message}{$contextStr}\n",
            FILE_APPEND | LOCK_EX
        );
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }
}

/**
 * Rate Limiter
 */
class RateLimiter
{
    private string $storagePath;
    private int $limit;
    private bool $enabled;

    public function __construct(array $config)
    {
        $this->enabled = $config['enabled'] ?? false;
        $this->limit = $config['requests_per_minute'] ?? 60;
        $this->storagePath = $config['storage_path'] ?? __DIR__ . '/storage/rate_limits/';

        if ($this->enabled && !is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    public function check(string $identifier): bool
    {
        if (!$this->enabled) {
            return true;
        }

        $file = $this->storagePath . md5($identifier) . '.json';
        $now = time();
        $windowStart = $now - 60;

        $data = ['requests' => []];
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true) ?: ['requests' => []];
        }

        // Remove old requests
        $data['requests'] = array_filter($data['requests'], fn($ts) => $ts > $windowStart);

        // Check limit
        if (count($data['requests']) >= $this->limit) {
            return false;
        }

        // Add current request
        $data['requests'][] = $now;
        file_put_contents($file, json_encode($data));

        return true;
    }

    public function getRemaining(string $identifier): int
    {
        if (!$this->enabled) {
            return $this->limit;
        }

        $file = $this->storagePath . md5($identifier) . '.json';
        $windowStart = time() - 60;

        if (!file_exists($file)) {
            return $this->limit;
        }

        $data = json_decode(file_get_contents($file), true) ?: ['requests' => []];
        $recentRequests = array_filter($data['requests'], fn($ts) => $ts > $windowStart);

        return max(0, $this->limit - count($recentRequests));
    }
}

/**
 * API Application
 */
class ApiApplication
{
    private array $config;
    private Logger $logger;
    private RateLimiter $rateLimiter;
    private ?SubiektRepository $repository = null;
    private ?string $apiKeyName = null;

    public function __construct()
    {
        // Load configuration
        $configFile = __DIR__ . '/config.php';
        if (!file_exists($configFile)) {
            $this->sendError('Configuration file not found. Copy config.example.php to config.php', 500);
            exit;
        }

        $this->config = require $configFile;
        $this->logger = new Logger($this->config['logging'] ?? []);
        $this->rateLimiter = new RateLimiter($this->config['api']['rate_limit'] ?? []);
    }

    /**
     * Run the application
     */
    public function run(): void
    {
        try {
            // Handle CORS preflight
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                $this->handleCors();
                http_response_code(204);
                exit;
            }

            // Handle CORS headers
            $this->handleCors();

            // Authenticate request
            if (!$this->authenticate()) {
                return;
            }

            // Check rate limit
            if (!$this->checkRateLimit()) {
                return;
            }

            // Route request
            $this->route();

        } catch (PDOException $e) {
            $this->logger->error('Database error', ['message' => $e->getMessage()]);
            $this->sendError('Database connection error: ' . $e->getMessage(), 503);
        } catch (Exception $e) {
            $this->logger->error('Application error', ['message' => $e->getMessage()]);
            $this->sendError('Internal server error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Handle CORS headers
     */
    private function handleCors(): void
    {
        $corsConfig = $this->config['api']['cors'] ?? [];
        if (!($corsConfig['enabled'] ?? false)) {
            return;
        }

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        $allowedOrigins = $corsConfig['allowed_origins'] ?? ['*'];

        if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
            header('Access-Control-Allow-Origin: ' . ($allowedOrigins[0] === '*' ? '*' : $origin));
        }

        header('Access-Control-Allow-Methods: ' . implode(', ', $corsConfig['allowed_methods'] ?? ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']));
        header('Access-Control-Allow-Headers: ' . implode(', ', $corsConfig['allowed_headers'] ?? ['Content-Type', 'X-API-Key']));
        header('Access-Control-Max-Age: 86400');
    }

    /**
     * Authenticate API request
     */
    private function authenticate(): bool
    {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;

        if (!$apiKey) {
            $this->sendError('Missing X-API-Key header', 401);
            return false;
        }

        $keys = $this->config['api']['keys'] ?? [];
        if (!isset($keys[$apiKey])) {
            $this->logger->error('Invalid API key attempted', ['key_prefix' => substr($apiKey, 0, 8) . '...']);
            $this->sendError('Invalid API key', 401);
            return false;
        }

        $keyConfig = $keys[$apiKey];

        // Check IP whitelist
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
        $whitelist = $keyConfig['ip_whitelist'] ?? [];
        if (!empty($whitelist) && !in_array($clientIp, $whitelist)) {
            $this->logger->error('IP not in whitelist', ['ip' => $clientIp, 'key_name' => $keyConfig['name']]);
            $this->sendError('Access denied from this IP address', 403);
            return false;
        }

        $this->apiKeyName = $keyConfig['name'] ?? 'Unknown';
        return true;
    }

    /**
     * Check rate limit
     */
    private function checkRateLimit(): bool
    {
        $identifier = $_SERVER['HTTP_X_API_KEY'] ?? $_SERVER['REMOTE_ADDR'];

        if (!$this->rateLimiter->check($identifier)) {
            $this->sendError('Rate limit exceeded. Try again later.', 429, [
                'retry_after' => 60,
            ]);
            return false;
        }

        // Add rate limit headers
        $remaining = $this->rateLimiter->getRemaining($identifier);
        header('X-RateLimit-Limit: ' . ($this->config['api']['rate_limit']['requests_per_minute'] ?? 60));
        header('X-RateLimit-Remaining: ' . $remaining);

        return true;
    }

    /**
     * Route request to appropriate handler
     */
    private function route(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Remove base path if present (e.g., /subiekt-api)
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        if ($basePath !== '/' && $basePath !== '\\') {
            $uri = substr($uri, strlen($basePath));
        }

        // Normalize URI
        $uri = '/' . trim($uri, '/');

        // Log request
        if ($this->config['logging']['log_requests'] ?? false) {
            $this->logger->info('API Request', [
                'method' => $method,
                'uri' => $uri,
                'client' => $this->apiKeyName,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
        }

        // Route matching
        $routes = [
            'GET' => [
                '/api/health' => 'handleHealth',
                '/api/stats' => 'handleStats',
                '/api/products' => 'handleGetProducts',
                '/api/products/(\d+)' => 'handleGetProductById',
                '/api/products/sku/(.+)' => 'handleGetProductBySku',
                '/api/stock' => 'handleGetStock',
                '/api/stock/(\d+)' => 'handleGetProductStock',
                '/api/stock/sku/(.+)' => 'handleGetProductStockBySku',
                '/api/prices/(\d+)' => 'handleGetProductPrices',
                '/api/prices/sku/(.+)' => 'handleGetProductPricesBySku',
                '/api/warehouses' => 'handleGetWarehouses',
                '/api/price-types' => 'handleGetPriceTypes',
                '/api/vat-rates' => 'handleGetVatRates',
                '/api/manufacturers' => 'handleGetManufacturers',
                '/api/product-groups' => 'handleGetProductGroups',
                '/api/units' => 'handleGetUnits',
            ],
        ];

        // Find matching route
        $methodRoutes = $routes[$method] ?? [];
        foreach ($methodRoutes as $pattern => $handler) {
            $regex = '#^' . $pattern . '$#';
            if (preg_match($regex, $uri, $matches)) {
                array_shift($matches); // Remove full match
                call_user_func_array([$this, $handler], $matches);
                return;
            }
        }

        // No route found
        $this->sendError('Endpoint not found: ' . $uri, 404);
    }

    /**
     * Get repository instance
     */
    private function getRepository(): SubiektRepository
    {
        if ($this->repository === null) {
            $this->repository = new SubiektRepository($this->config['database']);
        }
        return $this->repository;
    }

    /**
     * Get request parameter
     */
    private function getParam(string $name, $default = null)
    {
        return $_GET[$name] ?? $default;
    }

    /**
     * Get pagination parameters
     */
    private function getPaginationParams(): array
    {
        $page = max(1, (int) $this->getParam('page', 1));
        $pageSize = min(
            $this->config['api']['max_page_size'] ?? 500,
            max(1, (int) $this->getParam('page_size', $this->config['api']['default_page_size'] ?? 100))
        );

        return [$page, $pageSize];
    }

    // ==========================================
    // HANDLERS
    // ==========================================

    /**
     * Health check endpoint
     */
    private function handleHealth(): void
    {
        $health = $this->getRepository()->healthCheck();
        $this->sendResponse($health);
    }

    /**
     * Database statistics
     */
    private function handleStats(): void
    {
        $stats = $this->getRepository()->getStats();
        $this->sendResponse($stats);
    }

    /**
     * Get products with pagination
     */
    private function handleGetProducts(): void
    {
        [$page, $pageSize] = $this->getPaginationParams();

        $priceTypeId = $this->getParam('price_type_id', $this->config['defaults']['price_type_id'] ?? 1);
        $warehouseId = $this->getParam('warehouse_id', $this->config['defaults']['warehouse_id'] ?? 1);

        $filters = [
            'sku' => $this->getParam('sku'),
            'name' => $this->getParam('name'),
            'ean' => $this->getParam('ean'),
            'modified_since' => $this->getParam('modified_since'),
            'active_only' => $this->getParam('active_only', '1') === '1',
        ];

        $result = $this->getRepository()->getProducts($page, $pageSize, (int) $priceTypeId, (int) $warehouseId, $filters);
        $this->sendResponse($result);
    }

    /**
     * Get single product by ID
     */
    private function handleGetProductById(int $id): void
    {
        $priceTypeId = $this->getParam('price_type_id', $this->config['defaults']['price_type_id'] ?? 1);
        $warehouseId = $this->getParam('warehouse_id', $this->config['defaults']['warehouse_id'] ?? 1);

        $product = $this->getRepository()->getProductById($id, (int) $priceTypeId, (int) $warehouseId);

        if (!$product) {
            $this->sendError('Product not found', 404);
            return;
        }

        $this->sendResponse(['data' => $product]);
    }

    /**
     * Get single product by SKU
     */
    private function handleGetProductBySku(string $sku): void
    {
        $sku = urldecode($sku);
        $priceTypeId = $this->getParam('price_type_id', $this->config['defaults']['price_type_id'] ?? 1);
        $warehouseId = $this->getParam('warehouse_id', $this->config['defaults']['warehouse_id'] ?? 1);

        $product = $this->getRepository()->getProductBySku($sku, (int) $priceTypeId, (int) $warehouseId);

        if (!$product) {
            $this->sendError('Product not found with SKU: ' . $sku, 404);
            return;
        }

        $this->sendResponse(['data' => $product]);
    }

    /**
     * Get stock levels
     */
    private function handleGetStock(): void
    {
        [$page, $pageSize] = $this->getPaginationParams();
        $warehouseId = $this->getParam('warehouse_id');

        $result = $this->getRepository()->getStock($page, $pageSize, $warehouseId ? (int) $warehouseId : null);
        $this->sendResponse($result);
    }

    /**
     * Get stock for single product
     */
    private function handleGetProductStock(int $id): void
    {
        $stock = $this->getRepository()->getProductStock($id);
        $this->sendResponse(['data' => $stock]);
    }

    /**
     * Get stock for product by SKU
     */
    private function handleGetProductStockBySku(string $sku): void
    {
        $sku = urldecode($sku);
        $stock = $this->getRepository()->getProductStockBySku($sku);
        $this->sendResponse(['data' => $stock]);
    }

    /**
     * Get prices for single product
     */
    private function handleGetProductPrices(int $id): void
    {
        $prices = $this->getRepository()->getProductPrices($id);
        $this->sendResponse(['data' => $prices]);
    }

    /**
     * Get prices for product by SKU
     */
    private function handleGetProductPricesBySku(string $sku): void
    {
        $sku = urldecode($sku);
        $prices = $this->getRepository()->getProductPricesBySku($sku);
        $this->sendResponse(['data' => $prices]);
    }

    /**
     * Get warehouses
     */
    private function handleGetWarehouses(): void
    {
        $warehouses = $this->getRepository()->getWarehouses();
        $this->sendResponse(['data' => $warehouses]);
    }

    /**
     * Get price types
     */
    private function handleGetPriceTypes(): void
    {
        $priceTypes = $this->getRepository()->getPriceTypes();
        $this->sendResponse(['data' => $priceTypes]);
    }

    /**
     * Get VAT rates
     */
    private function handleGetVatRates(): void
    {
        $vatRates = $this->getRepository()->getVatRates();
        $this->sendResponse(['data' => $vatRates]);
    }

    /**
     * Get manufacturers
     */
    private function handleGetManufacturers(): void
    {
        $manufacturers = $this->getRepository()->getManufacturers();
        $this->sendResponse(['data' => $manufacturers]);
    }

    /**
     * Get product groups
     */
    private function handleGetProductGroups(): void
    {
        $groups = $this->getRepository()->getProductGroups();
        $this->sendResponse(['data' => $groups]);
    }

    /**
     * Get measurement units
     */
    private function handleGetUnits(): void
    {
        $units = $this->getRepository()->getUnits();
        $this->sendResponse(['data' => $units]);
    }

    // ==========================================
    // RESPONSE HELPERS
    // ==========================================

    /**
     * Send JSON response
     */
    private function sendResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode([
            'success' => true,
            'timestamp' => date('c'),
            ...$data,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Send error response
     */
    private function sendError(string $message, int $statusCode = 400, array $extra = []): void
    {
        http_response_code($statusCode);
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => $statusCode,
                'message' => $message,
                ...$extra,
            ],
            'timestamp' => date('c'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

// Create storage directories if needed
$storageDirs = [
    __DIR__ . '/storage',
    __DIR__ . '/storage/logs',
    __DIR__ . '/storage/rate_limits',
    __DIR__ . '/storage/cache',
];

foreach ($storageDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Run the application
$app = new ApiApplication();
$app->run();
