using Microsoft.Data.SqlClient;
using Dapper;
using Microsoft.Extensions.Options;
using Microsoft.Extensions.Logging;

namespace SubiektApi;

// ==================== INTERFACES ====================

/// <summary>
/// Interface for product write operations in Subiekt GT.
/// Implemented by SferaProductWriter (COM) and DirectSqlProductWriter (SQL).
/// </summary>
public interface ISferaProductWriter
{
    Task<ProductWriteResponse> CreateProductAsync(ProductWriteRequest request);
    Task<ProductWriteResponse> UpdateProductAsync(int productId, ProductWriteRequest request);
    Task<ProductWriteResponse> UpdateProductBySkuAsync(string sku, ProductWriteRequest request);
    Task<StockWriteResponse> UpdateStockAsync(int productId, StockWriteRequest request);
    Task<StockWriteResponse> UpdateStockBySkuAsync(string sku, StockWriteRequest request);
    Task<bool> ProductExistsAsync(string sku);
    Task<int?> GetProductIdBySkuAsync(string sku);
}

/// <summary>
/// Request model for stock update operations.
/// Keys are warehouse IDs, values are stock data objects.
/// </summary>
public class StockWriteRequest
{
    /// <summary>
    /// Stock data per warehouse. Key = WarehouseId, Value = StockData.
    /// Example: { "1": { "quantity": 100.0, "min": 10.0, "max": 500.0 }, "4": { "quantity": 50.0 } }
    /// </summary>
    public Dictionary<int, StockData>? Stock { get; set; }
}

/// <summary>
/// Stock data for a single warehouse.
/// </summary>
public class StockData
{
    /// <summary>Current stock quantity (st_Stan)</summary>
    public decimal? Quantity { get; set; }

    /// <summary>Minimum stock level (st_StanMin)</summary>
    public decimal? Min { get; set; }

    /// <summary>Maximum stock level (st_StanMax)</summary>
    public decimal? Max { get; set; }

    /// <summary>Reserved quantity (st_StanRez) - usually read-only, but can be set</summary>
    public decimal? Reserved { get; set; }
}

/// <summary>
/// Response model for stock write operations.
/// </summary>
public class StockWriteResponse
{
    public bool Success { get; set; }
    public DateTime Timestamp { get; set; } = DateTime.Now;
    public int? ProductId { get; set; }
    public string? Sku { get; set; }
    public string? Action { get; set; }
    public string? Message { get; set; }
    public int RowsAffected { get; set; }
    public string? Error { get; set; }
    public string? ErrorCode { get; set; }
}

// ==================== SFERA PRODUCT WRITER (COM) ====================

/// <summary>
/// Full Sfera implementation using COM/OLE API
/// Requires Sfera license and Windows environment
/// </summary>
public class SferaProductWriter : ISferaProductWriter
{
    private readonly SferaService _sferaService;
    private readonly IConfiguration _config;
    private readonly ILogger<SferaProductWriter> _logger;

    public SferaProductWriter(
        SferaService sferaService,
        IConfiguration config,
        ILogger<SferaProductWriter> logger)
    {
        _sferaService = sferaService;
        _config = config;
        _logger = logger;
    }

    public async Task<ProductWriteResponse> CreateProductAsync(ProductWriteRequest request)
    {
        // TODO: Implement using Sfera COM
        // var gt = new COM('Insert.gt');
        // var towar = subiekt.TowaryManager.DodajTowar();
        // towar.Symbol = request.Sku;
        // towar.Zapisz();

        return new ProductWriteResponse
        {
            Success = false,
            Timestamp = DateTime.Now,
            Error = "Sfera COM interop not implemented",
            ErrorCode = "SFERA_NOT_IMPLEMENTED"
        };
    }

    public async Task<ProductWriteResponse> UpdateProductAsync(int productId, ProductWriteRequest request)
    {
        return new ProductWriteResponse
        {
            Success = false,
            Timestamp = DateTime.Now,
            Error = "Sfera COM interop not implemented",
            ErrorCode = "SFERA_NOT_IMPLEMENTED"
        };
    }

    public async Task<ProductWriteResponse> UpdateProductBySkuAsync(string sku, ProductWriteRequest request)
    {
        return new ProductWriteResponse
        {
            Success = false,
            Timestamp = DateTime.Now,
            Error = "Sfera COM interop not implemented",
            ErrorCode = "SFERA_NOT_IMPLEMENTED"
        };
    }

    public Task<StockWriteResponse> UpdateStockAsync(int productId, StockWriteRequest request)
    {
        return Task.FromResult(new StockWriteResponse
        {
            Success = false,
            Timestamp = DateTime.Now,
            Error = "Sfera COM interop not implemented",
            ErrorCode = "SFERA_NOT_IMPLEMENTED"
        });
    }

    public Task<StockWriteResponse> UpdateStockBySkuAsync(string sku, StockWriteRequest request)
    {
        return Task.FromResult(new StockWriteResponse
        {
            Success = false,
            Timestamp = DateTime.Now,
            Error = "Sfera COM interop not implemented",
            ErrorCode = "SFERA_NOT_IMPLEMENTED"
        });
    }

    public Task<bool> ProductExistsAsync(string sku) => Task.FromResult(false);
    public Task<int?> GetProductIdBySkuAsync(string sku) => Task.FromResult<int?>(null);
}

// ==================== DIRECT SQL PRODUCT WRITER ====================

/// <summary>
/// DirectSQL implementation for product operations.
/// CREATE: Implemented with transactions, ID generation via MAX+1 (with warning)
/// UPDATE: Full support for basic fields
/// WARNING: DirectSQL bypasses Sfera business logic - use with caution!
/// </summary>
public class DirectSqlProductWriter : ISferaProductWriter
{
    private readonly string _connectionString;
    private readonly ILogger<DirectSqlProductWriter> _logger;

    public DirectSqlProductWriter(IConfiguration config, ILogger<DirectSqlProductWriter> logger)
    {
        _connectionString = config.GetConnectionString("SubiektGT")
            ?? throw new InvalidOperationException("Connection string 'SubiektGT' not found");
        _logger = logger;
    }

    private SqlConnection GetConnection() => new SqlConnection(_connectionString);

    /// <summary>
    /// Creates a new product using DirectSQL.
    /// WARNING: This bypasses Sfera business logic!
    /// Uses MAX(tw_Id)+1 for ID generation (risk of race conditions).
    /// </summary>
    public async Task<ProductWriteResponse> CreateProductAsync(ProductWriteRequest request)
    {
        // Validation
        if (string.IsNullOrWhiteSpace(request.Sku))
        {
            return new ProductWriteResponse
            {
                Success = false,
                Timestamp = DateTime.Now,
                Error = "SKU is required",
                ErrorCode = "VALIDATION_ERROR"
            };
        }

        if (string.IsNullOrWhiteSpace(request.Name))
        {
            return new ProductWriteResponse
            {
                Success = false,
                Timestamp = DateTime.Now,
                Error = "Name is required",
                ErrorCode = "VALIDATION_ERROR"
            };
        }

        // SKU max 20 chars
        if (request.Sku.Length > 20)
        {
            return new ProductWriteResponse
            {
                Success = false,
                Timestamp = DateTime.Now,
                Error = "SKU must be max 20 characters",
                ErrorCode = "VALIDATION_ERROR"
            };
        }

        // Name max 50 chars
        if (request.Name.Length > 50)
        {
            return new ProductWriteResponse
            {
                Success = false,
                Timestamp = DateTime.Now,
                Error = "Name must be max 50 characters",
                ErrorCode = "VALIDATION_ERROR"
            };
        }

        using var conn = GetConnection();
        await conn.OpenAsync();

        // Check if SKU already exists
        var existingId = await conn.ExecuteScalarAsync<int?>(
            "SELECT tw_Id FROM tw__Towar WHERE tw_Symbol = @sku AND tw_Usuniety = 0",
            new { sku = request.Sku });

        if (existingId.HasValue)
        {
            return new ProductWriteResponse
            {
                Success = false,
                Timestamp = DateTime.Now,
                ProductId = existingId.Value,
                Sku = request.Sku,
                Error = $"Product with SKU '{request.Sku}' already exists (ID: {existingId.Value})",
                ErrorCode = "DUPLICATE_SKU"
            };
        }

        using var transaction = conn.BeginTransaction();

        try
        {
            _logger.LogWarning(
                "DirectSQL CREATE: Using MAX(tw_Id)+1 for ID generation. " +
                "This is NOT recommended for production - use Sfera API instead!");

            // Generate new tw_Id using MAX+1
            // WARNING: This is risky - race conditions possible!
            // Proper approach would be: EXEC spIdentyfikator 'tw__Towar', 1, @newId OUTPUT
            var maxId = await conn.ExecuteScalarAsync<int?>(
                "SELECT MAX(tw_Id) FROM tw__Towar",
                transaction: transaction) ?? 0;

            var newTwId = maxId + 1;

            _logger.LogInformation(
                "Creating product: SKU={Sku}, Name={Name}, ID={Id}",
                request.Sku, request.Name, newTwId);

            // Default values - MUST match Subiekt GT GUI standards
            // CRITICAL: Unit MUST include dot ("szt." not "szt") - GUI requires this format!
            var unit = request.Unit ?? "szt.";
            if (!unit.EndsWith(".") && unit.Length < 10)
            {
                unit += "."; // Ensure unit ends with dot
            }
            var description = request.Description ?? "";
            var ean = request.Ean ?? "";
            var pkwiu = request.Pkwiu ?? "";

            // Default VAT rate (23% = ID 100001) if not specified
            // This ensures product is properly configured for invoicing
            const int DEFAULT_VAT_RATE_ID = 100001;
            var vatRateId = request.VatRateId ?? DEFAULT_VAT_RATE_ID;

            // INSERT into tw__Towar (minimum required fields + NOT NULL fields)
            var insertTowarSql = @"
                INSERT INTO tw__Towar (
                    tw_Id,
                    tw_Symbol,
                    tw_Nazwa,
                    tw_Opis,
                    tw_Rodzaj,
                    tw_JednMiary,
                    tw_Zablokowany,
                    tw_Usuniety,
                    tw_PKWiU,
                    tw_SWW,
                    tw_DostSymbol,
                    tw_UrzNazwa,
                    tw_PodstKodKresk,
                    tw_WWW,
                    tw_JakPrzySp,
                    tw_PrzezWartosc,
                    tw_CenaOtwarta,
                    tw_KontrolaTW,
                    tw_SklepInternet,
                    tw_Pole1, tw_Pole2, tw_Pole3, tw_Pole4,
                    tw_Pole5, tw_Pole6, tw_Pole7, tw_Pole8,
                    tw_Uwagi,
                    tw_JednMiaryZak,
                    tw_JMZakInna,
                    tw_JednMiarySprz,
                    tw_JMSprzInna,
                    tw_SerwisAukcyjny,
                    tw_SprzedazMobilna,
                    tw_Akcyza,
                    tw_AkcyzaZaznacz,
                    tw_ObrotMarza,
                    tw_OdwrotneObciazenie,
                    tw_ProgKwotowyOO,
                    tw_DodawalnyDoZW,
                    tw_KomunikatDokumenty,
                    tw_MechanizmPodzielonejPlatnosci,
                    tw_GrupaJpkVat,
                    tw_CzasDostawy,
                    tw_DniWaznosc,
                    tw_OplCukrowaPodlega,
                    tw_OplCukrowaInneSlodzace,
                    tw_OplCukrowaSok,
                    tw_OplCukrowaKofeinaPodlega,
                    tw_OplCukrowaNapojWeglElektr,
                    tw_WegielPodlegaOswiadczeniu,
                    tw_WegielOpisPochodzenia,
                    tw_PodlegaOplacieNaFunduszOchronyRolnictwa,
                    tw_ObjetySysKaucyjnym,
                    tw_AkcyzaMarkaWyrobow,
                    tw_AkcyzaWielkoscProducenta,
                    tw_IdVatSp,
                    tw_IdVatZak,
                    tw_IdGrupa,
                    tw_IdPodstDostawca,
                    tw_IdProducenta,
                    tw_Masa
                )
                VALUES (
                    @tw_Id,
                    @tw_Symbol,
                    @tw_Nazwa,
                    @tw_Opis,
                    @tw_Rodzaj,
                    @tw_JednMiary,
                    @tw_Zablokowany,
                    @tw_Usuniety,
                    @tw_PKWiU,
                    @tw_SWW,
                    @tw_DostSymbol,
                    @tw_UrzNazwa,
                    @tw_PodstKodKresk,
                    @tw_WWW,
                    @tw_JakPrzySp,
                    @tw_PrzezWartosc,
                    @tw_CenaOtwarta,
                    @tw_KontrolaTW,
                    @tw_SklepInternet,
                    @tw_Pole1, @tw_Pole2, @tw_Pole3, @tw_Pole4,
                    @tw_Pole5, @tw_Pole6, @tw_Pole7, @tw_Pole8,
                    @tw_Uwagi,
                    @tw_JednMiaryZak,
                    @tw_JMZakInna,
                    @tw_JednMiarySprz,
                    @tw_JMSprzInna,
                    @tw_SerwisAukcyjny,
                    @tw_SprzedazMobilna,
                    @tw_Akcyza,
                    @tw_AkcyzaZaznacz,
                    @tw_ObrotMarza,
                    @tw_OdwrotneObciazenie,
                    @tw_ProgKwotowyOO,
                    @tw_DodawalnyDoZW,
                    @tw_KomunikatDokumenty,
                    @tw_MechanizmPodzielonejPlatnosci,
                    @tw_GrupaJpkVat,
                    @tw_CzasDostawy,
                    @tw_DniWaznosc,
                    @tw_OplCukrowaPodlega,
                    @tw_OplCukrowaInneSlodzace,
                    @tw_OplCukrowaSok,
                    @tw_OplCukrowaKofeinaPodlega,
                    @tw_OplCukrowaNapojWeglElektr,
                    @tw_WegielPodlegaOswiadczeniu,
                    @tw_WegielOpisPochodzenia,
                    @tw_PodlegaOplacieNaFunduszOchronyRolnictwa,
                    @tw_ObjetySysKaucyjnym,
                    @tw_AkcyzaMarkaWyrobow,
                    @tw_AkcyzaWielkoscProducenta,
                    @tw_IdVatSp,
                    @tw_IdVatZak,
                    @tw_IdGrupa,
                    @tw_IdPodstDostawca,
                    @tw_IdProducenta,
                    @tw_Masa
                )";

            var towarParams = new
            {
                tw_Id = newTwId,
                tw_Symbol = request.Sku,
                tw_Nazwa = request.Name,
                tw_Opis = description.Length > 255 ? description.Substring(0, 255) : description,
                tw_Rodzaj = 1, // 1 = towar (product), 2 = usługa (service)
                tw_JednMiary = unit.Length > 10 ? unit.Substring(0, 10) : unit,
                tw_Zablokowany = false,
                tw_Usuniety = false,
                tw_PKWiU = pkwiu.Length > 20 ? pkwiu.Substring(0, 20) : pkwiu,
                tw_SWW = "",
                tw_DostSymbol = "",
                tw_UrzNazwa = request.Name.Length > 50 ? request.Name.Substring(0, 50) : request.Name,
                tw_PodstKodKresk = ean.Length > 20 ? ean.Substring(0, 20) : ean,
                tw_WWW = "",
                tw_JakPrzySp = true,           // CRITICAL: Must be true for GUI visibility!
                tw_PrzezWartosc = false,
                tw_CenaOtwarta = false,
                tw_KontrolaTW = false,
                tw_SklepInternet = true,       // CRITICAL: Must be true for GUI visibility!
                tw_Pole1 = "", tw_Pole2 = "", tw_Pole3 = "", tw_Pole4 = "",
                tw_Pole5 = "", tw_Pole6 = "", tw_Pole7 = "", tw_Pole8 = "",
                tw_Uwagi = "",
                tw_JednMiaryZak = unit.Length > 10 ? unit.Substring(0, 10) : unit,
                tw_JMZakInna = false,
                tw_JednMiarySprz = unit.Length > 10 ? unit.Substring(0, 10) : unit,
                tw_JMSprzInna = false,
                tw_SerwisAukcyjny = false,
                tw_SprzedazMobilna = false,
                tw_Akcyza = false,
                tw_AkcyzaZaznacz = false,
                tw_ObrotMarza = false,
                tw_OdwrotneObciazenie = false,
                tw_ProgKwotowyOO = 0,
                tw_DodawalnyDoZW = false,
                tw_KomunikatDokumenty = 3,     // CRITICAL: Must be 3 for GUI visibility!
                tw_MechanizmPodzielonejPlatnosci = false,
                tw_GrupaJpkVat = -1,           // CRITICAL: Must be -1 for GUI visibility!
                tw_CzasDostawy = 0,            // Required: delivery time in days
                tw_DniWaznosc = 0,             // Required: validity days
                tw_OplCukrowaPodlega = false,
                tw_OplCukrowaInneSlodzace = false,
                tw_OplCukrowaSok = false,
                tw_OplCukrowaKofeinaPodlega = false,
                tw_OplCukrowaNapojWeglElektr = false,
                tw_WegielPodlegaOswiadczeniu = false,
                tw_WegielOpisPochodzenia = "",
                tw_PodlegaOplacieNaFunduszOchronyRolnictwa = false,
                tw_ObjetySysKaucyjnym = false,
                tw_AkcyzaMarkaWyrobow = "",
                tw_AkcyzaWielkoscProducenta = "",
                tw_IdVatSp = vatRateId,        // Use default if not specified
                tw_IdVatZak = vatRateId,       // Same VAT for purchase
                tw_IdGrupa = request.GroupId,
                tw_IdPodstDostawca = request.SupplierContractorId ?? request.ManufacturerId,
                tw_IdProducenta = request.ManufacturerContractorId,
                tw_Masa = request.Weight
            };

            await conn.ExecuteAsync(insertTowarSql, towarParams, transaction);

            _logger.LogInformation("Inserted tw__Towar with ID={Id}", newTwId);

            // Generate tc_Id for tw_Cena
            var maxTcId = await conn.ExecuteScalarAsync<int?>(
                "SELECT MAX(tc_Id) FROM tw_Cena",
                transaction: transaction) ?? 0;

            var newTcId = maxTcId + 1;

            // Default prices
            var priceNet0 = request.PriceNet ?? request.PricesNet?.GetValueOrDefault(0) ?? 0m;
            var priceGross0 = request.PriceGross ?? request.PricesGross?.GetValueOrDefault(0) ?? 0m;

            // If only net provided, calculate gross (23% VAT)
            if (priceNet0 > 0 && priceGross0 == 0)
            {
                priceGross0 = Math.Round(priceNet0 * 1.23m, 2);
            }

            // INSERT into tw_Cena (all 11 price levels)
            var insertCenaSql = @"
                INSERT INTO tw_Cena (
                    tc_Id,
                    tc_IdTowar,
                    tc_CenaNetto0, tc_CenaNetto1, tc_CenaNetto2, tc_CenaNetto3, tc_CenaNetto4,
                    tc_CenaNetto5, tc_CenaNetto6, tc_CenaNetto7, tc_CenaNetto8, tc_CenaNetto9, tc_CenaNetto10,
                    tc_CenaBrutto0, tc_CenaBrutto1, tc_CenaBrutto2, tc_CenaBrutto3, tc_CenaBrutto4,
                    tc_CenaBrutto5, tc_CenaBrutto6, tc_CenaBrutto7, tc_CenaBrutto8, tc_CenaBrutto9, tc_CenaBrutto10,
                    tc_IdWaluta0, tc_IdWaluta1, tc_IdWaluta2, tc_IdWaluta3, tc_IdWaluta4,
                    tc_IdWaluta5, tc_IdWaluta6, tc_IdWaluta7, tc_IdWaluta8, tc_IdWaluta9, tc_IdWaluta10,
                    tc_WalutaJedn
                )
                VALUES (
                    @tc_Id,
                    @tc_IdTowar,
                    @tc_CenaNetto0, @tc_CenaNetto1, @tc_CenaNetto2, @tc_CenaNetto3, @tc_CenaNetto4,
                    @tc_CenaNetto5, @tc_CenaNetto6, @tc_CenaNetto7, @tc_CenaNetto8, @tc_CenaNetto9, @tc_CenaNetto10,
                    @tc_CenaBrutto0, @tc_CenaBrutto1, @tc_CenaBrutto2, @tc_CenaBrutto3, @tc_CenaBrutto4,
                    @tc_CenaBrutto5, @tc_CenaBrutto6, @tc_CenaBrutto7, @tc_CenaBrutto8, @tc_CenaBrutto9, @tc_CenaBrutto10,
                    @tc_IdWaluta0, @tc_IdWaluta1, @tc_IdWaluta2, @tc_IdWaluta3, @tc_IdWaluta4,
                    @tc_IdWaluta5, @tc_IdWaluta6, @tc_IdWaluta7, @tc_IdWaluta8, @tc_IdWaluta9, @tc_IdWaluta10,
                    @tc_WalutaJedn
                )";

            var cenaParams = new
            {
                tc_Id = newTcId,
                tc_IdTowar = newTwId,
                tc_CenaNetto0 = request.PricesNet?.GetValueOrDefault(0) ?? priceNet0,
                tc_CenaNetto1 = request.PricesNet?.GetValueOrDefault(1) ?? priceNet0,
                tc_CenaNetto2 = request.PricesNet?.GetValueOrDefault(2) ?? priceNet0,
                tc_CenaNetto3 = request.PricesNet?.GetValueOrDefault(3) ?? priceNet0,
                tc_CenaNetto4 = request.PricesNet?.GetValueOrDefault(4) ?? priceNet0,
                tc_CenaNetto5 = request.PricesNet?.GetValueOrDefault(5) ?? priceNet0,
                tc_CenaNetto6 = request.PricesNet?.GetValueOrDefault(6) ?? priceNet0,
                tc_CenaNetto7 = request.PricesNet?.GetValueOrDefault(7) ?? priceNet0,
                tc_CenaNetto8 = request.PricesNet?.GetValueOrDefault(8) ?? priceNet0,
                tc_CenaNetto9 = request.PricesNet?.GetValueOrDefault(9) ?? priceNet0,
                tc_CenaNetto10 = request.PricesNet?.GetValueOrDefault(10) ?? priceNet0,
                tc_CenaBrutto0 = request.PricesGross?.GetValueOrDefault(0) ?? priceGross0,
                tc_CenaBrutto1 = request.PricesGross?.GetValueOrDefault(1) ?? priceGross0,
                tc_CenaBrutto2 = request.PricesGross?.GetValueOrDefault(2) ?? priceGross0,
                tc_CenaBrutto3 = request.PricesGross?.GetValueOrDefault(3) ?? priceGross0,
                tc_CenaBrutto4 = request.PricesGross?.GetValueOrDefault(4) ?? priceGross0,
                tc_CenaBrutto5 = request.PricesGross?.GetValueOrDefault(5) ?? priceGross0,
                tc_CenaBrutto6 = request.PricesGross?.GetValueOrDefault(6) ?? priceGross0,
                tc_CenaBrutto7 = request.PricesGross?.GetValueOrDefault(7) ?? priceGross0,
                tc_CenaBrutto8 = request.PricesGross?.GetValueOrDefault(8) ?? priceGross0,
                tc_CenaBrutto9 = request.PricesGross?.GetValueOrDefault(9) ?? priceGross0,
                tc_CenaBrutto10 = request.PricesGross?.GetValueOrDefault(10) ?? priceGross0,
                tc_IdWaluta0 = "PLN",
                tc_IdWaluta1 = "PLN",
                tc_IdWaluta2 = "PLN",
                tc_IdWaluta3 = "PLN",
                tc_IdWaluta4 = "PLN",
                tc_IdWaluta5 = "PLN",
                tc_IdWaluta6 = "PLN",
                tc_IdWaluta7 = "PLN",
                tc_IdWaluta8 = "PLN",
                tc_IdWaluta9 = "PLN",
                tc_IdWaluta10 = "PLN",
                tc_WalutaJedn = unit.Length > 10 ? unit.Substring(0, 10) : unit
            };

            await conn.ExecuteAsync(insertCenaSql, cenaParams, transaction);

            _logger.LogInformation("Inserted tw_Cena with ID={Id} for product ID={ProductId}", newTcId, newTwId);

            // CRITICAL FIX: Insert into tw_Stan for default warehouses
            // Without this record, product won't be visible in Subiekt GT GUI!
            // GUI requires at least one warehouse assignment to display product in:
            // - Kartoteka towarow (product list)
            // - Document issuing (FV, WZ, etc.)
            // - Stock views
            var defaultWarehouseIds = new[] { 1, 4 }; // "Sprzedaz" (1) and "Stany" (4)

            foreach (var warehouseId in defaultWarehouseIds)
            {
                var insertStanSql = @"
                    INSERT INTO tw_Stan (st_TowId, st_MagId, st_Stan, st_StanMin, st_StanRez, st_StanMax)
                    VALUES (@productId, @warehouseId, 0, 0, 0, 0)";

                await conn.ExecuteAsync(insertStanSql, new
                {
                    productId = newTwId,
                    warehouseId = warehouseId
                }, transaction);
            }

            _logger.LogInformation(
                "Inserted tw_Stan for product ID={Id}, warehouses: [{Warehouses}]",
                newTwId, string.Join(", ", defaultWarehouseIds));

            transaction.Commit();

            _logger.LogInformation(
                "Successfully created product: SKU={Sku}, ID={Id} (DirectSQL)",
                request.Sku, newTwId);

            return new ProductWriteResponse
            {
                Success = true,
                Timestamp = DateTime.Now,
                ProductId = newTwId,
                Sku = request.Sku,
                Action = "created",
                Message = $"Product created successfully via DirectSQL (ID: {newTwId}). " +
                          "Assigned to warehouses: Sprzedaz, Stany. " +
                          "WARNING: This bypasses Sfera business logic - verify in Subiekt GT GUI!",
                RowsAffected = 4 // tw__Towar + tw_Cena + 2x tw_Stan
            };
        }
        catch (SqlException ex)
        {
            transaction.Rollback();

            _logger.LogError(ex,
                "SQL error creating product SKU={Sku}: {Message}",
                request.Sku, ex.Message);

            return new ProductWriteResponse
            {
                Success = false,
                Timestamp = DateTime.Now,
                Sku = request.Sku,
                Error = $"SQL error: {ex.Message}",
                ErrorCode = "SQL_ERROR"
            };
        }
        catch (Exception ex)
        {
            transaction.Rollback();

            _logger.LogError(ex,
                "Error creating product SKU={Sku}: {Message}",
                request.Sku, ex.Message);

            return new ProductWriteResponse
            {
                Success = false,
                Timestamp = DateTime.Now,
                Sku = request.Sku,
                Error = ex.Message,
                ErrorCode = "INTERNAL_ERROR"
            };
        }
    }

    public async Task<ProductWriteResponse> UpdateProductAsync(int productId, ProductWriteRequest request)
    {
        using var conn = GetConnection();
        await conn.OpenAsync();

        // Check if product exists
        var existingSku = await conn.ExecuteScalarAsync<string?>(
            "SELECT tw_Symbol FROM tw__Towar WHERE tw_Id = @id AND tw_Usuniety = 0",
            new { id = productId });

        if (existingSku == null)
        {
            return new ProductWriteResponse
            {
                Success = false,
                Timestamp = DateTime.Now,
                ProductId = productId,
                Error = $"Product with ID {productId} not found",
                ErrorCode = "PRODUCT_NOT_FOUND"
            };
        }

        using var transaction = conn.BeginTransaction();

        try
        {
            var updates = new List<string>();
            var parameters = new DynamicParameters();
            parameters.Add("@id", productId);

            // Build dynamic UPDATE for tw__Towar
            if (!string.IsNullOrWhiteSpace(request.Name))
            {
                updates.Add("tw_Nazwa = @name");
                parameters.Add("@name", request.Name.Length > 50 ? request.Name.Substring(0, 50) : request.Name);
            }

            if (!string.IsNullOrWhiteSpace(request.Description))
            {
                updates.Add("tw_Opis = @description");
                parameters.Add("@description", request.Description.Length > 255 ? request.Description.Substring(0, 255) : request.Description);
            }

            if (!string.IsNullOrWhiteSpace(request.Unit))
            {
                updates.Add("tw_JednMiary = @unit");
                updates.Add("tw_JednMiaryZak = @unit");
                updates.Add("tw_JednMiarySprz = @unit");
                parameters.Add("@unit", request.Unit.Length > 10 ? request.Unit.Substring(0, 10) : request.Unit);
            }

            if (!string.IsNullOrWhiteSpace(request.Ean))
            {
                updates.Add("tw_PodstKodKresk = @ean");
                parameters.Add("@ean", request.Ean.Length > 20 ? request.Ean.Substring(0, 20) : request.Ean);
            }

            if (!string.IsNullOrWhiteSpace(request.Pkwiu))
            {
                updates.Add("tw_PKWiU = @pkwiu");
                parameters.Add("@pkwiu", request.Pkwiu.Length > 20 ? request.Pkwiu.Substring(0, 20) : request.Pkwiu);
            }

            if (request.Weight.HasValue)
            {
                updates.Add("tw_Masa = @weight");
                parameters.Add("@weight", request.Weight.Value);
            }

            if (request.VatRateId.HasValue)
            {
                updates.Add("tw_IdVatSp = @vatId");
                parameters.Add("@vatId", request.VatRateId.Value);
            }

            if (request.GroupId.HasValue)
            {
                updates.Add("tw_IdGrupa = @groupId");
                parameters.Add("@groupId", request.GroupId.Value);
            }

            // tw_IdPodstDostawca - Supplier contractor (FK to kh__Kontrahent)
            // ST9: SupplierContractorId takes priority over legacy ManufacturerId
            if (request.SupplierContractorId.HasValue)
            {
                updates.Add("tw_IdPodstDostawca = @supplierContractorId");
                parameters.Add("@supplierContractorId", request.SupplierContractorId.Value);
            }
            else if (request.ManufacturerId.HasValue)
            {
                // Legacy: ManufacturerId mapped to tw_IdPodstDostawca
                updates.Add("tw_IdPodstDostawca = @manufacturerId");
                parameters.Add("@manufacturerId", request.ManufacturerId.Value);
            }

            // ST9: tw_IdProducenta - Manufacturer contractor (FK to kh__Kontrahent)
            if (request.ManufacturerContractorId.HasValue)
            {
                updates.Add("tw_IdProducenta = @manufacturerContractorId");
                parameters.Add("@manufacturerContractorId", request.ManufacturerContractorId.Value);
            }

            // Product-level minimum stock (tw_StanMin) - NOT per-warehouse!
            // In Subiekt GT, tw__Towar.tw_StanMin is global for all warehouses.
            // PPM sends the lowest minimum from all its warehouses.
            if (request.MinimumStock.HasValue)
            {
                updates.Add("tw_StanMin = @minimumStock");
                parameters.Add("@minimumStock", request.MinimumStock.Value);

                // Also set the unit for minimum stock display
                var minUnit = request.MinimumStockUnit ?? request.Unit ?? "szt.";
                if (!minUnit.EndsWith(".") && minUnit.Length < 10)
                {
                    minUnit += ".";
                }
                updates.Add("tw_JednStanMin = @minimumStockUnit");
                parameters.Add("@minimumStockUnit", minUnit.Length > 10 ? minUnit.Substring(0, 10) : minUnit);

                _logger.LogInformation(
                    "Setting product-level minimum stock: tw_StanMin={Min}, tw_JednStanMin={Unit}",
                    request.MinimumStock.Value, minUnit);
            }

            // ====== EXTENDED FIELDS (ETAP_08 FAZA 3.4) ======

            // tw_SklepInternet - Internet shop visibility
            if (request.ShopInternet.HasValue)
            {
                updates.Add("tw_SklepInternet = @shopInternet");
                parameters.Add("@shopInternet", request.ShopInternet.Value);
            }

            // tw_MechanizmPodzielonejPlatnosci - Split payment mechanism
            if (request.SplitPayment.HasValue)
            {
                updates.Add("tw_MechanizmPodzielonejPlatnosci = @splitPayment");
                parameters.Add("@splitPayment", request.SplitPayment.Value);
            }

            // tw_Pole1 - Material (max 50 chars)
            if (!string.IsNullOrEmpty(request.Pole1))
            {
                updates.Add("tw_Pole1 = @pole1");
                parameters.Add("@pole1", request.Pole1.Length > 50 ? request.Pole1.Substring(0, 50) : request.Pole1);
            }

            // tw_Pole2 - Stock location CSV (max 50 chars)
            if (!string.IsNullOrEmpty(request.Pole2))
            {
                updates.Add("tw_Pole2 = @pole2");
                parameters.Add("@pole2", request.Pole2.Length > 50 ? request.Pole2.Substring(0, 50) : request.Pole2);
            }

            // tw_Pole3 - Defect symbol (max 50 chars)
            if (!string.IsNullOrEmpty(request.Pole3))
            {
                updates.Add("tw_Pole3 = @pole3");
                parameters.Add("@pole3", request.Pole3.Length > 50 ? request.Pole3.Substring(0, 50) : request.Pole3);
            }

            // tw_Pole4 - Application (max 50 chars)
            if (!string.IsNullOrEmpty(request.Pole4))
            {
                updates.Add("tw_Pole4 = @pole4");
                parameters.Add("@pole4", request.Pole4.Length > 50 ? request.Pole4.Substring(0, 50) : request.Pole4);
            }

            // tw_Pole5 - CN Code (max 50 chars)
            if (!string.IsNullOrEmpty(request.Pole5))
            {
                updates.Add("tw_Pole5 = @pole5");
                parameters.Add("@pole5", request.Pole5.Length > 50 ? request.Pole5.Substring(0, 50) : request.Pole5);
            }

            // tw_DostSymbol - Supplier code (max 20 chars)
            if (!string.IsNullOrEmpty(request.SupplierCode))
            {
                updates.Add("tw_DostSymbol = @supplierCode");
                parameters.Add("@supplierCode", request.SupplierCode.Length > 20 ? request.SupplierCode.Substring(0, 20) : request.SupplierCode);
            }

            // tw_Pole8 - Parent SKU for variants (max 50 chars)
            if (!string.IsNullOrEmpty(request.Pole8))
            {
                updates.Add("tw_Pole8 = @pole8");
                parameters.Add("@pole8", request.Pole8.Length > 50 ? request.Pole8.Substring(0, 50) : request.Pole8);
            }

            var rowsAffected = 0;

            if (updates.Count > 0)
            {
                var updateSql = $"UPDATE tw__Towar SET {string.Join(", ", updates)} WHERE tw_Id = @id";
                rowsAffected += await conn.ExecuteAsync(updateSql, parameters, transaction);
            }

            // Update prices if provided
            if (request.PriceNet.HasValue || request.PriceGross.HasValue ||
                request.PricesNet?.Count > 0 || request.PricesGross?.Count > 0)
            {
                var priceUpdates = new List<string>();
                var priceParams = new DynamicParameters();
                priceParams.Add("@productId", productId);

                for (int i = 0; i <= 10; i++)
                {
                    decimal? netPrice = request.PricesNet?.GetValueOrDefault(i);
                    decimal? grossPrice = request.PricesGross?.GetValueOrDefault(i);

                    // Use single price values as fallback for level 0
                    if (i == 0)
                    {
                        netPrice ??= request.PriceNet;
                        grossPrice ??= request.PriceGross;
                    }

                    if (netPrice.HasValue)
                    {
                        priceUpdates.Add($"tc_CenaNetto{i} = @net{i}");
                        priceParams.Add($"@net{i}", netPrice.Value);
                    }

                    if (grossPrice.HasValue)
                    {
                        priceUpdates.Add($"tc_CenaBrutto{i} = @gross{i}");
                        priceParams.Add($"@gross{i}", grossPrice.Value);
                    }
                }

                if (priceUpdates.Count > 0)
                {
                    var priceSql = $"UPDATE tw_Cena SET {string.Join(", ", priceUpdates)} WHERE tc_IdTowar = @productId";
                    rowsAffected += await conn.ExecuteAsync(priceSql, priceParams, transaction);
                }
            }

            transaction.Commit();

            _logger.LogInformation(
                "Updated product ID={Id} (SKU={Sku}), rows affected: {Rows}",
                productId, existingSku, rowsAffected);

            return new ProductWriteResponse
            {
                Success = true,
                Timestamp = DateTime.Now,
                ProductId = productId,
                Sku = existingSku,
                Action = "updated",
                Message = $"Product updated successfully (ID: {productId})",
                RowsAffected = rowsAffected
            };
        }
        catch (Exception ex)
        {
            transaction.Rollback();

            _logger.LogError(ex,
                "Error updating product ID={Id}: {Message}",
                productId, ex.Message);

            return new ProductWriteResponse
            {
                Success = false,
                Timestamp = DateTime.Now,
                ProductId = productId,
                Error = ex.Message,
                ErrorCode = "UPDATE_ERROR"
            };
        }
    }

    public async Task<ProductWriteResponse> UpdateProductBySkuAsync(string sku, ProductWriteRequest request)
    {
        var productId = await GetProductIdBySkuAsync(sku);

        if (!productId.HasValue)
        {
            return new ProductWriteResponse
            {
                Success = false,
                Timestamp = DateTime.Now,
                Sku = sku,
                Error = $"Product with SKU '{sku}' not found",
                ErrorCode = "PRODUCT_NOT_FOUND"
            };
        }

        return await UpdateProductAsync(productId.Value, request);
    }

    /// <summary>
    /// Updates stock quantities for a product by product ID.
    /// Uses DirectSQL UPDATE on tw_Stan table.
    /// If warehouse record doesn't exist, INSERT is used.
    /// </summary>
    public async Task<StockWriteResponse> UpdateStockAsync(int productId, StockWriteRequest request)
    {
        if (request.Stock == null || request.Stock.Count == 0)
        {
            return new StockWriteResponse
            {
                Success = false,
                Timestamp = DateTime.Now,
                ProductId = productId,
                Error = "No stock data provided",
                ErrorCode = "VALIDATION_ERROR"
            };
        }

        using var conn = GetConnection();
        await conn.OpenAsync();

        // Check if product exists
        var existingSku = await conn.ExecuteScalarAsync<string?>(
            "SELECT tw_Symbol FROM tw__Towar WHERE tw_Id = @id AND tw_Usuniety = 0",
            new { id = productId });

        if (existingSku == null)
        {
            return new StockWriteResponse
            {
                Success = false,
                Timestamp = DateTime.Now,
                ProductId = productId,
                Error = $"Product with ID {productId} not found",
                ErrorCode = "PRODUCT_NOT_FOUND"
            };
        }

        using var transaction = conn.BeginTransaction();

        try
        {
            var rowsAffected = 0;
            var updatedWarehouses = new List<int>();
            var insertedWarehouses = new List<int>();

            foreach (var kvp in request.Stock)
            {
                var warehouseId = kvp.Key;
                var stockData = kvp.Value;

                // Check if record exists
                var existingCount = await conn.ExecuteScalarAsync<int>(
                    "SELECT COUNT(*) FROM tw_Stan WHERE st_TowId = @productId AND st_MagId = @warehouseId",
                    new { productId, warehouseId }, transaction);

                if (existingCount > 0)
                {
                    // Build dynamic UPDATE for existing record
                    var updates = new List<string>();
                    var parameters = new DynamicParameters();
                    parameters.Add("@productId", productId);
                    parameters.Add("@warehouseId", warehouseId);

                    if (stockData.Quantity.HasValue)
                    {
                        updates.Add("st_Stan = @quantity");
                        parameters.Add("@quantity", stockData.Quantity.Value);
                    }
                    if (stockData.Min.HasValue)
                    {
                        updates.Add("st_StanMin = @min");
                        parameters.Add("@min", stockData.Min.Value);
                    }
                    if (stockData.Max.HasValue)
                    {
                        updates.Add("st_StanMax = @max");
                        parameters.Add("@max", stockData.Max.Value);
                    }
                    if (stockData.Reserved.HasValue)
                    {
                        updates.Add("st_StanRez = @reserved");
                        parameters.Add("@reserved", stockData.Reserved.Value);
                    }

                    if (updates.Count > 0)
                    {
                        var updateSql = $@"
                            UPDATE tw_Stan
                            SET {string.Join(", ", updates)}
                            WHERE st_TowId = @productId AND st_MagId = @warehouseId";

                        rowsAffected += await conn.ExecuteAsync(updateSql, parameters, transaction);
                        updatedWarehouses.Add(warehouseId);
                    }
                }
                else
                {
                    // INSERT new record with all values
                    var insertSql = @"
                        INSERT INTO tw_Stan (st_TowId, st_MagId, st_Stan, st_StanMin, st_StanRez, st_StanMax)
                        VALUES (@productId, @warehouseId, @quantity, @min, @reserved, @max)";

                    rowsAffected += await conn.ExecuteAsync(insertSql, new
                    {
                        productId,
                        warehouseId,
                        quantity = stockData.Quantity ?? 0,
                        min = stockData.Min ?? 0,
                        max = stockData.Max ?? 0,
                        reserved = stockData.Reserved ?? 0
                    }, transaction);

                    insertedWarehouses.Add(warehouseId);
                }
            }

            transaction.Commit();

            _logger.LogInformation(
                "Updated stock for product ID={Id} (SKU={Sku}): Updated warehouses: [{Updated}], Inserted warehouses: [{Inserted}]",
                productId, existingSku, string.Join(", ", updatedWarehouses), string.Join(", ", insertedWarehouses));

            return new StockWriteResponse
            {
                Success = true,
                Timestamp = DateTime.Now,
                ProductId = productId,
                Sku = existingSku,
                Action = "stock_updated",
                Message = $"Stock updated for {request.Stock.Count} warehouse(s). Updated: [{string.Join(", ", updatedWarehouses)}], Inserted: [{string.Join(", ", insertedWarehouses)}]",
                RowsAffected = rowsAffected
            };
        }
        catch (Exception ex)
        {
            transaction.Rollback();

            _logger.LogError(ex,
                "Error updating stock for product ID={Id}: {Message}",
                productId, ex.Message);

            return new StockWriteResponse
            {
                Success = false,
                Timestamp = DateTime.Now,
                ProductId = productId,
                Error = ex.Message,
                ErrorCode = "STOCK_UPDATE_ERROR"
            };
        }
    }

    /// <summary>
    /// Updates stock quantities for a product by SKU.
    /// </summary>
    public async Task<StockWriteResponse> UpdateStockBySkuAsync(string sku, StockWriteRequest request)
    {
        var productId = await GetProductIdBySkuAsync(sku);

        if (!productId.HasValue)
        {
            return new StockWriteResponse
            {
                Success = false,
                Timestamp = DateTime.Now,
                Sku = sku,
                Error = $"Product with SKU '{sku}' not found",
                ErrorCode = "PRODUCT_NOT_FOUND"
            };
        }

        return await UpdateStockAsync(productId.Value, request);
    }

    public async Task<bool> ProductExistsAsync(string sku)
    {
        using var conn = GetConnection();

        var count = await conn.ExecuteScalarAsync<int>(
            "SELECT COUNT(*) FROM tw__Towar WHERE tw_Symbol = @sku AND tw_Usuniety = 0",
            new { sku });

        return count > 0;
    }

    public async Task<int?> GetProductIdBySkuAsync(string sku)
    {
        using var conn = GetConnection();

        return await conn.ExecuteScalarAsync<int?>(
            "SELECT tw_Id FROM tw__Towar WHERE tw_Symbol = @sku AND tw_Usuniety = 0",
            new { sku });
    }
}
