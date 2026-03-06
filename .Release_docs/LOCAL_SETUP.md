# PPM - Konfiguracja Lokalnego Srodowiska Deweloperskiego

## 1. Wymagania systemowe

| Komponent | Minimalna wersja | Zalecana wersja | Uwagi |
|-----------|-----------------|-----------------|-------|
| PHP | 8.3 | 8.3.x | Produkcja uzywa 8.3.23 |
| Composer | 2.x | 2.8.x | |
| Node.js | 20.x | 24.x | Tylko lokalnie - prod bez Node! |
| npm | 10.x | najnowszy | |
| XAMPP | 8.x | najnowszy | MariaDB 10.x |
| Git | 2.x | najnowszy | |
| plink/pscp | PuTTY | najnowszy | Do deployment/sync |

**UWAGA:** Lokalne PHP moze byc 8.5.x - produkcja uzywa 8.3.23. Testuj na PHP 8.3 gdy to mozliwe. Roznice moga dotyczyc: deprecated functions, nowych typow, zmian w standardowej bibliotece.

## 2. Instalacja krok po kroku

### 2.1 Klonowanie repozytorium

```bash
git clone https://github.com/wilendar/PPM-CC-Laravel.git
cd PPM-CC-Laravel
git checkout develop
```

### 2.2 PHP i Composer

```bash
# Sprawdz wersje
php -v           # >= 8.3
composer -V       # >= 2.x

# Zainstaluj zaleznosci
composer install
```

Wymagane rozszerzenia PHP: `pdo_mysql`, `gd`, `zip`, `bcmath`, `mbstring`, `xml`, `curl`, `fileinfo`.

### 2.3 Node.js i npm

```bash
node -v    # >= 20.x
npm -v     # >= 10.x

npm install
npm run build    # Buduje assets (Vite)
```

### 2.4 XAMPP (MariaDB)

1. Pobierz XAMPP z https://www.apachefriends.org/
2. Zainstaluj z komponentami: Apache, MariaDB, phpMyAdmin
3. Uruchom XAMPP Control Panel -> Start MySQL
4. Konfiguracja `my.ini` (opcjonalne ale zalecane):

```ini
[mysqld]
max_allowed_packet=256M
innodb_buffer_pool_size=256M
```

5. Utworz baze danych:

```bash
# Przez CLI (XAMPP shell)
mysql -u root -e "CREATE DATABASE ppm_local CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Lub przez phpMyAdmin: http://localhost/phpmyadmin
```

### 2.5 Konfiguracja .env

```bash
# Skopiuj szablon
cp .env.example .env

# Edytuj kluczowe wartosci:
```

| Parametr | Wartosc lokalna |
|----------|----------------|
| APP_ENV | local |
| APP_DEBUG | true |
| APP_URL | http://localhost:8000 |
| DB_HOST | 127.0.0.1 |
| DB_DATABASE | ppm_local |
| DB_USERNAME | root |
| DB_PASSWORD | *(puste)* |
| SESSION_DOMAIN | *(puste)* |
| MAIL_MAILER | log |
| LOG_LEVEL | debug |
| TELESCOPE_ENABLED | true |
| DEV_AUTH_BYPASS | false |

### 2.6 Inicjalizacja Laravel

```bash
php artisan key:generate
php artisan storage:link
```

### 2.7 Baza danych

**Opcja A: Synchronizacja z produkcji** (zalecane):
```powershell
.\.Release_docs\scripts\sync-db.ps1 -ExcludeLarge
```
Szczegoly: patrz [DATABASE_SYNC.md](DATABASE_SYNC.md)

**Opcja B: Czyste migracje** (pusta baza):
```bash
php artisan migrate --seed
```

### 2.8 Uruchomienie

```bash
# Serwer PHP (terminal 1)
php artisan serve
# -> http://localhost:8000

# Vite dev server z HMR (terminal 2, opcjonalnie)
npm run dev
```

## 3. Konto testowe

| Email | Haslo | Rola |
|-------|-------|------|
| admin@mpptrade.pl | Admin123!MPP | Super Admin |
| admin@ppm.mpptrade.pl | Admin123!PPM | Admin |
| manager@ppm.mpptrade.pl | Manager123!PPM | Manager |

Pelna lista kont: `_DOCS/dane_hostingu.md` (sekcja "Uzytkownicy testowi")

## 4. Troubleshooting

### "SQLSTATE[HY000] [2002] Connection refused"
- Sprawdz czy MySQL jest uruchomiony w XAMPP
- Sprawdz DB_HOST=127.0.0.1 (nie localhost - moze uzywac socketa)

### "Vite manifest not found"
- Uruchom `npm run build` (produkcja) lub `npm run dev` (development z HMR)

### "Class not found"
```bash
composer dump-autoload
php artisan optimize:clear
```

### PHP version mismatch warnings
- Lokalne PHP 8.5 moze generowac inne deprecation warnings niz prod 8.3
- Przed deploymentem testuj krytyczne funkcje na produkcji

### Storage permission errors
```bash
php artisan storage:link
# Na Windows: uruchom terminal jako Administrator
```
