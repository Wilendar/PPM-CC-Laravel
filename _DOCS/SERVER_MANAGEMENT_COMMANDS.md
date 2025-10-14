# DOKUMENTACJA KOMEND ZARZĄDZANIA SERWEREM - PPM-CC-Laravel

**Data:** 2025-09-23
**Agent:** Claude Code
**Projekt:** PPM-CC-Laravel (Prestashop Product Manager)
**Serwer:** Hostido.net.pl (host379076)

---

## 🔑 PODSTAWOWE ZMIENNE I KONFIGURACJA

### SSH Key i Parametry Połączenia
```powershell
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$ServerHost = "host379076@host379076.hostido.net.pl"
$ServerPort = "64321"
$LaravelRoot = "domains/ppm.mpptrade.pl/public_html"
```

---

## 📂 STRUKTURA KATALOGÓW NA SERWERZE

### Główne Katalogi:
```
/home/host379076/
├── domains/
│   └── ppm.mpptrade.pl/
│       └── public_html/          # ROOT APLIKACJI LARAVEL
│           ├── app/
│           ├── resources/
│           ├── storage/
│           └── vendor/
├── .claude/                      # Konfiguracja Claude (można usunąć)
├── Maildir/
└── tmp/
```

### Ścieżki Kluczowych Plików:
- **ProductForm.php**: `domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Products/Management/ProductForm.php`
- **Template**: `domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/product-form.blade.php`
- **Logi Laravel**: `domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log`

---

## 🚀 KOMENDY UPLOAD PLIKÓW

### Upload Pojedynczego Pliku
```powershell
# Template produktu
pwsh -c '$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"; pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Http\Livewire\Products\Management\ProductForm.php" host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Products/Management/ProductForm.php'

# Template Blade
pwsh -c '$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"; pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\management\product-form.blade.php" host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/product-form.blade.php'
```

### Upload Wielu Plików Jednocześnie
```powershell
# Upload całego katalogu
pwsh -c '$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"; pscp -r -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\*" host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/'
```

---

## 🧹 KOMENDY CACHE I MAINTENANCE

### Podstawowe Cache Clear
```powershell
pwsh -c '$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"; plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"'
```

### Kompletne Cache Clear
```powershell
pwsh -c '$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"; plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan config:clear && php artisan view:clear && php artisan route:clear"'
```

### Migracje Bazy Danych
```powershell
pwsh -c '$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"; plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force"'
```

---

## 📊 KOMENDY DIAGNOSTYCZNE

### Sprawdzenie Logów
```powershell
# Ostatnie 20 linii logów
pwsh -c '$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"; plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -n 20 storage/logs/laravel.log"'

# Filtrowanie logów po słowie kluczowym
pwsh -c '$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"; plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -n 30 storage/logs/laravel.log | grep context"'
```

### Sprawdzenie Struktury Katalogów
```powershell
# Lista plików w katalogu głównym
pwsh -c '$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"; plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "ls -la"'

# Lista plików Laravel
pwsh -c '$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"; plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "ls -la domains/ppm.mpptrade.pl/public_html/"'
```

### Sprawdzenie Metadanych Plików
```powershell
# Data modyfikacji pliku
pwsh -c '$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"; plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "stat domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Products/Management/ProductForm.php"'

# Rozmiar pliku
pwsh -c '$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"; plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "ls -la domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Products/Management/ProductForm.php"'
```

### Sprawdzenie Zawartości Plików
```powershell
# Sprawdzenie konkretnych linii w pliku
pwsh -c '$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"; plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "head -n 1290 domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Products/Management/ProductForm.php | tail -n 5"'

# Wyszukiwanie tekstu w pliku
pwsh -c '$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"; plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "grep \"context_categories\" domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Products/Management/ProductForm.php"'
```

---

## 🗑️ KOMENDY CLEANUP I MAINTENANCE

### Usuwanie Plików Tymczasowych
```powershell
# Usunięcie wszystkich plików _TEMP_*
pwsh -c '$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"; plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && rm -f _TEMP_*"'

# Usunięcie błędnych katalogów
pwsh -c '$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"; plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "rm -rf domainsppm*"'

# Usunięcie niepotrzebnych plików z głównego katalogu
pwsh -c '$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"; plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "rm -f fio-test .claude.json .claude.json.backup"'
```

### Liczenie Plików
```powershell
# Liczenie plików _TEMP_*
pwsh -c '$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"; plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && ls _TEMP_* | wc -l"'
```

---

## 🔧 WORKFLOW KOMPLETNEGO DEPLOYMENT

### Standardowy Deployment Process
```powershell
# 1. Upload pliku
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
pscp -i $HostidoKey -P 64321 "LOCAL_FILE_PATH" host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/REMOTE_PATH

# 2. Cache Clear
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"

# 3. Sprawdzenie logów
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -n 10 storage/logs/laravel.log"
```

---

## ⚠️ NAJCZĘSTSZE PROBLEMY I ROZWIĄZANIA

### Problem: Pliki nie ładują się po upload
**Rozwiązanie:**
1. Sprawdzić czy plik faktycznie został wgrany: `stat filename`
2. Wyczyścić wszystkie cache: `php artisan cache:clear && php artisan config:clear && php artisan view:clear`
3. Sprawdzić uprawnienia plików: `ls -la filename`

### Problem: Błędy z polskimi znakami w PowerShell
**Rozwiązanie:** Używać `pwsh -c` zamiast bezpośrednich komend bash

### Problem: Cache się nie czyści
**Rozwiązanie:** Uruchomić pełny pipeline cache clear z wszystkimi artisan commands

### Problem: Niepotrzebne pliki na serwerze
**Rozwiązanie:** Regularnie czyścić pliki `_TEMP_*` i błędne katalogi `domainsppm*`

---

## 📝 NOTATKI

### Zmiany wykonane 2025-09-23:
1. ✅ Usunięto 31 plików `_TEMP_*` z katalogu głównego Laravel
2. ✅ Usunięto błędne katalogi `domainsppm*` (8 katalogów)
3. ✅ Usunięto pliki `fio-test` (1GB), `.claude.json`, `.claude.json.backup`
4. ✅ Naprawiono system pending changes w ProductForm.php
5. ✅ Naprawiono template categories w product-form.blade.php

### Status systemu:
- **Server:** ✅ Cleaned up and optimized
- **Laravel:** ✅ Updated with context-safe category system
- **Cache:** ✅ All cleared and refreshed
- **Pending Changes Fix:** ✅ Deployed but requires testing

---

**Autor:** Claude Code
**Ostatnia aktualizacja:** 2025-09-23 11:00