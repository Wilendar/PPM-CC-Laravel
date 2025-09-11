# 🚀 PRZEWODNIK BEZPIECZNEGO WDRAŻANIA - PPM Laravel

**Dla zespołu MPP TRADE - Instrukcja deployment na serwer Hostido**

---

## ⚠️ KRYTYCZNE ZASADY - PRZECZYTAJ PRZED WDROŻENIEM

### 🔴 **CO NIGDY NIE ROBIC:**
1. **NIGDY nie usuwaj folderu `vendor/`** - zawiera wszystkie zależności PHP
2. **NIGDY nie usuwaj `public/index.php`** - główny entry point Laravel
3. **NIGDY nie modyfikuj plików bezpośrednio na serwerze** - tylko przez upload z lokala
4. **NIGDY nie rób `composer install` bez backup** - może nadpisać pliki
5. **NIGDY nie uploaduj całego projektu naraz** - tylko zmienione pliki

### ✅ **ZAWSZE ROBIC:**
1. **Backup przed każdą zmianą**
2. **Test na pliku testowym pierwszy**
3. **Upload pojedynczych plików/folderów**
4. **Clear cache po każdym uploading**
5. **Sprawdź stronę po każdej zmianie**

---

## 🛠️ WYMAGANE NARZĘDZIA

### 1. **WinSCP (ZALECANE)**
```
Download: https://winscp.net/eng/download.php
Konfiguracja:
- File Protocol: SFTP
- Host: host379076.hostido.net.pl
- Port: 64321
- Username: host379076
- Private key: D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk
```

### 2. **PuTTY Tools (Alternatywnie)**
```powershell
# Upload pojedynczego pliku
pscp -scp -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" "lokalny_plik.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/sciezka/"

# Upload całego folderu
pscp -scp -r -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" "folder_lokalny/" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/"
```

### 3. **SSH do komend**
```powershell
# Połączenie SSH
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "komenda"
```

---

## 📂 STRUKTURA SERWERA

```
domains/ppm.mpptrade.pl/public_html/
├── index.php                 # ⚠️ FRONT CONTROLLER - NIE USUWAĆ
├── public/
│   └── index.php            # ⚠️ LARAVEL ENTRY POINT - NIE USUWAĆ  
├── vendor/                  # ⚠️ DEPENDENCIES - NIE USUWAĆ
├── app/                     # ✅ Kod aplikacji - można modyfikować
├── resources/
│   └── views/              # ✅ Blade templates - można modyfikować
├── config/                 # ⚠️ Ostrożnie - może zepsuć aplikację
├── database/               # ✅ Migracje i seeders
├── routes/                 # ✅ Routing
└── storage/                # ⚠️ Cache i logi - nie modyfikować
```

---

## 🚦 PROCEDURA BEZPIECZNEGO WDROŻENIA

### **FAZA 1: PRZYGOTOWANIE**

1. **Backup lokalnych zmian**
```bash
# Stwórz backup lokalnie
git add .
git commit -m "Backup przed deployment - [data]"
```

2. **Lista plików do uploading**
```
✅ Często modyfikowane (BEZPIECZNE):
- resources/views/**/*.blade.php
- app/Http/**/*.php
- routes/*.php
- database/migrations/*.php

⚠️ Ostrożnie (TESTOWAĆ):
- config/*.php
- .env (tylko jeśli konieczne)
- composer.json (tylko z composer update)

❌ NIGDY:
- vendor/
- public/index.php (chyba że wiesz co robisz)
- bootstrap/cache/
- storage/framework/
```

### **FAZA 2: UPLOAD**

#### **A. WinSCP (Zalecane)**
1. Otwórz WinSCP i połącz się z serwerem
2. Navigate do `domains/ppm.mpptrade.pl/public_html/`
3. Drag & drop TYLKO zmienione pliki
4. **NIGDY nie drag & drop całego projektu!**

#### **B. PowerShell (dla zaawansowanych)**
```powershell
# Upload pojedynczego pliku Blade
pscp -scp -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" "resources\views\welcome.blade.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/"

# Upload controllera
pscp -scp -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" "app\Http\Controllers\AuthController.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Http/Controllers/"
```

### **FAZA 3: WERYFIKACJA I CACHE**

1. **Clear Laravel cache (ZAWSZE po uploadzie)**
```powershell
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan config:clear && php artisan route:clear"
```

2. **Test strony**
```powershell
# Test HTTP status
curl -I "https://ppm.mpptrade.pl/"
curl -I "https://ppm.mpptrade.pl/login"
curl -I "https://ppm.mpptrade.pl/admin"

# Wszystkie powinny zwrócić "200 OK"
```

---

## 🆘 PROCEDURY AWARYJNE

### **Gdy strona się zepsuła:**

1. **Sprawdź logi błędów**
```powershell
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "tail -20 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log"
```

2. **Emergency maintenance mode**
```powershell
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "cd domains/ppm.mpptrade.pl/public_html && echo '<?php echo \"<h1>System w trakcie naprawy</h1><p>Wrócimy wkrótce!</p>\"; ?>' > maintenance.php && cp index.php index.php.backup && echo '<?php require \"maintenance.php\"; ?>' > index.php"
```

3. **Przywrócenie backup**
```powershell
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "cd domains/ppm.mpptrade.pl/public_html && cp index.php.backup index.php && rm maintenance.php"
```

### **Gdy brakuje vendor/ (KRYTYCZNE)**
```powershell
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "cd domains/ppm.mpptrade.pl/public_html && composer install --no-dev --optimize-autoloader"
```

---

## 📝 CHECKLIST PRZED KAŻDYM DEPLOYMENT

```
□ Backup lokalnych zmian (git commit)
□ Sprawdziłem które pliki modyfikuję  
□ Lista zawiera TYLKO zmienione pliki
□ NIE ma w liście vendor/, public/index.php, storage/
□ Test upload na 1 pliku najpierw
□ Upload wszystkich zaplanowanych plików
□ Clear cache (view:clear, config:clear, route:clear)
□ Test wszystkich stron (/, /login, /admin)
□ Sprawdzenie logów błędów
□ Poinformowanie zespołu o udanym deployment
```

---

## 🔧 CZĘSTE PROBLEMY I ROZWIĄZANIA

| Problem | Przyczyna | Rozwiązanie |
|---------|-----------|-------------|
| **500 Error** | Błąd PHP/Laravel | `tail -20 storage/logs/laravel.log` |
| **Brak vendor/** | Usunięte dependencies | `composer install --no-dev` |
| **Blank page** | Brak public/index.php | Przywróć z backup |
| **Cache issues** | Stary cache | `php artisan view:clear` |
| **Permission denied** | Złe uprawnienia | `chmod 755 bootstrap/cache storage` |
| **Vite manifest error** | @vite() bez build | Zastąp CDN linkami w Blade |
| **Duplicate methods** | Kopiuj/wklej błędy | Usuń duplikaty w PHP |
| **Missing config files** | Usunięte pliki config/ | Przywróć standardowe pliki Laravel |

---

## 📋 KROK PO KROKU - PIERWSZY DEPLOYMENT

### **DLA NOWYCH CZŁONKÓW ZESPOŁU - CZYTAJ TO NAJPIERW:**

#### **KROK 1: Przygotuj swoje środowisko**
```powershell
# 1. Sprawdź czy masz dostęp do klucza SSH
Test-Path "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
# Powinno zwrócić: True

# 2. Sprawdź czy masz PuTTY tools (pscp, plink)
where.exe pscp
where.exe plink
# Powinno pokazać ścieżki do programów

# 3. Test połączenia SSH (tylko test, nic nie rób!)
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "whoami"
# Powinno zwrócić: host379076
```

#### **KROK 2: Pierwsze bezpieczne zmiany**
```powershell
# PRZYKŁAD: Zmiana prostego pliku Blade
# 1. Edytuj plik lokalnie: resources/views/welcome.blade.php
# 2. Stwórz backup:
git add resources/views/welcome.blade.php
git commit -m "Update welcome page - backup before deployment"

# 3. Upload na serwer:
pscp -scp -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" "resources\views\welcome.blade.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/"

# 4. Clear cache:
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear"

# 5. Test strony:
curl -I "https://ppm.mpptrade.pl/"
```

#### **KROK 3: Co robić gdy coś pójdzie źle**
```powershell
# 1. NATYCHMIAST sprawdź logi:
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "tail -20 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log"

# 2. Test podstawowych endpointów:
curl -I "https://ppm.mpptrade.pl/"          # Homepage
curl -I "https://ppm.mpptrade.pl/login"     # Login
curl -I "https://ppm.mpptrade.pl/admin"     # Admin panel

# 3. Jeśli 500 error - sprawdź czy nie usunąłeś krytycznych plików:
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "cd domains/ppm.mpptrade.pl/public_html && ls -la public/index.php vendor/autoload.php"
```

---

## 🔍 DIAGNOSTYKA BŁĘDÓW - GDZIE SZUKAĆ PROBLEMÓW

### **1. Hierarchy diagnostyki (rób po kolei!):**

#### **Level 1: HTTP Status Code**
```powershell
# Co oznaczają kody błędów:
curl -I "https://ppm.mpptrade.pl/"

# 200 OK = wszystko działa
# 302 Found = przekierowanie (może być OK)
# 403 Forbidden = problem uprawnień
# 404 Not Found = brak pliku/route
# 500 Internal Server Error = KRYTYCZNY BŁĄD PHP/Laravel
```

#### **Level 2: Laravel Logs (najważniejsze!)**
```powershell
# Sprawdź ostatnie błędy:
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "tail -50 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log"

# Szukaj tych fraz w logach:
# "Fatal error" = krytyczny błąd PHP
# "Class not found" = problem z autoloader/vendor
# "No such file" = brak pliku
# "Permission denied" = problem uprawnień
# "syntax error" = błąd składni PHP
```

#### **Level 3: Sprawdź kluczowe pliki**
```powershell
# Te pliki MUSZĄ istnieć:
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "cd domains/ppm.mpptrade.pl/public_html && ls -la public/index.php"

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "cd domains/ppm.mpptrade.pl/public_html && ls -la vendor/autoload.php"

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "cd domains/ppm.mpptrade.pl/public_html && ls -la .env"
```

#### **Level 4: Test Laravel Bootstrap**
```powershell
# Sprawdź czy Laravel może się uruchomić:
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "cd domains/ppm.mpptrade.pl/public_html && php -r \"require 'vendor/autoload.php'; echo 'Autoloader OK'; \""
```

### **2. Typowe błędy i ich rozwiązania:**

#### **"Class not found" / "autoload.php not found"**
```powershell
# Problem: Uszkodzony/brakujący vendor/
# Rozwiązanie:
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "cd domains/ppm.mpptrade.pl/public_html && composer install --no-dev --optimize-autoloader"
```

#### **"Please provide a valid cache path"**
```powershell
# Problem: Uprawnienia storage/
# Rozwiązanie:
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "cd domains/ppm.mpptrade.pl/public_html && chmod -R 755 storage bootstrap/cache"
```

#### **"syntax error, unexpected token"**
```powershell
# Problem: Błąd składni w uploadowanym pliku PHP
# Rozwiązanie: Sprawdź plik lokalnie:
php -l app/Http/Controllers/YourController.php
# Popraw błędy i upload ponownie
```

---

## 🚨 HISTORIE AWARII - UCZ SIĘ NA BŁĘDACH INNYCH

### **AWARIA #1: 2025-09-11 (Pierwsza) - "Rozwalona strona"**
**Problem**: Kolega usunął krytyczne pliki vendor/, duplikaty metod

**Co się zepsuło:**
- HTTP 500 na całej stronie
- Brak vendor/autoload.php
- Duplikaty metod w AdminDashboard.php, User.php, Product.php
- @vite() błędy w layouts

**Jak naprawiono:**
1. `composer install --no-dev --optimize-autoloader`
2. Usunięcie duplikatów metod 
3. Zamiana @vite() na CDN w layouts
4. Fix route names w admin.blade.php

**Czas naprawy:** 3 godziny

### **AWARIA #2: 2025-09-11 (Druga) - "ZNOWU rozwalił stronę"**
**Problem**: Usunięty public/index.php + uszkodzony vendor/

**Symptomy:**
- HTTP 500 Internal Server Error
- "Please provide a valid cache path" w logach
- Kompletna niedostępność strony

**Diagnoza:**
```powershell
# Brak głównego entry pointa:
ls -la public/index.php  # No such file or directory

# Uszkodzony vendor:
ls -la vendor/autoload.php  # No such file or directory
```

**Naprawa:**
```powershell
# 1. Odbudowa vendor/:
rm -rf vendor/
composer install --no-dev --optimize-autoloader

# 2. Odtworzenie public/index.php:
# (standard Laravel entry point)

# 3. Fix uprawnień:
chmod -R 755 storage bootstrap/cache

# 4. Clear cache:
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

**Czas naprawy:** 45 minut (dzięki doświadczeniu z pierwszej awarii!)

### **⚠️ NAJWAŻNIEJSZE WNIOSKI:**

#### **NIGDY NIE RÓB TEGO:**
1. **Nie usuwaj public/index.php** - to entry point całej aplikacji
2. **Nie usuwaj vendor/** - to wszystkie zależności PHP
3. **Nie kopiuj/wklej kodu** bez sprawdzenia duplikatów
4. **Nie używaj @vite()** na shared hostingu - użyj CDN
5. **Nie rób zmian bez git commit** - zawsze rób backup

#### **ZAWSZE RÓB TO:**
1. **git commit** przed każdą zmianą
2. **Sprawdź logi** po każdym upload: `tail -20 storage/logs/laravel.log`
3. **Test HTTP status** wszystkich stron: `curl -I`
4. **Clear cache** po każdej zmianie
5. **Upload pojedynczych plików**, nie całych folderów

---

## 📚 SZCZEGÓŁOWE PRZYKŁADY UPLOADÓW

### **Scenario 1: Zmiana Blade template**
```powershell
# 1. Edytuj plik lokalnie
# resources/views/admin/dashboard.blade.php

# 2. Backup:
git add resources/views/admin/dashboard.blade.php
git commit -m "Update admin dashboard layout"

# 3. Upload:
pscp -scp -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" "resources\views\admin\dashboard.blade.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/admin/"

# 4. Clear view cache:
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear"

# 5. Test:
curl -I "https://ppm.mpptrade.pl/admin"
```

### **Scenario 2: Zmiana Controller**
```powershell
# 1. Sprawdź składnię lokalnie:
php -l app/Http/Controllers/AdminController.php

# 2. Backup:
git add app/Http/Controllers/AdminController.php
git commit -m "Update AdminController methods"

# 3. Upload:
pscp -scp -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" "app\Http\Controllers\AdminController.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Http/Controllers/"

# 4. Clear wszystkie cache:
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan config:clear && php artisan route:clear"

# 5. Test i sprawdź logi:
curl -I "https://ppm.mpptrade.pl/admin"
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "tail -10 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log"
```

### **Scenario 3: Zmiana Route**
```powershell
# 1. Edytuj routes/web.php lokalnie

# 2. Sprawdź składnię:
php artisan route:list --local  # (sprawdź lokalnie)

# 3. Backup:
git add routes/web.php
git commit -m "Add new routes for admin panel"

# 4. Upload:
pscp -scp -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" "routes\web.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/routes/"

# 5. Clear route cache:
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan route:clear"

# 6. Test nowych route:
curl -I "https://ppm.mpptrade.pl/new-route"
```

### **Scenario 4: Emergency rollback (gdy coś pójdzie źle)**
```powershell
# 1. Sprawdź co jest złe:
curl -I "https://ppm.mpptrade.pl/"  # Jeśli 500 error:

# 2. Sprawdź logi błędów:
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "tail -20 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log"

# 3. Rollback ostatniego pliku (przykład Controller):
git log --oneline -5  # znajdź ostatni commit
git checkout HEAD~1 -- app/Http/Controllers/AdminController.php
pscp -scp -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" "app\Http\Controllers\AdminController.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Http/Controllers/"

# 4. Clear cache:
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan config:clear && php artisan route:clear"

# 5. Test:
curl -I "https://ppm.mpptrade.pl/"
```

---

## 👥 KONTAKT W RAZIE PROBLEMÓW

1. **Claude Code AI** - dla problemów technicznych
2. **Kamil Wiliński** - dla problemów business/projektowych  
3. **Team Chat** - dla komunikacji zmian

---

**⚡ PAMIĘTAJ: Lepiej być ostrożnym niż naprawiać zepsutą stronę!**

---

## 🛡️ DODATKOWE ZASADY BEZPIECZEŃSTWA

### **Przed każdym deployment:**
```powershell
# 1. ZAWSZE sprawdź składnię PHP:
php -l app/Http/Controllers/YourFile.php

# 2. ZAWSZE rób backup:
git add . && git commit -m "Backup before deployment $(Get-Date -Format 'yyyy-MM-dd HH:mm')"

# 3. Upload TYLKO zmienione pliki:
git status  # Zobacz co zmieniłeś
```

### **Po każdym deployment:**
```powershell
# 1. Clear cache:
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan config:clear && php artisan view:clear && php artisan route:clear"

# 2. Test wszystkich stron:
curl -I "https://ppm.mpptrade.pl/"
curl -I "https://ppm.mpptrade.pl/login" 
curl -I "https://ppm.mpptrade.pl/admin"

# 3. Sprawdź logi:
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "tail -10 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log"
```

### **Mnemonic - zapamiętaj: "BACKUP, UPLOAD, CLEAR, TEST"**
1. **B**ackup = git commit  
2. **U**pload = pscp single files
3. **C**lear = artisan cache clear
4. **T**est = curl -I + check logs

---

## 📱 QUICK REFERENCE - NAJWAŻNIEJSZE KOMENDY

### **Diagnostic commands (kopiuj/wklej gdy coś nie działa):**
```powershell
# Quick health check (wszystkie w jednej linii):
curl -I "https://ppm.mpptrade.pl/" && curl -I "https://ppm.mpptrade.pl/login" && curl -I "https://ppm.mpptrade.pl/admin"

# Quick log check:
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "tail -20 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log"

# Quick file existence check:
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "cd domains/ppm.mpptrade.pl/public_html && ls -la public/index.php vendor/autoload.php .env"

# Emergency vendor rebuild:
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "cd domains/ppm.mpptrade.pl/public_html && composer install --no-dev --optimize-autoloader"

# Emergency cache clear + permissions:
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -batch "cd domains/ppm.mpptrade.pl/public_html && chmod -R 755 storage bootstrap/cache && php artisan config:clear && php artisan cache:clear && php artisan view:clear && php artisan route:clear"
```

### **Upload commands templates:**
```powershell
# Blade template:
pscp -scp -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" "resources\views\FILENAME.blade.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/"

# Controller:
pscp -scp -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" "app\Http\Controllers\FILENAME.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Http/Controllers/"

# Route file:
pscp -scp -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" "routes\web.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/routes/"

# Middleware:
pscp -scp -P 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" "app\Http\Middleware\FILENAME.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Http/Middleware/"
```

---

## 🎯 PODSUMOWANIE DLA POŚPIESZNYCH

**Jeśli czytasz tylko to (choć powinieneś przeczytać wszystko!):**

### ⚠️ **ZASADA #1: NIGDY nie usuwaj tych plików/folderów:**
- `vendor/` (wszystkie zależności PHP)
- `public/index.php` (entry point Laravel) 
- `bootstrap/cache/` (cache Bootstrap)
- `storage/` (logi i cache)

### ✅ **ZASADA #2: Workflow "BACT":**
1. **B**ackup: `git commit -m "message"`
2. **A**dd: Upload pojedynczy plik przez `pscp` 
3. **C**lear: `php artisan view:clear` (i inne cache)
4. **T**est: `curl -I https://ppm.mpptrade.pl/`

### 🚨 **ZASADA #3: Gdy coś się zepsuje - STOP:**
1. **Nie próbuj naprawić na ślepo**
2. Sprawdź logi: `tail -20 storage/logs/laravel.log`
3. Sprawdź HTTP status: `curl -I https://ppm.mpptrade.pl/`
4. Wezwij pomoc jeśli nie wiesz co robisz

### 📞 **ZASADA #4: Poproś o pomoc WCZEŚNIE:**
Lepiej zapytać przed zniszczeniem niż naprawiać po szkodzie!

---

*Dokument zaktualizowany: 2025-09-11 po analizie dwóch awarii*  
*Autor: Claude Code AI*  
*Wersja: 2.0 - Enhanced Edition*  
*Status: ✅ Przetestowane na rzeczywistych awariach*