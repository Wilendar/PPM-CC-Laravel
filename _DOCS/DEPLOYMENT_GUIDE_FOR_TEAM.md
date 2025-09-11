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

## 🚨 PRZYPADEK AWARYJNY - NAUKA Z 2025-09-11

**Problem**: Kolega przypadkowo usunął krytyczne pliki serwera (vendor/, public/index.php)

### Co się zepsuło:
- Cały folder `vendor/` (wszystkie dependencies PHP)
- Plik `public/index.php` (Laravel entry point)  
- Duplikaty metod w AdminDashboard.php
- Layout auth.blade.php z błędem Vite manifest

### Jak naprawiliśmy:
1. **Przywrócenie vendor/**: `composer install --no-dev --optimize-autoloader`
2. **Odtworzenie public/index.php** z standardową strukturą Laravel
3. **Usunięcie duplikatów** metodami sed
4. **Zamiana @vite() na CDN** w layouts/auth.blade.php

### ⚠️ WNIOSKI:
- **NIGDY nie usuwaj vendor/** bez backup
- **Sprawdzaj duplikaty** przed zapisem plików  
- **Używaj CDN zamiast Vite** na shared hostingu
- **Testuj KAŻDĄ stronę** po zmianie

---

## 👥 KONTAKT W RAZIE PROBLEMÓW

1. **Claude Code AI** - dla problemów technicznych
2. **Kamil Wiliński** - dla problemów business/projektowych  
3. **Team Chat** - dla komunikacji zmian

---

**⚡ PAMIĘTAJ: Lepiej być ostrożnym niż naprawiać zepsutą stronę!**

---

*Dokument stworzony: 2025-09-11*  
*Autor: Claude Code AI*  
*Wersja: 1.0*