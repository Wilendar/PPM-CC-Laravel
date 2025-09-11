# NOWY PLAN ETAP_01 - Hybrydowy Workflow

## 📋 SZCZEGÓŁOWY PLAN ZADAŃ

- ❌ **1. WERYFIKACJA ŚRODOWISKA HOSTIDO**
  - ❌ **1.1 Analiza serwera Hostido**
    - ❌ **1.1.1 Przegląd danych hostingowych**
      - ❌ 1.1.1.1 Weryfikacja danych SSH (host379076@host379076.hostido.net.pl:64321)
      - ❌ 1.1.1.2 Sprawdzenie dostępu do bazy danych (host379076_ppm)
      - ❌ 1.1.1.3 Testowanie połączenia SSH z hasłem
      - ❌ 1.1.1.4 Dokumentacja limitów hostingu MD4
    - ❌ **1.1.2 Testowanie dostępnych technologii**
      - ❌ 1.1.2.1 Sprawdzenie wersji PHP na serwerze (php -v)
      - ❌ 1.1.2.2 Weryfikacja Composer (composer --version)
      - ❌ 1.1.2.3 Sprawdzenie Node.js (/opt/alt/alt-nodejs22/root/usr/bin/node)
      - ❌ 1.1.2.4 Testowanie uprawnień do zapisywania plików
    - ❌ **1.1.3 Przygotowanie środowiska serwerowego**
      - ❌ 1.1.3.1 Utworzenie struktury katalogów (/domains/ppm.mpptrade.pl/)
      - ❌ 1.1.3.2 Konfiguracja uprawnień folderów (755/644)
      - ❌ 1.1.3.3 Testowanie bazy danych MySQL
      - ❌ 1.1.3.4 Przygotowanie .env dla produkcji

---

- ❌ **2. DEPLOYMENT PIPELINE I AUTOMATYZACJA**
  - ✅ **2.1 Narzędzia automatyzacji** (UKOŃCZONE)
    - ✅ 2.1.1 hostido_automation.ps1 - SSH automation
    - ✅ 2.1.2 hostido_deploy.ps1 - File upload automation  
    - ✅ 2.1.3 Aktualizacja CLAUDE.md z komendami
  - ❌ **2.2 Testowanie automatyzacji**
    - ❌ 2.2.1 Instalacja PuTTY (.\_TOOLS\hostido_automation.ps1 -InstallPuTTY)
    - ❌ 2.2.2 Instalacja WinSCP (.\_TOOLS\hostido_deploy.ps1 -InstallWinSCP)
    - ❌ 2.2.3 Test połączenia SSH (.\_TOOLS\hostido_automation.ps1 -TestConnection)
    - ❌ 2.2.4 Test upload plików (.\_TOOLS\hostido_deploy.ps1 -SetupDirectories)

---

- ❌ **3. UTWORZENIE PROJEKTU LARAVEL 12.X (LOKALNIE)**
  - ❌ **3.1 Struktura projektu**
    - ❌ **3.1.1 Inicjalizacja projektu**
      - ❌ 3.1.1.1 Utworzenie podstawowej struktury folderów Laravel
      - ❌ 3.1.1.2 Przygotowanie pliku composer.json dla Laravel 12.x
      - ❌ 3.1.1.3 Utworzenie pliku .env z konfiguracją Hostido
      - ❌ 3.1.1.4 Inicjalizacja repozytorium Git
    - ❌ **3.1.2 Konfiguracja podstawowa**
      - ❌ 3.1.2.1 Konfiguracja APP_NAME="PPM - Prestashop Product Manager"
      - ❌ 3.1.2.2 Ustawienie APP_URL=https://ppm.mpptrade.pl
      - ❌ 3.1.2.3 Konfiguracja bazy danych Hostido
      - ❌ 3.1.2.4 Przygotowanie .env.example

---

- ❌ **4. DEPLOYMENT I INSTALACJA NA SERWERZE**
  - ❌ **4.1 Pierwszy deployment**
    - ❌ **4.1.1 Upload plików Laravel**
      - ❌ 4.1.1.1 Upload podstawowych plików (.\_TOOLS\hostido_deploy.ps1)
      - ❌ 4.1.1.2 Kopiowanie .env na serwer
      - ❌ 4.1.1.3 Ustawienie uprawnień storage i bootstrap/cache
      - ❌ 4.1.1.4 Konfiguracja .htaccess dla Laravel
    - ❌ **4.1.2 Instalacja zależności na serwerze**
      - ❌ 4.1.2.1 composer install na serwerze
      - ❌ 4.1.2.2 php artisan key:generate
      - ❌ 4.1.2.3 Testowanie podstawowej strony Laravel
      - ❌ 4.1.2.4 Weryfikacja na https://ppm.mpptrade.pl

---

- ❌ **5. PAKIETY I KOMPONENTY LARAVEL**
  - ❌ **5.1 Pakiety obowiązkowe**
    - ❌ **5.1.1 Laravel Livewire 3.x**
      - ❌ 5.1.1.1 composer require livewire/livewire "^3.0" (na serwerze)
      - ❌ 5.1.1.2 php artisan livewire:publish --config
      - ❌ 5.1.1.3 Konfiguracja Livewire w config/livewire.php
      - ❌ 5.1.1.4 Testowanie pierwszego komponentu Livewire
    - ❌ **5.1.2 Laravel Excel (PhpSpreadsheet)**
      - ❌ 5.1.2.1 composer require maatwebsite/excel (na serwerze)
      - ❌ 5.1.2.2 php artisan vendor:publish Excel provider
      - ❌ 5.1.2.3 Konfiguracja w config/excel.php
      - ❌ 5.1.2.4 Testowanie importu/eksportu XLSX
    - ❌ **5.1.3 Spatie Laravel Permission**
      - ❌ 5.1.3.1 composer require spatie/laravel-permission (na serwerze)
      - ❌ 5.1.3.2 php artisan vendor:publish Permission provider
      - ❌ 5.1.3.3 Migracja tabel uprawnień
      - ❌ 5.1.3.4 Konfiguracja modeli User dla 7 poziomów

---

- ❌ **6. VITE I FRONTEND ASSETS**
  - ❌ **6.1 Konfiguracja Vite (lokalnie)**
    - ❌ **6.1.1 Setup package.json**
      - ❌ 6.1.1.1 npm install vite laravel-vite-plugin (lokalnie)
      - ❌ 6.1.1.2 npm install alpinejs axios
      - ❌ 6.1.1.3 npm install tailwindcss @tailwindcss/forms
      - ❌ 6.1.1.4 Konfiguracja vite.config.js
    - ❌ **6.1.2 TailwindCSS setup**
      - ❌ 6.1.2.1 npx tailwindcss init -p
      - ❌ 6.1.2.2 Konfiguracja tailwind.config.js
      - ❌ 6.1.2.3 resources/css/app.css setup
      - ❌ 6.1.2.4 Dark mode konfiguracja
  - ❌ **6.2 Build i deployment assets**
    - ❌ 6.2.1 npm run build (lokalnie)
    - ❌ 6.2.2 Upload built assets na serwer
    - ❌ 6.2.3 Testowanie stylów na ppm.mpptrade.pl
    - ❌ 6.2.4 Alpine.js integration test

---

- ❌ **7. DOKUMENTACJA I QUALITY ASSURANCE**
  - ❌ **7.1 Dokumentacja techniczna**
    - ❌ 7.1.1 docs/installation/README.md
    - ❌ 7.1.2 docs/deployment/Hostido.md
    - ❌ 7.1.3 Aktualizacja CLAUDE.md
    - ❌ 7.1.4 API documentation setup
  - ❌ **7.2 Quality tools setup**
    - ❌ 7.2.1 composer require --dev phpstan/phpstan
    - ❌ 7.2.2 composer require --dev friendsofphp/php-cs-fixer  
    - ❌ 7.2.3 Konfiguracja phpstan.neon
    - ❌ 7.2.4 Testowanie quality checks

---

## ✅ WORKFLOW HYBRYDOWY
1. **Kod lokalnie** - Pisanie kodu w Windows
2. **Deploy** - `.\_TOOLS\hostido_deploy.ps1` upload na serwer
3. **Test** - Weryfikacja na https://ppm.mpptrade.pl
4. **Iterate** - Powtarzanie cyklu