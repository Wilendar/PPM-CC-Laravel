---
paths: "public/build/**/*"
---

# Deployment: Vite Manifest Handling

## Critical Issue
Laravel's `vite()` helper reads `public/build/manifest.json` but Vite creates `.vite/manifest.json`.

## Mandatory Deployment Step
```powershell
# ALWAYS upload manifest to ROOT of build folder
pscp -i $HostidoKey -P 64321 "public/build/.vite/manifest.json" host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/public/build/manifest.json
```

## Complete Assets Deployment
```powershell
$HostidoKey = "D:\SSH\Hostido\HostidoSSHNoPass.ppk"

# 1. Build locally
npm run build

# 2. Upload ALL assets (not just changed ones!)
pscp -i $HostidoKey -P 64321 -r "public/build/assets/*" host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/public/build/assets/

# 3. Upload manifest to ROOT
pscp -i $HostidoKey -P 64321 "public/build/.vite/manifest.json" host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/public/build/manifest.json

# 4. Clear cache
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"
```

## Symptoms of Wrong Deployment
- Old CSS/JS despite upload -> check manifest hash
- 404 on assets -> manifest not in root
- Styles partially working -> not all files uploaded

## Verification
```powershell
# Check HTTP 200 for asset
curl -I "https://ppm.mpptrade.pl/public/build/assets/app-HASH.css"
```
