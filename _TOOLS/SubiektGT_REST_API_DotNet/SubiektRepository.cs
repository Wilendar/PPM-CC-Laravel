using Microsoft.Data.SqlClient;
using Dapper;
using System.Diagnostics;

namespace SubiektApi;

public interface ISubiektRepository
{
    Task<DatabaseStats> GetDatabaseStatsAsync();
    Task<(IEnumerable<Product> Products, int TotalCount)> GetProductsAsync(int page, int pageSize, int priceLevel, int warehouseId, string? sku, string? name);
    Task<Product?> GetProductByIdAsync(int id, int priceLevel, int warehouseId);
    Task<Product?> GetProductBySkuAsync(string sku, int priceLevel, int warehouseId);
    Task<(IEnumerable<Stock> Stock, int TotalCount)> GetAllStockAsync(int page, int pageSize);
    Task<IEnumerable<Stock>> GetStockByProductIdAsync(int productId);
    Task<IEnumerable<ProductPrice>> GetPricesByProductIdAsync(int productId);
    Task<IEnumerable<Warehouse>> GetWarehousesAsync();
    Task<IEnumerable<PriceLevel>> GetPriceLevelsAsync();
    Task<IEnumerable<VatRate>> GetVatRatesAsync();
    Task<IEnumerable<TableInfo>> GetDatabaseSchemaAsync();
    Task<IEnumerable<ColumnInfo>> GetTableColumnsAsync(string tableName);
}

public class SubiektRepository : ISubiektRepository
{
    private readonly string _connectionString;

    public SubiektRepository(IConfiguration config)
    {
        _connectionString = config.GetConnectionString("SubiektGT")
            ?? throw new InvalidOperationException("Connection string 'SubiektGT' not found");
    }

    private SqlConnection GetConnection() => new SqlConnection(_connectionString);

    // Returns price column based on level (0-10)
    private string GetPriceColumn(int level, bool gross)
    {
        var prefix = gross ? "tc_CenaBrutto" : "tc_CenaNetto";
        return $"{prefix}{Math.Clamp(level, 0, 10)}";
    }

    public async Task<DatabaseStats> GetDatabaseStatsAsync()
    {
        var sw = Stopwatch.StartNew();

        using var conn = GetConnection();
        await conn.OpenAsync();

        var stats = new DatabaseStats
        {
            DatabaseName = conn.Database,
            ServerVersion = conn.ServerVersion
        };

        // tw_Usuniety = 0 AND tw_Zablokowany = 0 means active product
        stats.ProductsCount = await conn.ExecuteScalarAsync<int>(
            "SELECT COUNT(*) FROM tw__Towar WHERE tw_Usuniety = 0 AND tw_Zablokowany = 0");

        sw.Stop();
        stats.ResponseTimeMs = sw.Elapsed.TotalMilliseconds;

        return stats;
    }

    public async Task<(IEnumerable<Product> Products, int TotalCount)> GetProductsAsync(
        int page, int pageSize, int priceLevel, int warehouseId,
        string? sku, string? name)
    {
        using var conn = GetConnection();
        await conn.OpenAsync();

        // Active = not deleted AND not blocked
        var whereClause = "WHERE t.tw_Usuniety = 0 AND t.tw_Zablokowany = 0";
        var parameters = new DynamicParameters();
        parameters.Add("@warehouseId", warehouseId);
        parameters.Add("@offset", (page - 1) * pageSize);
        parameters.Add("@pageSize", pageSize);

        if (!string.IsNullOrEmpty(sku))
        {
            whereClause += " AND t.tw_Symbol LIKE @sku";
            parameters.Add("@sku", $"%{sku}%");
        }
        if (!string.IsNullOrEmpty(name))
        {
            whereClause += " AND t.tw_Nazwa LIKE @name";
            parameters.Add("@name", $"%{name}%");
        }

        // Count query
        var countSql = $"SELECT COUNT(*) FROM tw__Towar t {whereClause}";
        var totalCount = await conn.ExecuteScalarAsync<int>(countSql, parameters);

        // Price columns based on level
        var priceNetCol = GetPriceColumn(priceLevel, false);
        var priceGrossCol = GetPriceColumn(priceLevel, true);

        // Data query
        var sql = $@"
            SELECT
                t.tw_Id AS Id,
                t.tw_Symbol AS Sku,
                t.tw_Nazwa AS Name,
                t.tw_Opis AS Description,
                t.tw_JednMiary AS Unit,
                t.tw_PKWiU AS Pkwiu,
                t.tw_PodstKodKresk AS Ean,
                t.tw_Masa AS Weight,
                ISNULL(c.{priceNetCol}, 0) AS PriceNet,
                ISNULL(c.{priceGrossCol}, 0) AS PriceGross,
                ISNULL(s.st_Stan, 0) AS Stock,
                ISNULL(s.st_StanRez, 0) AS StockReserved,
                v.vat_Stawka AS VatRate,
                CASE WHEN t.tw_Usuniety = 0 AND t.tw_Zablokowany = 0 THEN 1 ELSE 0 END AS IsActive,
                gr.grt_Nazwa AS GroupName,
                kh.kh_Symbol AS ManufacturerName
            FROM tw__Towar t
            LEFT JOIN tw_Cena c ON t.tw_Id = c.tc_IdTowar
            LEFT JOIN tw_Stan s ON t.tw_Id = s.st_TowId AND s.st_MagId = @warehouseId
            LEFT JOIN sl_StawkaVAT v ON t.tw_IdVatSp = v.vat_Id
            LEFT JOIN sl_GrupaTw gr ON t.tw_IdGrupa = gr.grt_Id
            LEFT JOIN kh__Kontrahent kh ON t.tw_IdPodstDostawca = kh.kh_Id
            {whereClause}
            ORDER BY t.tw_Id
            OFFSET @offset ROWS FETCH NEXT @pageSize ROWS ONLY";

        var products = await conn.QueryAsync<Product>(sql, parameters);

        return (products, totalCount);
    }

    public async Task<Product?> GetProductByIdAsync(int id, int priceLevel, int warehouseId)
    {
        using var conn = GetConnection();

        var priceNetCol = GetPriceColumn(priceLevel, false);
        var priceGrossCol = GetPriceColumn(priceLevel, true);

        var sql = $@"
            SELECT
                t.tw_Id AS Id,
                t.tw_Symbol AS Sku,
                t.tw_Nazwa AS Name,
                t.tw_Opis AS Description,
                t.tw_JednMiary AS Unit,
                t.tw_PKWiU AS Pkwiu,
                t.tw_PodstKodKresk AS Ean,
                t.tw_Masa AS Weight,
                ISNULL(c.{priceNetCol}, 0) AS PriceNet,
                ISNULL(c.{priceGrossCol}, 0) AS PriceGross,
                ISNULL(s.st_Stan, 0) AS Stock,
                ISNULL(s.st_StanRez, 0) AS StockReserved,
                v.vat_Stawka AS VatRate,
                CASE WHEN t.tw_Usuniety = 0 AND t.tw_Zablokowany = 0 THEN 1 ELSE 0 END AS IsActive,
                gr.grt_Nazwa AS GroupName,
                kh.kh_Symbol AS ManufacturerName
            FROM tw__Towar t
            LEFT JOIN tw_Cena c ON t.tw_Id = c.tc_IdTowar
            LEFT JOIN tw_Stan s ON t.tw_Id = s.st_TowId AND s.st_MagId = @warehouseId
            LEFT JOIN sl_StawkaVAT v ON t.tw_IdVatSp = v.vat_Id
            LEFT JOIN sl_GrupaTw gr ON t.tw_IdGrupa = gr.grt_Id
            LEFT JOIN kh__Kontrahent kh ON t.tw_IdPodstDostawca = kh.kh_Id
            WHERE t.tw_Id = @id";

        return await conn.QueryFirstOrDefaultAsync<Product>(sql, new { id, warehouseId });
    }

    public async Task<Product?> GetProductBySkuAsync(string sku, int priceLevel, int warehouseId)
    {
        using var conn = GetConnection();

        var priceNetCol = GetPriceColumn(priceLevel, false);
        var priceGrossCol = GetPriceColumn(priceLevel, true);

        var sql = $@"
            SELECT
                t.tw_Id AS Id,
                t.tw_Symbol AS Sku,
                t.tw_Nazwa AS Name,
                t.tw_Opis AS Description,
                t.tw_JednMiary AS Unit,
                t.tw_PKWiU AS Pkwiu,
                t.tw_PodstKodKresk AS Ean,
                t.tw_Masa AS Weight,
                ISNULL(c.{priceNetCol}, 0) AS PriceNet,
                ISNULL(c.{priceGrossCol}, 0) AS PriceGross,
                ISNULL(s.st_Stan, 0) AS Stock,
                ISNULL(s.st_StanRez, 0) AS StockReserved,
                v.vat_Stawka AS VatRate,
                CASE WHEN t.tw_Usuniety = 0 AND t.tw_Zablokowany = 0 THEN 1 ELSE 0 END AS IsActive,
                gr.grt_Nazwa AS GroupName,
                kh.kh_Symbol AS ManufacturerName
            FROM tw__Towar t
            LEFT JOIN tw_Cena c ON t.tw_Id = c.tc_IdTowar
            LEFT JOIN tw_Stan s ON t.tw_Id = s.st_TowId AND s.st_MagId = @warehouseId
            LEFT JOIN sl_StawkaVAT v ON t.tw_IdVatSp = v.vat_Id
            LEFT JOIN sl_GrupaTw gr ON t.tw_IdGrupa = gr.grt_Id
            LEFT JOIN kh__Kontrahent kh ON t.tw_IdPodstDostawca = kh.kh_Id
            WHERE t.tw_Symbol = @sku AND t.tw_Usuniety = 0";

        return await conn.QueryFirstOrDefaultAsync<Product>(sql, new { sku, warehouseId });
    }

    public async Task<(IEnumerable<Stock> Stock, int TotalCount)> GetAllStockAsync(int page, int pageSize)
    {
        using var conn = GetConnection();

        var countSql = "SELECT COUNT(*) FROM tw_Stan";
        var totalCount = await conn.ExecuteScalarAsync<int>(countSql);

        var sql = @"
            SELECT
                s.st_TowId AS ProductId,
                t.tw_Symbol AS Sku,
                s.st_MagId AS WarehouseId,
                m.mag_Nazwa AS WarehouseName,
                s.st_Stan AS Quantity,
                s.st_StanRez AS Reserved
            FROM tw_Stan s
            JOIN tw__Towar t ON s.st_TowId = t.tw_Id
            JOIN sl_Magazyn m ON s.st_MagId = m.mag_Id
            ORDER BY s.st_TowId
            OFFSET @offset ROWS FETCH NEXT @pageSize ROWS ONLY";

        var stock = await conn.QueryAsync<Stock>(sql, new { offset = (page - 1) * pageSize, pageSize });

        return (stock, totalCount);
    }

    public async Task<IEnumerable<Stock>> GetStockByProductIdAsync(int productId)
    {
        using var conn = GetConnection();

        var sql = @"
            SELECT
                s.st_TowId AS ProductId,
                t.tw_Symbol AS Sku,
                s.st_MagId AS WarehouseId,
                m.mag_Nazwa AS WarehouseName,
                s.st_Stan AS Quantity,
                s.st_StanRez AS Reserved
            FROM tw_Stan s
            JOIN tw__Towar t ON s.st_TowId = t.tw_Id
            JOIN sl_Magazyn m ON s.st_MagId = m.mag_Id
            WHERE s.st_TowId = @productId";

        return await conn.QueryAsync<Stock>(sql, new { productId });
    }

    public async Task<IEnumerable<ProductPrice>> GetPricesByProductIdAsync(int productId)
    {
        using var conn = GetConnection();

        // Get price level names from tw_Parametr
        var nameSql = @"
            SELECT TOP 1
                twp_NazwaCeny1 AS Name1, twp_NazwaCeny2 AS Name2, twp_NazwaCeny3 AS Name3,
                twp_NazwaCeny4 AS Name4, twp_NazwaCeny5 AS Name5, twp_NazwaCeny6 AS Name6,
                twp_NazwaCeny7 AS Name7, twp_NazwaCeny8 AS Name8, twp_NazwaCeny9 AS Name9,
                twp_NazwaCeny10 AS Name10
            FROM tw_Parametr";

        var names = await conn.QueryFirstOrDefaultAsync<dynamic>(nameSql);

        // CORRECT MAPPING: twp_NazwaCeny[N] → Level N (tc_CenaNetto[N])
        // Level 0 (tc_CenaNetto0) is UNUSED when price groups are active - always 0.0000
        var levelNames = new Dictionary<int, string>
        {
            { 0, "(Nieużywany)" },                   // tc_CenaNetto0 - unused with price groups
            { 1, names?.Name1 ?? "Cena 1" },         // twp_NazwaCeny1 → tc_CenaNetto1
            { 2, names?.Name2 ?? "Cena 2" },         // twp_NazwaCeny2 → tc_CenaNetto2
            { 3, names?.Name3 ?? "Cena 3" },         // twp_NazwaCeny3 → tc_CenaNetto3
            { 4, names?.Name4 ?? "Cena 4" },         // twp_NazwaCeny4 → tc_CenaNetto4
            { 5, names?.Name5 ?? "Cena 5" },         // twp_NazwaCeny5 → tc_CenaNetto5
            { 6, names?.Name6 ?? "Cena 6" },         // twp_NazwaCeny6 → tc_CenaNetto6
            { 7, names?.Name7 ?? "Cena 7" },         // twp_NazwaCeny7 → tc_CenaNetto7
            { 8, names?.Name8 ?? "Cena 8" },         // twp_NazwaCeny8 → tc_CenaNetto8
            { 9, names?.Name9 ?? "Cena 9" },         // twp_NazwaCeny9 → tc_CenaNetto9
            { 10, names?.Name10 ?? "Cena 10" }       // twp_NazwaCeny10 → tc_CenaNetto10
        };

        // Get all 11 price levels (0-10) for the product
        var priceSql = @"
            SELECT
                c.tc_IdTowar AS ProductId,
                t.tw_Symbol AS Sku,
                c.tc_CenaNetto0 AS Net0, c.tc_CenaBrutto0 AS Gross0,
                c.tc_CenaNetto1 AS Net1, c.tc_CenaBrutto1 AS Gross1,
                c.tc_CenaNetto2 AS Net2, c.tc_CenaBrutto2 AS Gross2,
                c.tc_CenaNetto3 AS Net3, c.tc_CenaBrutto3 AS Gross3,
                c.tc_CenaNetto4 AS Net4, c.tc_CenaBrutto4 AS Gross4,
                c.tc_CenaNetto5 AS Net5, c.tc_CenaBrutto5 AS Gross5,
                c.tc_CenaNetto6 AS Net6, c.tc_CenaBrutto6 AS Gross6,
                c.tc_CenaNetto7 AS Net7, c.tc_CenaBrutto7 AS Gross7,
                c.tc_CenaNetto8 AS Net8, c.tc_CenaBrutto8 AS Gross8,
                c.tc_CenaNetto9 AS Net9, c.tc_CenaBrutto9 AS Gross9,
                c.tc_CenaNetto10 AS Net10, c.tc_CenaBrutto10 AS Gross10
            FROM tw_Cena c
            JOIN tw__Towar t ON c.tc_IdTowar = t.tw_Id
            WHERE c.tc_IdTowar = @productId";

        var priceData = await conn.QueryFirstOrDefaultAsync<dynamic>(priceSql, new { productId });

        if (priceData == null)
            return Enumerable.Empty<ProductPrice>();

        var prices = new List<ProductPrice>();
        // Loop through levels 0-10 (11 total) - level 0 is unused but included for completeness
        for (int i = 0; i <= 10; i++)
        {
            var netProp = $"Net{i}";
            var grossProp = $"Gross{i}";

            decimal net = 0, gross = 0;
            var priceDict = (IDictionary<string, object>)priceData;
            if (priceDict.ContainsKey(netProp) && priceDict[netProp] != null)
                net = Convert.ToDecimal(priceDict[netProp]);
            if (priceDict.ContainsKey(grossProp) && priceDict[grossProp] != null)
                gross = Convert.ToDecimal(priceDict[grossProp]);

            prices.Add(new ProductPrice
            {
                ProductId = (int)priceData.ProductId,
                Sku = (string)priceData.Sku,
                PriceLevel = i,
                PriceLevelName = levelNames[i],
                PriceNet = net,
                PriceGross = gross
            });
        }

        return prices;
    }

    public async Task<IEnumerable<Warehouse>> GetWarehousesAsync()
    {
        using var conn = GetConnection();

        var sql = @"
            SELECT
                mag_Id AS Id,
                mag_Symbol AS Symbol,
                mag_Nazwa AS Name
            FROM sl_Magazyn
            ORDER BY mag_Id";

        return await conn.QueryAsync<Warehouse>(sql);
    }

    public async Task<IEnumerable<PriceLevel>> GetPriceLevelsAsync()
    {
        // Price level names are stored in tw_Parametr table (columns twp_NazwaCeny1..10)
        // IMPORTANT: When using Subiekt GT with price groups for contractors:
        // - tc_CenaNetto0 (Level 0) is UNUSED (always 0.0000) - base price placeholder
        // - twp_NazwaCeny1 = tc_CenaNetto1 (Level 1) = First actual price group (e.g., "Detaliczna")
        // - twp_NazwaCeny2 = tc_CenaNetto2 (Level 2) = Second price group (e.g., "MRF-MPP")
        // - etc. up to twp_NazwaCeny10 = tc_CenaNetto10 (Level 10)
        using var conn = GetConnection();

        var sql = @"
            SELECT TOP 1
                twp_NazwaCeny1 AS Name1,
                twp_NazwaCeny2 AS Name2,
                twp_NazwaCeny3 AS Name3,
                twp_NazwaCeny4 AS Name4,
                twp_NazwaCeny5 AS Name5,
                twp_NazwaCeny6 AS Name6,
                twp_NazwaCeny7 AS Name7,
                twp_NazwaCeny8 AS Name8,
                twp_NazwaCeny9 AS Name9,
                twp_NazwaCeny10 AS Name10
            FROM tw_Parametr";

        var result = await conn.QueryFirstOrDefaultAsync<dynamic>(sql);

        if (result == null)
        {
            // Fallback if tw_Parametr is empty - still use correct mapping
            return new List<PriceLevel>
            {
                // Level 0 is UNUSED when price groups are active
                new PriceLevel { Id = 1, Name = "Cena 1" },
                new PriceLevel { Id = 2, Name = "Cena 2" },
                new PriceLevel { Id = 3, Name = "Cena 3" },
                new PriceLevel { Id = 4, Name = "Cena 4" },
                new PriceLevel { Id = 5, Name = "Cena 5" },
                new PriceLevel { Id = 6, Name = "Cena 6" },
                new PriceLevel { Id = 7, Name = "Cena 7" },
                new PriceLevel { Id = 8, Name = "Cena 8" },
                new PriceLevel { Id = 9, Name = "Cena 9" },
                new PriceLevel { Id = 10, Name = "Cena 10" }
            };
        }

        // CORRECT MAPPING: twp_NazwaCeny[N] → Level N (tc_CenaNetto[N])
        // Level 0 is intentionally omitted - it's unused with price groups
        return new List<PriceLevel>
        {
            new PriceLevel { Id = 1, Name = result.Name1 ?? "Cena 1" },    // twp_NazwaCeny1 → tc_CenaNetto1
            new PriceLevel { Id = 2, Name = result.Name2 ?? "Cena 2" },    // twp_NazwaCeny2 → tc_CenaNetto2
            new PriceLevel { Id = 3, Name = result.Name3 ?? "Cena 3" },    // twp_NazwaCeny3 → tc_CenaNetto3
            new PriceLevel { Id = 4, Name = result.Name4 ?? "Cena 4" },    // twp_NazwaCeny4 → tc_CenaNetto4
            new PriceLevel { Id = 5, Name = result.Name5 ?? "Cena 5" },    // twp_NazwaCeny5 → tc_CenaNetto5
            new PriceLevel { Id = 6, Name = result.Name6 ?? "Cena 6" },    // twp_NazwaCeny6 → tc_CenaNetto6
            new PriceLevel { Id = 7, Name = result.Name7 ?? "Cena 7" },    // twp_NazwaCeny7 → tc_CenaNetto7
            new PriceLevel { Id = 8, Name = result.Name8 ?? "Cena 8" },    // twp_NazwaCeny8 → tc_CenaNetto8
            new PriceLevel { Id = 9, Name = result.Name9 ?? "Cena 9" },    // twp_NazwaCeny9 → tc_CenaNetto9
            new PriceLevel { Id = 10, Name = result.Name10 ?? "Cena 10" }  // twp_NazwaCeny10 → tc_CenaNetto10
        };
    }

    public async Task<IEnumerable<VatRate>> GetVatRatesAsync()
    {
        using var conn = GetConnection();

        var sql = @"
            SELECT
                vat_Id AS Id,
                vat_Symbol AS Symbol,
                vat_Stawka AS Rate
            FROM sl_StawkaVAT
            ORDER BY vat_Stawka DESC";

        return await conn.QueryAsync<VatRate>(sql);
    }

    public async Task<IEnumerable<TableInfo>> GetDatabaseSchemaAsync()
    {
        using var conn = GetConnection();

        var sql = @"
            SELECT
                t.TABLE_NAME AS TableName,
                t.TABLE_SCHEMA AS TableSchema,
                (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS c WHERE c.TABLE_NAME = t.TABLE_NAME) AS ColumnCount
            FROM INFORMATION_SCHEMA.TABLES t
            WHERE t.TABLE_TYPE = 'BASE TABLE'
            ORDER BY t.TABLE_NAME";

        var tables = await conn.QueryAsync<TableInfo>(sql);

        // Get row counts for key tables only
        var keyTables = new[] { "tw__Towar", "tw_Cena", "tw_Stan", "kh__Kontrahent", "dok__Dokument", "sl_Magazyn" };
        var result = tables.ToList();

        foreach (var table in result.Where(t => keyTables.Contains(t.TableName)))
        {
            try
            {
                var count = await conn.ExecuteScalarAsync<int>($"SELECT COUNT(*) FROM [{table.TableName}]");
                table.RowCount = count;
            }
            catch { table.RowCount = -1; }
        }

        return result;
    }

    public async Task<IEnumerable<ColumnInfo>> GetTableColumnsAsync(string tableName)
    {
        using var conn = GetConnection();

        var sql = @"
            SELECT
                COLUMN_NAME AS ColumnName,
                DATA_TYPE AS DataType,
                CASE WHEN IS_NULLABLE = 'YES' THEN 1 ELSE 0 END AS IsNullable,
                CHARACTER_MAXIMUM_LENGTH AS MaxLength,
                ORDINAL_POSITION AS OrdinalPosition
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = @tableName
            ORDER BY ORDINAL_POSITION";

        return await conn.QueryAsync<ColumnInfo>(sql, new { tableName });
    }
}

// ==================== MODELS ====================

public class DatabaseStats
{
    public string DatabaseName { get; set; } = "";
    public string ServerVersion { get; set; } = "";
    public int ProductsCount { get; set; }
    public double ResponseTimeMs { get; set; }
}

public class Product
{
    public int Id { get; set; }
    public string Sku { get; set; } = "";
    public string Name { get; set; } = "";
    public string? Description { get; set; }
    public string? Unit { get; set; }
    public string? Pkwiu { get; set; }
    public string? Ean { get; set; }
    public decimal? Weight { get; set; }
    public decimal PriceNet { get; set; }
    public decimal PriceGross { get; set; }
    public decimal Stock { get; set; }
    public decimal StockReserved { get; set; }
    public decimal? VatRate { get; set; }
    public bool IsActive { get; set; }
    public string? GroupName { get; set; }
    public string? ManufacturerName { get; set; }
}

public class Stock
{
    public int ProductId { get; set; }
    public string Sku { get; set; } = "";
    public int WarehouseId { get; set; }
    public string WarehouseName { get; set; } = "";
    public decimal Quantity { get; set; }
    public decimal Reserved { get; set; }
}

public class ProductPrice
{
    public int ProductId { get; set; }
    public string Sku { get; set; } = "";
    public int PriceLevel { get; set; }
    public string PriceLevelName { get; set; } = "";
    public decimal PriceNet { get; set; }
    public decimal PriceGross { get; set; }
}

public class Warehouse
{
    public int Id { get; set; }
    public string Symbol { get; set; } = "";
    public string Name { get; set; } = "";
}

public class PriceLevel
{
    public int Id { get; set; }
    public string Name { get; set; } = "";
}

public class VatRate
{
    public int Id { get; set; }
    public string Symbol { get; set; } = "";
    public decimal Rate { get; set; }
}

public class TableInfo
{
    public string TableName { get; set; } = "";
    public string TableSchema { get; set; } = "";
    public int ColumnCount { get; set; }
    public int RowCount { get; set; }
}

public class ColumnInfo
{
    public string ColumnName { get; set; } = "";
    public string DataType { get; set; } = "";
    public bool IsNullable { get; set; }
    public int? MaxLength { get; set; }
    public int OrdinalPosition { get; set; }
}
