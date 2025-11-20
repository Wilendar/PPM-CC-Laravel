$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING HAS_VARIANTS REFACTORING ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "[1/7] Uploading Product.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
  "app/Models/Product.php" `
  "$RemoteBase/app/Models/Product.php"

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] Product.php uploaded" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Product.php upload failed!" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "[2/7] Uploading HasVariants.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
  "app/Models/Concerns/Product/HasVariants.php" `
  "$RemoteBase/app/Models/Concerns/Product/HasVariants.php"

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] HasVariants.php uploaded" -ForegroundColor Green
} else {
    Write-Host "[ERROR] HasVariants.php upload failed!" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "[3/7] Uploading ProductForm.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
  "app/Http/Livewire/Products/Management/ProductForm.php" `
  "$RemoteBase/app/Http/Livewire/Products/Management/ProductForm.php"

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] ProductForm.php uploaded" -ForegroundColor Green
} else {
    Write-Host "[ERROR] ProductForm.php upload failed!" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "[4/7] Uploading ProductFormVariants.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
  "app/Http/Livewire/Products/Management/Traits/ProductFormVariants.php" `
  "$RemoteBase/app/Http/Livewire/Products/Management/Traits/ProductFormVariants.php"

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] ProductFormVariants.php uploaded" -ForegroundColor Green
} else {
    Write-Host "[ERROR] ProductFormVariants.php upload failed!" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "[5/7] Uploading ProductFormSaver.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
  "app/Http/Livewire/Products/Management/Services/ProductFormSaver.php" `
  "$RemoteBase/app/Http/Livewire/Products/Management/Services/ProductFormSaver.php"

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] ProductFormSaver.php uploaded" -ForegroundColor Green
} else {
    Write-Host "[ERROR] ProductFormSaver.php upload failed!" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "[6/7] Uploading VariantConversionService.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
  "app/Services/VariantConversionService.php" `
  "$RemoteBase/app/Services/VariantConversionService.php"

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] VariantConversionService.php uploaded" -ForegroundColor Green
} else {
    Write-Host "[ERROR] VariantConversionService.php upload failed!" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "[7/7] Uploading migration file..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
  "database/migrations/2025_10_31_100000_drop_has_variants_column_from_products.php" `
  "$RemoteBase/database/migrations/2025_10_31_100000_drop_has_variants_column_from_products.php"

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] Migration file uploaded" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Migration upload failed!" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "=== ALL FILES UPLOADED SUCCESSFULLY ===" -ForegroundColor Green
Write-Host "Ready for migration execution!" -ForegroundColor Cyan
