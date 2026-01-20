# ERP: Subiekt GT REST API Development

## Source Code Location
```
_TOOLS/SubiektGT_REST_API_DotNet/
```

## Project Structure
| File | Description |
|------|-------------|
| `Program.cs` | Main application, endpoints configuration |
| `SubiektRepository.cs` | Database queries, models |
| `appsettings.json` | Connection string configuration |
| `SubiektApi.csproj` | Project file (.NET 8) |
| `IIS_web.config` | IIS deployment config |
| `README.md` | Documentation |

## Build & Deploy Process

### 1. Build Locally
```powershell
cd "_TOOLS/SubiektGT_REST_API_DotNet"
dotnet publish -c Release -o ./publish
```

### 2. Upload to Server (EXEA Windows Server)
User must manually upload `publish/` folder contents to:
```
sapi.mpptrade.pl (IIS)
```

### 3. Restart IIS Application Pool
```powershell
# On server via RDP
iisreset /noforce
# or restart specific app pool in IIS Manager
```

## Key Files to Upload After Build
```
publish/
  SubiektApi.dll
  SubiektApi.exe
  SubiektApi.deps.json
  SubiektApi.runtimeconfig.json
  appsettings.json
  web.config
  (all other .dll files)
```

## Configuration (appsettings.json)
```json
{
  "ConnectionStrings": {
    "SubiektGT": "Server=(local)\\INSERTGT;Database=FIRMA;User Id=sa;Password=;TrustServerCertificate=True"
  },
  "ApiKey": "YOUR_API_KEY_HERE"
}
```

## Adding New Endpoints

### 1. Add interface method (SubiektRepository.cs)
```csharp
public interface ISubiektRepository
{
    Task<IEnumerable<NewModel>> GetNewDataAsync();
}
```

### 2. Implement query (SubiektRepository.cs)
```csharp
public async Task<IEnumerable<NewModel>> GetNewDataAsync()
{
    using var conn = GetConnection();
    var sql = @"SELECT ... FROM table WHERE ...";
    return await conn.QueryAsync<NewModel>(sql);
}
```

### 3. Add model (SubiektRepository.cs)
```csharp
public class NewModel
{
    public int Id { get; set; }
    public string Name { get; set; } = "";
}
```

### 4. Add endpoint (Program.cs)
```csharp
app.MapGet("/api/new-data", async (ISubiektRepository repo) =>
{
    var data = await repo.GetNewDataAsync();
    return Results.Ok(new { success = true, data });
}).RequireAuthorization();
```

## Database Schema Reference

**MANDATORY:** Before modifying queries, read:
- `_DOCS/SUBIEKT_GT_DATABASE_SCHEMA.md`
- `_DOCS/SUBIEKT_GT_DATABASE_SCHEMA.json`

## Key Tables for API

| Table | Description | Key Columns |
|-------|-------------|-------------|
| `tw__Towar` | Products | tw_Id, tw_Symbol, tw_Nazwa |
| `tw_Cena` | Prices | tc_IdTowar, tc_CenaNetto0..10 |
| `tw_Stan` | Stock | st_TowId, st_MagId, st_Stan |
| `sl_Magazyn` | Warehouses | mag_Id, mag_Symbol, mag_Nazwa |
| `sl_RodzCeny` | Price types | rc_Id, rc_Nazwa |
| `sl_StawkaVAT` | VAT rates | vat_Id, vat_Stawka |
| `kh__Kontrahent` | Contractors | kh_Id, kh_Symbol, kh_Nazwa |

## Deployment Checklist

- [ ] `dotnet publish -c Release -o ./publish`
- [ ] Upload ALL files from `publish/` to server
- [ ] Verify `appsettings.json` has correct connection string
- [ ] Restart IIS app pool
- [ ] Test: `curl -k -H "X-API-Key: KEY" https://sapi.mpptrade.pl/api/health`
- [ ] Verify new endpoint works

## Troubleshooting

### Connection Failed
- Check SQL Server is running
- Verify connection string in appsettings.json
- Check firewall allows port 1433

### 500 Internal Server Error
- Check IIS logs: `C:\inetpub\logs\LogFiles\`
- Check app logs in Event Viewer
- Verify all DLLs were uploaded

### Endpoint Not Found
- Restart IIS app pool after deploy
- Check Program.cs has endpoint defined
