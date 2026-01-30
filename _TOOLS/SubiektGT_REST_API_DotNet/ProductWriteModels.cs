namespace SubiektApi;

/// <summary>
/// Request model for creating/updating a product in Subiekt GT.
///
/// Field mappings:
/// - Sku -> tw_Symbol (max 20 chars)
/// - Name -> tw_Nazwa (max 50 chars)
/// - Description -> tw_Opis
/// - Ean -> tw_PodstKodKresk (max 20 chars)
/// - Unit -> tw_JednMiary (max 10 chars, e.g. "szt", "kg")
/// - Pkwiu -> tw_PKWiU
/// - Weight -> tw_Masa (decimal, in kg)
/// - VatRateId -> tw_IdVatSp (FK to sl_StawkaVAT)
/// - GroupId -> tw_IdGrupa (FK to sl_GrupaTw)
/// - Prices -> tc_CenaNetto0..9 (via tw_Cena table)
/// </summary>
public class ProductWriteRequest
{
    /// <summary>
    /// Product SKU/Symbol (required for create, max 20 chars).
    /// Maps to tw_Symbol in Subiekt GT.
    /// </summary>
    public string? Sku { get; set; }

    /// <summary>
    /// Product name (required for create, max 50 chars).
    /// Maps to tw_Nazwa in Subiekt GT.
    /// </summary>
    public string? Name { get; set; }

    /// <summary>
    /// Product description.
    /// Maps to tw_Opis in Subiekt GT.
    /// </summary>
    public string? Description { get; set; }

    /// <summary>
    /// EAN/barcode (max 20 chars).
    /// Maps to tw_PodstKodKresk in Subiekt GT.
    /// </summary>
    public string? Ean { get; set; }

    /// <summary>
    /// Measurement unit (max 10 chars, e.g. "szt", "kg", "m").
    /// Maps to tw_JednMiary in Subiekt GT.
    /// </summary>
    public string? Unit { get; set; }

    /// <summary>
    /// PKWiU classification code.
    /// Maps to tw_PKWiU in Subiekt GT.
    /// </summary>
    public string? Pkwiu { get; set; }

    /// <summary>
    /// Weight in kilograms.
    /// Maps to tw_Masa in Subiekt GT.
    /// </summary>
    public decimal? Weight { get; set; }

    /// <summary>
    /// VAT rate ID (foreign key to sl_StawkaVAT).
    /// Maps to tw_IdVatSp in Subiekt GT.
    /// </summary>
    public int? VatRateId { get; set; }

    /// <summary>
    /// Product group ID (foreign key to sl_GrupaTw).
    /// Maps to tw_IdGrupa in Subiekt GT.
    /// </summary>
    public int? GroupId { get; set; }

    /// <summary>
    /// Manufacturer/Supplier ID (foreign key to kh__Kontrahent).
    /// Maps to tw_IdPodstDostawca in Subiekt GT.
    /// LEGACY: Use SupplierContractorId/ManufacturerContractorId for explicit mapping.
    /// </summary>
    public int? ManufacturerId { get; set; }

    /// <summary>
    /// Supplier contractor ID (foreign key to kh__Kontrahent).
    /// Maps to tw_IdPodstDostawca in Subiekt GT.
    /// Used by BusinessPartner mapping (ST9).
    /// </summary>
    public int? SupplierContractorId { get; set; }

    /// <summary>
    /// Manufacturer contractor ID (foreign key to kh__Kontrahent).
    /// Maps to tw_IdProducenta in Subiekt GT.
    /// Used by BusinessPartner mapping (ST9).
    /// </summary>
    public int? ManufacturerContractorId { get; set; }

    /// <summary>
    /// Prices by level (0-9).
    /// Level 0 = Detaliczna, 1-9 = custom price levels.
    /// Names are configured in tw_Parametr table.
    ///
    /// IMPORTANT: Stock cannot be set here - only through documents!
    /// </summary>
    public Dictionary<int, PriceData>? Prices { get; set; }

    /// <summary>
    /// Whether the product is active.
    /// Maps to tw_Zablokowany (inverted logic: Active=true -> Zablokowany=0)
    /// </summary>
    public bool? IsActive { get; set; }

    /// <summary>
    /// Product-level minimum stock (global, not per-warehouse).
    /// Maps to tw_StanMin in Subiekt GT (tw__Towar table).
    /// Note: In Subiekt, all warehouses share the same minimum stock at product level.
    /// PPM should send the LOWEST minimum from all warehouses.
    /// </summary>
    public decimal? MinimumStock { get; set; }

    /// <summary>
    /// Unit for minimum stock display (e.g. "szt.", "kg").
    /// Maps to tw_JednStanMin in Subiekt GT (tw__Towar table).
    /// If not specified, defaults to product's main unit (tw_JednMiary).
    /// </summary>
    public string? MinimumStockUnit { get; set; }

    // ==================== EXTENDED FIELDS (ETAP_08 FAZA 3.4) ====================

    /// <summary>
    /// Whether product is visible in online shop.
    /// Maps to tw_SklepInternet in Subiekt GT.
    /// </summary>
    public bool? ShopInternet { get; set; }

    /// <summary>
    /// Whether product uses split payment mechanism (MPP - Mechanizm Podzielonej Platnosci).
    /// Maps to tw_MechanizmPodzielonejPlatnosci in Subiekt GT.
    /// </summary>
    public bool? SplitPayment { get; set; }

    /// <summary>
    /// Custom field 1 - Material.
    /// Maps to tw_Pole1 in Subiekt GT (max 50 chars).
    /// </summary>
    public string? Pole1 { get; set; }

    /// <summary>
    /// Custom field 2 - Stock location (CSV format: "WAREHOUSE1:LOCATION1,WAREHOUSE2:LOCATION2").
    /// Maps to tw_Pole2 in Subiekt GT (max 50 chars).
    /// </summary>
    public string? Pole2 { get; set; }

    /// <summary>
    /// Custom field 3 - Defect symbol.
    /// Maps to tw_Pole3 in Subiekt GT (max 50 chars).
    /// </summary>
    public string? Pole3 { get; set; }

    /// <summary>
    /// Custom field 4 - Application.
    /// Maps to tw_Pole4 in Subiekt GT (max 50 chars).
    /// </summary>
    public string? Pole4 { get; set; }

    /// <summary>
    /// Custom field 5 - CN Code (Combined Nomenclature for customs).
    /// Maps to tw_Pole5 in Subiekt GT (max 50 chars).
    /// </summary>
    public string? Pole5 { get; set; }

    /// <summary>
    /// Supplier product code.
    /// Maps to tw_DostSymbol in Subiekt GT (max 20 chars).
    /// </summary>
    public string? SupplierCode { get; set; }

    /// <summary>
    /// Custom field 8 - Parent SKU for variant products.
    /// Maps to tw_Pole8 in Subiekt GT (max 50 chars).
    /// Used to link variant products to their parent product in PPM.
    /// </summary>
    public string? Pole8 { get; set; }

    // ==================== SIMPLIFIED PRICE FIELDS (alternative to Prices dictionary) ====================

    /// <summary>
    /// Default net price (for price level 0).
    /// Alternative to using Prices dictionary.
    /// </summary>
    public decimal? PriceNet { get; set; }

    /// <summary>
    /// Default gross price (for price level 0).
    /// Alternative to using Prices dictionary.
    /// </summary>
    public decimal? PriceGross { get; set; }

    /// <summary>
    /// Net prices by level (0-10).
    /// Alternative to using Prices dictionary with PriceData objects.
    /// </summary>
    public Dictionary<int, decimal>? PricesNet { get; set; }

    /// <summary>
    /// Gross prices by level (0-10).
    /// Alternative to using Prices dictionary with PriceData objects.
    /// </summary>
    public Dictionary<int, decimal>? PricesGross { get; set; }
}

/// <summary>
/// Price data for a specific price level.
/// </summary>
public class PriceData
{
    /// <summary>
    /// Net price (without VAT).
    /// </summary>
    public decimal Net { get; set; }

    /// <summary>
    /// Gross price (with VAT). Optional - can be calculated from Net + VAT rate.
    /// </summary>
    public decimal? Gross { get; set; }
}

/// <summary>
/// Response model for product write operations.
/// </summary>
public class ProductWriteResponse
{
    /// <summary>
    /// Whether the operation was successful.
    /// </summary>
    public bool Success { get; set; }

    /// <summary>
    /// Timestamp of the operation.
    /// </summary>
    public DateTime Timestamp { get; set; }

    /// <summary>
    /// Product ID in Subiekt GT (tw_Id).
    /// </summary>
    public int? ProductId { get; set; }

    /// <summary>
    /// Product SKU/Symbol.
    /// </summary>
    public string? Sku { get; set; }

    /// <summary>
    /// Action performed: "created", "updated", "no_changes".
    /// </summary>
    public string? Action { get; set; }

    /// <summary>
    /// Human-readable message.
    /// </summary>
    public string? Message { get; set; }

    /// <summary>
    /// Number of rows affected (for SQL operations).
    /// </summary>
    public int? RowsAffected { get; set; }

    /// <summary>
    /// Error code if operation failed.
    /// Possible codes:
    /// - VALIDATION_ERROR: Request validation failed
    /// - DUPLICATE_SKU: Product with this SKU already exists
    /// - PRODUCT_NOT_FOUND: Product not found for update
    /// - SFERA_ERROR: Sfera GT API error
    /// - SFERA_CONNECTION_FAILED: Cannot connect to Sfera GT
    /// - SFERA_REQUIRED: Operation requires Sfera (cannot use DirectSQL)
    /// - CREATE_FAILED: Product creation failed
    /// - UPDATE_FAILED: Product update failed
    /// - INVALID_VAT_RATE: VAT rate ID not found
    /// </summary>
    public string? ErrorCode { get; set; }

    /// <summary>
    /// Error message if operation failed.
    /// </summary>
    public string? Error { get; set; }

    /// <summary>
    /// Additional details about the operation.
    /// </summary>
    public Dictionary<string, object>? Details { get; set; }
}

/// <summary>
/// Request for batch product operations.
/// </summary>
public class BatchProductWriteRequest
{
    /// <summary>
    /// List of products to create or update.
    /// </summary>
    public List<ProductWriteRequest> Products { get; set; } = new();

    /// <summary>
    /// Whether to continue on error or stop at first failure.
    /// </summary>
    public bool ContinueOnError { get; set; } = true;

    /// <summary>
    /// Whether to update existing products (matched by SKU) or skip them.
    /// </summary>
    public bool UpdateExisting { get; set; } = true;
}

/// <summary>
/// Response for batch product operations.
/// </summary>
public class BatchProductWriteResponse
{
    public bool Success { get; set; }
    public DateTime Timestamp { get; set; }
    public int TotalRequested { get; set; }
    public int Created { get; set; }
    public int Updated { get; set; }
    public int Skipped { get; set; }
    public int Failed { get; set; }
    public List<ProductWriteResponse> Results { get; set; } = new();
    public string? Error { get; set; }
}
