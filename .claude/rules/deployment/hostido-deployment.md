# Deployment: Hostido Production Server

## Environment
| Parameter | Value |
|-----------|-------|
| Domain | ppm.mpptrade.pl |
| SSH Host | host379076@host379076.hostido.net.pl |
| SSH Port | 64321 |
| SSH Key | `D:\SSH\Hostido\HostidoSSHNoPass.ppk` |
| Laravel Root | `domains/ppm.mpptrade.pl/public_html/` |
| PHP | 8.3.23 |
| **Node.js** | NOT AVAILABLE - Build locally only! |

## Critical: Vite Build Process
```
[Local] npm run build -> public/build/ -> pscp upload -> [Production]
```

**MANIFEST ISSUE:** Laravel requires `public/build/manifest.json` (ROOT), but Vite creates `.vite/manifest.json`

## Deployment Commands

### PowerShell Setup
```powershell
$HostidoKey = "D:\SSH\Hostido\HostidoSSHNoPass.ppk"
```

### Upload Single File
```powershell
pscp -i $HostidoKey -P 64321 "LOCAL_PATH" host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/REMOTE_PATH
```

### Execute Remote Command
```powershell
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && COMMAND"
```

### Clear Cache
```powershell
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"
```

## Deployment Checklist
1. `npm run build` (check "built in X.XXs")
2. Upload ALL assets: `pscp -r public/build/assets/* -> remote/assets/`
3. Upload manifest: `.vite/manifest.json -> build/manifest.json`
4. Clear cache: `view:clear && cache:clear && config:clear`
5. HTTP 200 verify: `curl -I "https://ppm.mpptrade.pl/public/build/assets/app-X.css"`
6. Chrome DevTools verification (MANDATORY!)

## Common Issues

### CSS Changes Not Visible
1. Rebuild: `npm run build`
2. Check manifest.json hash
3. Upload ALL assets (not just changed ones)
4. Clear view cache
5. Hard refresh browser (Ctrl+Shift+R)

### Class Not Found After Deploy
```powershell
plink ... "cd domains/ppm.mpptrade.pl/public_html && composer dump-autoload && php artisan optimize:clear"
```
