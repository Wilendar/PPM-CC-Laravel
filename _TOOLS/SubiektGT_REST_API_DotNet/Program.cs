using Microsoft.Data.SqlClient;
using Dapper;
using SubiektApi;

var builder = WebApplication.CreateBuilder(args);

// Add services
builder.Services.AddScoped<ISubiektRepository, SubiektRepository>();
builder.Services.AddSingleton(builder.Configuration);

// Configure Sfera GT (for write operations)
builder.Services.Configure<SferaConfig>(builder.Configuration.GetSection("Sfera"));

// Register Sfera services
// Use DirectSqlProductWriter as fallback when Sfera COM is not available
var useSfera = builder.Configuration.GetValue<bool>("Sfera:Enabled", false);
if (useSfera)
{
    builder.Services.AddSingleton<SferaService>();
    builder.Services.AddScoped<ISferaProductWriter, SferaProductWriter>();
}
else
{
    // Fallback to DirectSQL for basic updates (no creates, no prices)
    builder.Services.AddScoped<ISferaProductWriter, DirectSqlProductWriter>();
}

// Add logging
builder.Services.AddLogging(logging =>
{
    logging.AddConsole();
    logging.SetMinimumLevel(LogLevel.Information);
});

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

// Get all prices by product ID (all 10 price levels)
app.MapGet("/api/prices/{id:int}", async (ISubiektRepository repo, int id) =>
{
    try
    {
        var prices = await repo.GetPricesByProductIdAsync(id);
        return Results.Ok(new { success = true, timestamp = DateTime.Now.ToString("o"), data = prices });
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

// ==================== WRITE ENDPOINTS (Sfera GT) ====================

// Check Sfera GT connection status
app.MapGet("/api/sfera/health", async (IServiceProvider sp) =>
{
    try
    {
        var sferaService = sp.GetService<SferaService>();
        if (sferaService == null)
        {
            return Results.Ok(new
            {
                success = true,
                timestamp = DateTime.Now.ToString("o"),
                sfera_enabled = false,
                mode = "DirectSQL",
                message = "Sfera GT not configured. Using DirectSQL fallback (limited write operations)."
            });
        }

        var health = await sferaService.TestConnectionAsync();
        return Results.Ok(new
        {
            success = health.Success,
            timestamp = DateTime.Now.ToString("o"),
            sfera_enabled = true,
            mode = "Sfera",
            status = health.Status,
            server = health.Server,
            database = health.Database,
            version = health.Version,
            response_time_ms = health.ResponseTimeMs,
            error = health.Error
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

// Create new product
// POST /api/products
// Body: ProductWriteRequest JSON
app.MapPost("/api/products", async (ProductWriteRequest request, ISferaProductWriter writer) =>
{
    try
    {
        var result = await writer.CreateProductAsync(request);

        if (result.Success)
        {
            return Results.Created($"/api/products/{result.ProductId}", new
            {
                success = true,
                timestamp = result.Timestamp.ToString("o"),
                data = new
                {
                    product_id = result.ProductId,
                    sku = result.Sku,
                    action = result.Action,
                    message = result.Message
                }
            });
        }

        // Return appropriate status code based on error
        var statusCode = result.ErrorCode switch
        {
            "VALIDATION_ERROR" => 400,
            "DUPLICATE_SKU" => 409,
            "SFERA_REQUIRED" => 501,
            "SFERA_CONNECTION_FAILED" => 503,
            _ => 400
        };

        return Results.Json(new
        {
            success = false,
            timestamp = result.Timestamp.ToString("o"),
            error = result.Error,
            error_code = result.ErrorCode
        }, statusCode: statusCode);
    }
    catch (Exception ex)
    {
        return Results.Json(new
        {
            success = false,
            timestamp = DateTime.Now.ToString("o"),
            error = ex.Message,
            error_code = "INTERNAL_ERROR"
        }, statusCode: 500);
    }
});

// Update product by ID
// PUT /api/products/{id}
// Body: ProductWriteRequest JSON
app.MapPut("/api/products/{id:int}", async (int id, ProductWriteRequest request, ISferaProductWriter writer) =>
{
    try
    {
        var result = await writer.UpdateProductAsync(id, request);

        if (result.Success)
        {
            return Results.Ok(new
            {
                success = true,
                timestamp = result.Timestamp.ToString("o"),
                data = new
                {
                    product_id = result.ProductId,
                    sku = result.Sku,
                    action = result.Action,
                    rows_affected = result.RowsAffected,
                    message = result.Message
                }
            });
        }

        var statusCode = result.ErrorCode switch
        {
            "PRODUCT_NOT_FOUND" => 404,
            "VALIDATION_ERROR" => 400,
            "SFERA_CONNECTION_FAILED" => 503,
            _ => 400
        };

        return Results.Json(new
        {
            success = false,
            timestamp = result.Timestamp.ToString("o"),
            error = result.Error,
            error_code = result.ErrorCode
        }, statusCode: statusCode);
    }
    catch (Exception ex)
    {
        return Results.Json(new
        {
            success = false,
            timestamp = DateTime.Now.ToString("o"),
            error = ex.Message,
            error_code = "INTERNAL_ERROR"
        }, statusCode: 500);
    }
});

// Update product by SKU
// PUT /api/products/sku/{sku}
// Body: ProductWriteRequest JSON
app.MapPut("/api/products/sku/{sku}", async (string sku, ProductWriteRequest request, ISferaProductWriter writer) =>
{
    try
    {
        var result = await writer.UpdateProductBySkuAsync(sku, request);

        if (result.Success)
        {
            return Results.Ok(new
            {
                success = true,
                timestamp = result.Timestamp.ToString("o"),
                data = new
                {
                    product_id = result.ProductId,
                    sku = result.Sku ?? sku,
                    action = result.Action,
                    rows_affected = result.RowsAffected,
                    message = result.Message
                }
            });
        }

        var statusCode = result.ErrorCode switch
        {
            "PRODUCT_NOT_FOUND" => 404,
            "VALIDATION_ERROR" => 400,
            "SFERA_CONNECTION_FAILED" => 503,
            _ => 400
        };

        return Results.Json(new
        {
            success = false,
            timestamp = result.Timestamp.ToString("o"),
            error = result.Error,
            error_code = result.ErrorCode
        }, statusCode: statusCode);
    }
    catch (Exception ex)
    {
        return Results.Json(new
        {
            success = false,
            timestamp = DateTime.Now.ToString("o"),
            error = ex.Message,
            error_code = "INTERNAL_ERROR"
        }, statusCode: 500);
    }
});

// Fix product visibility - adds missing tw_Stan records and fixes required fields
// POST /api/products/{id}/fix-visibility
app.MapPost("/api/products/{id:int}/fix-visibility", async (int id, ISubiektRepository repo, IConfiguration config) =>
{
    try
    {
        var connectionString = config.GetConnectionString("SubiektGT");
        using var conn = new SqlConnection(connectionString);
        await conn.OpenAsync();

        // Check if product exists
        var product = await conn.QueryFirstOrDefaultAsync<dynamic>(
            "SELECT tw_Id, tw_Symbol FROM tw__Towar WHERE tw_Id = @id AND tw_Usuniety = 0",
            new { id });

        if (product == null)
        {
            return Results.NotFound(new { success = false, error = "Product not found", error_code = "PRODUCT_NOT_FOUND" });
        }

        using var transaction = conn.BeginTransaction();
        var fixes = new List<string>();

        try
        {
            // Fix 1: Update critical fields in tw__Towar
            var updateTowarSql = @"
                UPDATE tw__Towar SET
                    tw_JakPrzySp = 1,
                    tw_SklepInternet = 1,
                    tw_KomunikatDokumenty = 3,
                    tw_GrupaJpkVat = -1,
                    tw_CzasDostawy = ISNULL(tw_CzasDostawy, 0),
                    tw_DniWaznosc = ISNULL(tw_DniWaznosc, 0),
                    tw_IdVatSp = ISNULL(tw_IdVatSp, 100001),
                    tw_IdVatZak = ISNULL(tw_IdVatZak, 100001),
                    tw_JednMiary = CASE WHEN tw_JednMiary NOT LIKE '%.' THEN tw_JednMiary + '.' ELSE tw_JednMiary END,
                    tw_JednMiaryZak = CASE WHEN tw_JednMiaryZak NOT LIKE '%.' THEN tw_JednMiaryZak + '.' ELSE tw_JednMiaryZak END,
                    tw_JednMiarySprz = CASE WHEN tw_JednMiarySprz NOT LIKE '%.' THEN tw_JednMiarySprz + '.' ELSE tw_JednMiarySprz END
                WHERE tw_Id = @id";

            var towarRows = await conn.ExecuteAsync(updateTowarSql, new { id }, transaction);
            fixes.Add($"tw__Towar fields updated ({towarRows} row)");

            // Fix 2: Add missing tw_Stan records for default warehouses (1 and 4)
            var defaultWarehouseIds = new[] { 1, 4 };
            foreach (var warehouseId in defaultWarehouseIds)
            {
                var existsStan = await conn.ExecuteScalarAsync<int>(
                    "SELECT COUNT(*) FROM tw_Stan WHERE st_TowId = @productId AND st_MagId = @warehouseId",
                    new { productId = id, warehouseId }, transaction);

                if (existsStan == 0)
                {
                    var insertStanSql = @"
                        INSERT INTO tw_Stan (st_TowId, st_MagId, st_Stan, st_StanMin, st_StanRez, st_StanMax)
                        VALUES (@productId, @warehouseId, 0, 0, 0, 0)";

                    await conn.ExecuteAsync(insertStanSql, new { productId = id, warehouseId }, transaction);
                    fixes.Add($"Added tw_Stan for warehouse {warehouseId}");
                }
                else
                {
                    fixes.Add($"tw_Stan for warehouse {warehouseId} already exists");
                }
            }

            transaction.Commit();

            return Results.Ok(new
            {
                success = true,
                timestamp = DateTime.Now.ToString("o"),
                product_id = id,
                sku = (string)product.tw_Symbol,
                fixes_applied = fixes,
                message = "Product visibility fixed. Please restart Subiekt GT to see changes."
            });
        }
        catch (Exception ex)
        {
            transaction.Rollback();
            throw;
        }
    }
    catch (Exception ex)
    {
        return Results.Json(new
        {
            success = false,
            timestamp = DateTime.Now.ToString("o"),
            error = ex.Message,
            error_code = "INTERNAL_ERROR"
        }, statusCode: 500);
    }
});

// Check if product exists by SKU
// HEAD /api/products/sku/{sku}
app.MapMethods("/api/products/sku/{sku}/exists", new[] { "HEAD", "GET" }, async (string sku, ISferaProductWriter writer) =>
{
    try
    {
        var exists = await writer.ProductExistsAsync(sku);
        var productId = exists ? await writer.GetProductIdBySkuAsync(sku) : null;

        return Results.Ok(new
        {
            success = true,
            timestamp = DateTime.Now.ToString("o"),
            exists = exists,
            product_id = productId,
            sku = sku
        });
    }
    catch (Exception ex)
    {
        return Results.Json(new
        {
            success = false,
            timestamp = DateTime.Now.ToString("o"),
            error = ex.Message
        }, statusCode: 500);
    }
});

app.Run();
