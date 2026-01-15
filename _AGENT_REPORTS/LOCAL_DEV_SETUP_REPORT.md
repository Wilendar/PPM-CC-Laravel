# RAPORT: Konfiguracja Srodowiska Lokalnego PPM-CC-Laravel

**Data**: 2025-12-04
**Zadanie**: Utworzenie lokalnego srodowiska developerskiego dla PPM-CC-Laravel

---

## STATUS: PRAWIE UKONCZONE (95%)

### ✅ ZREALIZOWANE KOMPONENTY

| Komponent | Status | Wersja/Sciezka |
|-----------|--------|----------------|
| PHP | ✅ OK | 8.3.25 (ZTS) |
| PHP ext-gd | ✅ OK | Wlaczone |
| PHP ext-zip | ✅ OK | Wlaczone |
| PHP ext-mbstring | ✅ OK | Wlaczone |
| PHP ext-curl | ✅ OK | Wlaczone |
| PHP ext-openssl | ✅ OK | Wlaczone |
| PHP ext-pdo_mysql | ✅ OK | Wlaczone |
| Node.js | ✅ OK | v22.17.1 |
| npm | ✅ OK | (z Node.js) |
| MariaDB | ✅ Zainstalowana | 12.0.2 |
| Git | ✅ OK | 2.51.0 |
| Composer | ✅ OK | via composer.phar |

### ✅ PROJEKT LOKALNY

| Element | Status | Sciezka |
|---------|--------|---------|
| Lokalizacja | ✅ OK | `D:\Projekty\PPM-CC-Laravel-local` |
| vendor/ | ✅ OK | 146 packages |
| node_modules/ | ✅ OK | 253 packages |
| .env | ✅ OK | Skonfigurowany |
| APP_KEY | ✅ OK | Wygenerowany |
| APP_ENV | ✅ OK | local |
| APP_URL | ✅ OK | http://localhost:8000 |
| DB_DATABASE | ✅ OK | ppm_cc_laravel_local |

### ⚠️ WYMAGANE DZIALANIE UZYTKOWNIKA

**MariaDB serwer nie jest uruchomiony!**

---

## NASTEPNE KROKI (dla uzytkownika)

### KROK 1: Uruchom serwer MariaDB

```
1. Otworz: services.msc (Win+R -> services.msc)
2. Znajdz: "MariaDB" (lub "MySQL")
3. Kliknij prawym -> Start
4. Ustaw: Startup type = Automatic
```

### KROK 2: Utworz baze danych

```powershell
cd "C:\Program Files\MariaDB 12.0\bin"
.\mysql.exe -u root -e "CREATE DATABASE ppm_cc_laravel_local CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### KROK 3: Uruchom migracje

```powershell
cd "D:\Projekty\PPM-CC-Laravel-local"
php artisan migrate --seed
```

### KROK 4: Uruchom serwer developerski

**Terminal 1 - Laravel:**
```powershell
cd "D:\Projekty\PPM-CC-Laravel-local"
php artisan serve
```

**Terminal 2 - Vite (hot reload):**
```powershell
cd "D:\Projekty\PPM-CC-Laravel-local"
npm run dev
```

### KROK 5: Otworz aplikacje

```
http://localhost:8000
Login: admin@mpptrade.pl / Admin123!MPP
```

---

## AUTOINSTALLER

Utworzony autoinstaller dla innych komputerow:
- **Lokalizacja**: `_TOOLS/autoinstaller_ppm_dev.ps1`

**Uzycie:**
```powershell
pwsh -ExecutionPolicy Bypass -File "_TOOLS/autoinstaller_ppm_dev.ps1"
```

**Opcje:**
- `-SkipComposer` - pominij instalacje Composer
- `-SkipMariaDB` - pominij instalacje MariaDB
- `-DryRun` - tylko pokaz co zostanie zrobione

---

## POROWNIANIE: LOKALNE vs PRODUKCJA

| Element | Lokalne | Produkcja (Hostido) |
|---------|---------|---------------------|
| PHP | 8.3.25 | 8.3.23 |
| MariaDB | 12.0.2 | 10.11.13 |
| Node.js | v22.17.1 | BRAK |
| Vite | TAK (npm run dev) | NIE (tylko build) |
| URL | localhost:8000 | ppm.mpptrade.pl |
| Deployment | - | SSH/pscp |

**UWAGA**: MariaDB 12.0 jest nowsza niz produkcyjna 10.11.13, ale powinna byc w pelni kompatybilna.

---

## PLIKI KONFIGURACYJNE

| Plik | Opis |
|------|------|
| `.env` | Lokalna konfiguracja (APP_KEY, DB, etc.) |
| `_TEMP/copy_project.ps1` | Skrypt kopiowania projektu |
| `_TEMP/setup_local_env.ps1` | Skrypt konfiguracji srodowiska |
| `_TEMP/final_fix.ps1` | Naprawa PHP extensions + config files |
| `_TEMP/verify_local_setup.ps1` | Weryfikacja instalacji |
| `_TOOLS/autoinstaller_ppm_dev.ps1` | Autoinstaller dla innych PC |

---

## WORKFLOW DEPLOYMENT: DEV -> PROD

```
[DEV: D:\Projekty\PPM-CC-Laravel-local]
         |
         | 1. Edytuj kod
         | 2. npm run build (buduje assets)
         | 3. Test lokalnie
         v
[OneDrive: D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel]
         |
         | 4. Skopiuj zmienione pliki
         v
[PROD: ppm.mpptrade.pl via SSH/pscp]
         |
         | 5. pscp upload
         | 6. php artisan cache:clear
         v
      GOTOWE!
```

---

**Raport wygenerowany**: 2025-12-04
**Status**: Oczekiwanie na uruchomienie MariaDB przez uzytkownika
