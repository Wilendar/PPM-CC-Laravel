$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING CATEGORY 36 IN DATABASE ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "Step 1: Does category 36 exist?" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='`$cat = App\Models\Category::find(36); if (`$cat) { echo ""EXISTS: "" . `$cat->name; echo PHP_EOL; echo ""Parent ID: "" . (`$cat->parent_id ?? ""NULL""); echo PHP_EOL; echo ""Sort order: "" . `$cat->sort_order; echo PHP_EOL; echo ""Active: "" . (`$cat->is_active ? ""YES"" : ""NO""); } else { echo ""CATEGORY 36 NOT FOUND IN DB""; }'"

Write-Host "`n`nStep 2: Check parent hierarchy of category 36" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='`$cat = App\Models\Category::find(36); if (`$cat) { echo ""Category 36: "" . `$cat->name; echo PHP_EOL; `$parent = `$cat->parent; if (`$parent) { echo ""Parent: "" . `$parent->id . "" - "" . `$parent->name; echo PHP_EOL; `$grandparent = `$parent->parent; if (`$grandparent) { echo ""Grandparent: "" . `$grandparent->id . "" - "" . `$grandparent->name; } } else { echo ""NO PARENT (root category)""; } }'"

Write-Host "`n`nStep 3: Check if category 36 is in availableCategories query" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='`$root = App\Models\Category::with(""children"")->whereNull(""parent_id"")->where(""is_active"", true)->orderBy(""sort_order"")->get(); echo ""Root categories count: "" . `$root->count(); echo PHP_EOL; `$found = false; foreach (`$root as `$r) { if (`$r->id == 36) { `$found = true; echo ""FOUND: Category 36 is ROOT""; break; } `$children = `$r->children; if (`$children) { foreach (`$children as `$c) { if (`$c->id == 36) { `$found = true; echo ""FOUND: Category 36 is child of "" . `$r->name; break 2; } } } } if (!`$found) { echo ""NOT FOUND in root or immediate children""; }'"

Write-Host "`n`n=== DONE ===" -ForegroundColor Green
