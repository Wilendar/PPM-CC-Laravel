using Microsoft.Data.SqlClient;
using Dapper;
using SubiektApi;

var builder = WebApplication.CreateBuilder(args);

// Add services
builder.Services.AddScoped<ISubiektRepository, SubiektRepository>();
builder.Services.AddSingleton(builder.Configuration);

var app = builder.Build();

// API Key middleware
app.Use(async (context, next) =>
{
    // Skip auth for health endpoint without key (basic check)
    if (context.Request.Path == "/api/health" && !context.Request.Headers.ContainsKey("X-API-Key"))
    {
        context.Response.StatusCode = 401;
        await context.Response.WriteAsJsonAsync(new { success = false, error = "API Key required" });
        return;
    }

    var apiKey = context.Request.Headers["X-API-Key"].FirstOrDefault();
    var validKeys = builder.Configuration.GetSection("ApiKeys").Get<string[]>() ?? Array.Empty<string>();

    if (string.IsNullOrEmpty(apiKey) || !validKeys.Contains(apiKey))
    {
        context.Response.StatusCode = 401;
        await context.Response.WriteAsJsonAsync(new { success = false, error = "Invalid API Key" });
        return;
    }

    await next();
});

// ==================== ENDPOINTS ====================

// Health check
app.MapGet("/api/health", async (ISubiektRepository repo) =>
{
    try
    {
        var stats = await repo.GetDatabaseStatsAsync();
        return Results.Ok(new
        {
            success = true,
            timestamp = DateTime.Now.ToString("o"),
            status = "ok",
            database = stats.DatabaseName,
            server_version = stats.ServerVersion,
            products_count = stats.ProductsCount,
            response_time_ms = stats.ResponseTimeMs
        });
    }
    catch (Exception ex)
    {
        return Results.Ok(new
        {
            success = false,
            timestamp = DateTime.Now.ToString("o"),
            error = ex.Message
        });
    }
});

// Get all products (paginated)
// priceLevel: 0-10 (maps to tc_CenaNetto0..tc_CenaNetto10)
app.MapGet("/api/products", async (ISubiektRepository repo, int page = 1, int pageSize = 100, int priceLevel = 0, int warehouseId = 1, string? sku = null, string? name = null) =>
{
    try
    {
        var result = await repo.GetProductsAsync(page, pageSize, priceLevel, warehouseId, sku, name);
        return Results.Ok(new
        {
            success = true,
            timestamp = DateTime.Now.ToString("o"),
            data = result.Products,
            pagination = new
            {
                current_page = page,
                page_size = pageSize,
                total_items = result.TotalCount,
                total_pages = (int)Math.Ceiling((double)result.TotalCount / pageSize),
                has_next = page * pageSize < result.TotalCount,
                has_previous = page > 1
            }
        });
    }
    catch (Exception ex)
    {
        return Results.Ok(new { success = false, error = ex.Message });
    }
});

// Get product by ID
// priceLevel: 0-10 (maps to tc_CenaNetto0..tc_CenaNetto10)
app.MapGet("/api/products/{id:int}", async (ISubiektRepository repo, int id, int priceLevel = 0, int warehouseId = 1) =>
{
    try
    {
        var product = await repo.GetProductByIdAsync(id, priceLevel, warehouseId);
        if (product == null)
            return Results.NotFound(new { success = false, error = "Product not found" });

        return Results.Ok(new { success = true, timestamp = DateTime.Now.ToString("o"), data = product });
    }
    catch (Exception ex)
    {
        return Results.Ok(new { success = false, error = ex.Message });
    }
});

// Get product by SKU
// priceLevel: 0-10 (maps to tc_CenaNetto0..tc_CenaNetto10)
app.MapGet("/api/products/sku/{sku}", async (ISubiektRepository repo, string sku, int priceLevel = 0, int warehouseId = 1) =>
{
    try
    {
        var product = await repo.GetProductBySkuAsync(sku, priceLevel, warehouseId);
        if (product == null)
            return Results.NotFound(new { success = false, error = "Product not found" });

        return Results.Ok(new { success = true, timestamp = DateTime.Now.ToString("o"), data = product });
    }
    catch (Exception ex)
    {
        return Results.Ok(new { success = false, error = ex.Message });
    }
});

// Get stock
app.MapGet("/api/stock", async (ISubiektRepository repo, int page = 1, int pageSize = 100) =>
{
    try
    {
        var result = await repo.GetAllStockAsync(page, pageSize);
        return Results.Ok(new
        {
            success = true,
            timestamp = DateTime.Now.ToString("o"),
            data = result.Stock,
            pagination = new
            {
                current_page = page,
                page_size = pageSize,
                total_items = result.TotalCount
            }
        });
    }
    catch (Exception ex)
    {
        return Results.Ok(new { success = false, error = ex.Message });
    }
});

// Get stock by product ID
app.MapGet("/api/stock/{id:int}", async (ISubiektRepository repo, int id) =>
{
    try
    {
        var stock = await repo.GetStockByProductIdAsync(id);
        return Results.Ok(new { success = true, timestamp = DateTime.Now.ToString("o"), data = stock });
    }
    catch (Exception ex)
    {
        return Results.Ok(new { success = false, error = ex.Message });
    }
});

// Get warehouses
app.MapGet("/api/warehouses", async (ISubiektRepository repo) =>
{
    try
    {
        var warehouses = await repo.GetWarehousesAsync();
        return Results.Ok(new { success = true, timestamp = DateTime.Now.ToString("o"), data = warehouses });
    }
    catch (Exception ex)
    {
        return Results.Ok(new { success = false, error = ex.Message });
    }
});

// Get price levels (0-10 mapping to tc_CenaNetto0..tc_CenaNetto10)
app.MapGet("/api/price-levels", async (ISubiektRepository repo) =>
{
    try
    {
        var priceLevels = await repo.GetPriceLevelsAsync();
        return Results.Ok(new { success = true, timestamp = DateTime.Now.ToString("o"), data = priceLevels });
    }
    catch (Exception ex)
    {
        return Results.Ok(new { success = false, error = ex.Message });
    }
});

// Get VAT rates
app.MapGet("/api/vat-rates", async (ISubiektRepository repo) =>
{
    try
    {
        var vatRates = await repo.GetVatRatesAsync();
        return Results.Ok(new { success = true, timestamp = DateTime.Now.ToString("o"), data = vatRates });
    }
    catch (Exception ex)
    {
        return Results.Ok(new { success = false, error = ex.Message });
    }
});

// Get database schema - for development/debugging
app.MapGet("/api/schema", async (ISubiektRepository repo) =>
{
    try
    {
        var schema = await repo.GetDatabaseSchemaAsync();
        return Results.Ok(new { success = true, timestamp = DateTime.Now.ToString("o"), data = schema });
    }
    catch (Exception ex)
    {
        return Results.Ok(new { success = false, error = ex.Message });
    }
});

// Get table columns - for development/debugging
app.MapGet("/api/schema/{tableName}", async (ISubiektRepository repo, string tableName) =>
{
    try
    {
        var columns = await repo.GetTableColumnsAsync(tableName);
        return Results.Ok(new { success = true, timestamp = DateTime.Now.ToString("o"), table = tableName, columns = columns });
    }
    catch (Exception ex)
    {
        return Results.Ok(new { success = false, error = ex.Message });
    }
});

app.Run();
