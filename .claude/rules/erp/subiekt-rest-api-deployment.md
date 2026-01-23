# ERP: Subiekt GT REST API Deployment (EXEA Server)

## Deployment Model
- **EXEA Server (sapi.mpptrade.pl)**: User deploys manually via RDP
- **Hostido (ppm.mpptrade.pl)**: Claude deploys via SSH/pscp

## REST API Build (Local)

### Prerequisites
```powershell
# Install .NET 8 SDK if not present
Invoke-WebRequest -Uri 'https://dot.net/v1/dotnet-install.ps1' -OutFile 'dotnet-install.ps1'
./dotnet-install.ps1 -Channel 8.0
```

### Build Command
```powershell
cd "D:\Skrypty\PPM-CC-Laravel\_TOOLS\SubiektGT_REST_API_DotNet"
& 'C:\Users\kamil\AppData\Local\Microsoft\dotnet\dotnet.exe' publish -c Release -o ./publish
```

## EXEA Deployment Instructions (User Manual)

### Source Location
```
D:\Skrypty\PPM-CC-Laravel\_TOOLS\SubiektGT_REST_API_DotNet\publish\
```

### Target Location (EXEA Windows Server)
```
sapi.mpptrade.pl → IIS Application Root
```

### Files to Upload
Upload ALL contents of `publish/` folder:
- `SubiektApi.dll` - Main application
- `SubiektApi.exe` - Executable
- `SubiektApi.deps.json` - Dependencies
- `SubiektApi.runtimeconfig.json` - Runtime config
- `appsettings.json` - Configuration (verify connection string!)
- `web.config` - IIS configuration
- All `*.dll` files (dependencies)
- `runtimes/` folder (native libraries)

### Post-Deploy Steps
1. Stop IIS Application Pool for sapi.mpptrade.pl
2. Upload all files from `publish/`
3. Verify `appsettings.json` has correct SQL connection string
4. Start IIS Application Pool
5. Test: `curl -k -H "X-API-Key: YOUR_KEY" https://sapi.mpptrade.pl/api/health`

### Verify New Endpoint
```bash
# Test PUT endpoint (new!)
curl -k -X PUT \
  -H "X-API-Key: YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{"Name": "Test Name"}' \
  https://sapi.mpptrade.pl/api/products/123
```

## Troubleshooting

### 500 Error After Deploy
- Check IIS logs: `C:\inetpub\logs\LogFiles\`
- Verify all DLLs uploaded (especially `runtimes/` folder)
- Check Event Viewer for .NET errors

### Connection String Issues
Edit `appsettings.json`:
```json
{
  "ConnectionStrings": {
    "SubiektGT": "Server=(local)\\INSERTGT;Database=FIRMA;..."
  }
}
```
