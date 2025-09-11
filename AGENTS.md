# CLAUDE.md

### KRYTYCZNE: NATYCHMIASTOWY DEPLOY PO ZMIANACH

- Po każdej zmianie w repo wykonuj natychmiastowy deploy na Hostido (prod) – bez czekania na osobne potwierdzenie.
- Procedura minimalna (domyślna):
  - Upload: `_TOOLS/hostido_deploy.ps1 -SourcePath "." -TargetPath "/domains/ppm.mpptrade.pl/public_html/"`
  - Komendy: `_TOOLS/hostido_deploy.ps1 -Command "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force && php artisan view:clear && php artisan config:clear && php artisan cache:clear"`
  - Health-check: odwiedź `/up`, smoke-test `/admin` (403/200 oraz widżety dashboardu)
- Backup (zalecany przy migracjach/ryzyku):
  - `_TOOLS/hostido_deploy.ps1 -CreateBackup -BackupName "auto_YYYYMMDD_HHMM"`
- Awaria/Brak dostępu:
  - Jeśli deploy nie jest możliwy (np. brak sieci/SSH), dodaj wpis do `_REPORTS` (⚠️ DEPLOY PENDING) z powodem i następnie wznowisz deployment przy pierwszej możliwości.
- Bezpieczeństwo:
  - Nie wykonuj destrukcyjnych operacji (rm -rf, reset danych) bez wyraźnego polecenia. Zawsze korzystaj ze skryptów w `_TOOLS` i preferuj backup + bezpieczne komendy Laravela.

### ZASADY AKTUALIZACJI PLANU (Plan_Projektu/*)

- Aktualizuj istniejące punkty: edytuj linie bezpośrednio w pliku planu. Nie dopisuj osobnych sekcji typu „AKTUALIZACJA PLANU” na końcu pliku.
- Statusy kroków: stosuj sekwencję: ❌ (nie rozpoczęte) → 🛠️ (w trakcie) → ✅ (ukończone); używaj ⚠️ dla blokerów z krótkim opisem przyczyny i odnośnikiem do blokującego podpunktu.
- Reguła PLIK: dodawaj „└──📁 PLIK: …” WYŁĄCZNIE przy statusie ✅. Wcięcie musi być wyrównane pod linią z ✅. Podawaj klikalne ścieżki względne (np. `app/...`, `resources/...`, `routes/...`).
- Granularność: oznaczaj status na najniższym poziomie (np. 1.1.2.1.4), nie tylko na poziomie nagłówka rodzica. Rodzic może pozostać 🛠️, jeśli część zadań dzieci nadal trwa.
- Spójność numeracji: nie zmieniaj numerów istniejących zadań i nie twórz nowych „ad-hoc” bez decyzji. Pracuj w obrębie wskazanej FAZY/podsekcji.
- Weryfikacja przed ✅: przed zmianą na ✅ upewnij się, że kod istnieje i przechodzi podstawowy smoke-check (np. komponent się renderuje, trasa odpowiada, logika działa).
- Zakres PLIK: wpisuj tylko najważniejsze 3–5 plików dla danego podpunktu – unikaj nadmiernie długich list.
- Raport po zmianie: po istotnej aktualizacji planu dodaj raport do `_REPORTS` zgodnie z szablonem (`[Punkt_Planu]_REPORT.md`) z listą plików i krótkim opisem zmian.

Przykład poprawnej zmiany punktu:

```
- ❌ 1.1.2.1.4 Recent Activity count (last 24h)
+ ✅ 1.1.2.1.4 Recent Activity count (last 24h)
      └──📁 PLIK: app/Http/Livewire/Dashboard/AdminDashboard.php
```

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Projekt: PPM-CC-Laravel (Prestashop Product Manager)

Aplikacja klasy enterprise do zarządzania produktami na wielu sklepach Prestashop jednocześnie, będąca centralnym hubem produktów dla organizacji MPP TRADE.

## Środowisko Techniczne

### Stack Technologiczny
- **Backend**: PHP 8.3 + Laravel 12.x
- **UI**: Blade + Livewire 3.x + Alpine.js 
- **Build**: Vite (tylko lokalne buildy)
- **DB**: MySQL SQL
- **Cache/Kolejki**: Redis (lub driver database jako fallback)
- **Import XLSX**: Laravel-Excel (PhpSpreadsheet)
- **Autoryzacja**: Laravel Socialite (Google Workspace + Microsoft Entra ID) - implementacja na końcu

### Środowisko Deployment
- **Domena**: ppm.mpptrade.pl
- **Hosting**: Hostido.net.pl
- **SSH**: host379076@host379076.hostido.net.pl:64321 (klucz SSH wymagany)
- **Baza**: host379076_ppm@localhost (MariaDB 10.11.13)
- **PHP**: 8.3.23 (natywnie dostępny)
- **Composer**: 2.8.5 (preinstalowany)

## Architektura Aplikacji

### System Użytkowników (Hierarchia uprawnień)
1. **Admin** - pełny dostęp + zarządzanie użytkownikami/sklepami/ERP
2. **Menadżer** - zarządzanie produktami + eksport + import CSV/ERP
3. **Redaktor** - edycja opisów/zdjęć + eksport (bez usuwania produktów)
4. **Magazynier** - panel dostaw (bez rezerwacji z kontenera)
5. **Handlowiec** - rezerwacje z kontenera (bez widoczności cen zakupu)
6. **Reklamacje** - panel reklamacji
7. **Użytkownik** - odczyt + wyszukiwarka

### Kluczowe Encje
- **Produkty**: SKU (klucz główny), nazwa, kategorie wielopoziomowe, opisy HTML, ceny grupowe, stany magazynowe, warianty
- **Kategorie**: 5 poziomów zagnieżdżenia (Kategoria→Kategoria4)
- **Grupy Cenowe**: Detaliczna, Dealer Standard/Premium, Warsztat/Premium, Szkółka-Komis-Drop, Pracownik
- **Magazyny**: MPPTRADE, Pitbike.pl, Cameraman, Otopit, INFMS, Reklamacje + custom
- **Sklepy Prestashop**: Multi-store support z dedykowanymi opisami/kategoriami per sklep
- **Integracje ERP**: Baselinker, Subiekt GT, Microsoft Dynamics

### System Importu/Eksportu
- **Import XLSX**: Mapowanie kolumn z predefiniowanymi szablonami (POJAZDY/CZĘŚCI)
- **Kluczowe kolumny**: ORDER, Parts Name, U8 Code, MRF CODE, Qty, Ctn no., Size, Weight, Model, VIN, Engine No.
- **System kontenerów**: id_kontener + dokumenty odprawy (.zip, .xlsx, .pdf, .xml)
- **Weryfikacja**: Sprawdzanie poprawności przed eksportem na Prestashop

## Komendy i Workflow

### Development Workflow
```bash
# Lokalne środowisko development
php artisan serve
php artisan migrate
php artisan db:seed

# Build assets
npm install
npm run dev       # Development
npm run build     # Production

# Testy
php artisan test
./vendor/bin/phpunit
```

### Deployment na Hostido
```powershell
# SSH z kluczem PuTTY (ścieżka do klucza)
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

# Test połączenia
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "php -v"

# Deployment commands
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && composer install --no-dev"

# Migracje i cache
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force && php artisan config:cache"
```

### Ręczne połączenie SSH
```bash
# Wymaga klucza SSH (HostidoSSHNoPass.ppk)
ssh -p 64321 host379076@host379076.hostido.net.pl
```

### Baza Danych
```bash
# Migracje
php artisan migrate
php artisan migrate:rollback
php artisan migrate:status

# Seeders
php artisan db:seed
php artisan db:seed --class=ProductSeeder
```

## Kluczowe Funkcjonalności

### System Dopasowań Pojazdów
- **Cechy**: Model, Oryginał, Zamiennik
- **Format eksportu**: Osobne wpisy dla każdego modelu (Model: X, Model: Y, etc.)
- **Filtrowanie**: Per sklep Prestashop (globalne modele z możliwością "banowania" na wybranych sklepach)

### System Wyszukiwania
- **Inteligentna wyszukiwarka**: Podpowiedzi, obsługa błędów, literówek
- **Filtry**: "Wyszukaj dokładnie" vs. przybliżone wyszukiwanie
- **Domyślny widok**: Statystyki zamiast listy produktów (dopóki nie wyszuka)

### Synchronizacja Multi-Store
- **Status synchronizacji**: Monitoring rozbieżności między aplikacją a Prestashop/ERP
- **Dedykowane dane per sklep**: Różne opisy, kategorie, cechy
- **Mapowanie**: Grupy cenowe, magazyny, kategorie między systemami

## Struktura Folderów Projektu

```
PPM-CC-Laravel/
├── _init.md                    # Dokumentacja projektu
├── AGENTS.md                   # Instrukcje dla agentów
├── dane_hostingu.md           # Dane hostingu i SSH
├── References/                # Mockupy UI i pliki źródłowe
│   ├── Dashboard_admin.png
│   ├── Lista_produktów.png
│   ├── Produkt_part1.png
│   ├── ERP_Dashboard.png
│   └── JK25154D*.xlsx         # Przykładowe pliki importu
└── [Laravel structure when created]
```

## Integracje

### Prestashop API
- Multi-store support
- Zachowanie struktur katalogów dla zdjęć
- Weryfikacja zgodności z bazą danych Prestashop 8.x/9.x
- **KRYTYCZNE**: Sprawdzanie struktury DB: https://github.com/PrestaShop/PrestaShop/blob/8.2.x/install-dev/data/db_structure.sql

### ERP Systems
- **Baselinker**: Priorytet #1 dla integracji
- **Subiekt GT**: Import/eksport + mapowanie magazynów
- **Microsoft Dynamics**: Zaawansowana integracja business

## Zasady Development

### Jakość Kodu
- **Klasa Enterprise**: Bez skrótów i uproszczeń
- **Bez hardcode'u**: Wszystko konfigurowane przez admin
- **Best Practices**: Laravel + Prestashop oficjalna dokumentacja
- **Bezpieczeństwo**: Walidacja, sanitization, error handling

### System Planowania
- Plan w folderze `Plan_Projektu/` - każdy etap osobny plik
- Statusy: ❌ (nie rozpoczęte), 🛠️ (w trakcie), ✅ (ukończone), ⚠️ (zablokowane)
- Raporty agentów w `_AGENT_REPORTS/`

### Kolejność Implementacji
1. ✅ Backend fundament + modele - COMPLETED
2. ✅ Dashboard + Panel produktów - COMPLETED
3. ✅ Panel admina (FAZA A, B, C) - COMPLETED
4. ⏳ Integracja Baselinker - IN PROGRESS
5. API Prestashop
6. Frontend z prawdziwymi danymi
7. System dostaw (przyszłość)
8. System reklamacji (przyszłość)
n### FAZA C: System Administration - COMPLETED 2025-01-09
- ✅ SystemSettings - Centralized application configuration
- ✅ BackupManager - Automated backup system z monitoring
- ✅ DatabaseMaintenance - Maintenance tools i health monitoring
- ✅ Enterprise Security - Encrypted settings i audit trail
- 📍 **Routes**: /admin/system-settings, /admin/backup, /admin/maintenance

## Uwagi Specjalne
- w pliku [dane_hostingu.md](dane_hostingu.md) masz wszystkie potrzebne dane do instalacji i uploadu aplikacji na serwerze Hostido.net.pl. Zgodnie z założeniami projektu nie tworzymy lokalnego środowiska, tylko piszemy tu kod. Całe gotowe środowisko znajduje się na serwerze Hostido gdzie zawsze po napisaniu lub aktualizacji kodu eksportujesz pliki przez SSH, a nastepnie przeprowadzasz weryfikacje poprawności ich działania na stronie https://ppm.mpptrade.pl
- **Laravel lokalizacja**: `/domains/ppm.mpptrade.pl/public_html/` - Laravel bezpośrednio w public_html (bez podfolderu)
- **Hybrydowy workflow**: Lokalne development → deploy na serwer → testy na ppm.mpptrade.pl
- **PowerShell environment**: Windows + PowerShell 7, unikać polskich znaków w skryptach
- **Nie tworzyć**: Niepotrzebnych wersji plików (_v1, _v2, etc.)
- **OAuth**: Implementować jako ostatni krok (Google Workspace + Microsoft)
- **Pierwszy admin**: Setup przez bezpośrednie wprowadzenie danych (email, hasło, firma)

## Super Admin Account - Testing & Verification

**KONTO SUPER ADMINISTRATORA** (dla Claude Code testing i verification):

```
URL: https://ppm.mpptrade.pl/login
Email: admin@mpptrade.pl
Password: Admin123!MPP
Role: Admin (pełne uprawnienia)
User ID: 8
Created: 2025-09-09
Status: Active
```

**Admin Panel Routes:**
- Dashboard: `/admin` - Main admin dashboard z widgets
- Shop Management: `/admin/shops` - PrestaShop connections
- ERP Integration: `/admin/integrations` - Baselinker, Subiekt GT, Dynamics  
- System Settings: `/admin/system-settings` - Application configuration
- Backup Management: `/admin/backup` - Automated backup system
- Maintenance: `/admin/maintenance` - Database maintenance tools
- Notifications: `/admin/notifications` - Real-time notification center
- Reports: `/admin/reports` - Business Intelligence analytics
- API Management: `/admin/api` - API monitoring i usage statistics
- Customization: `/admin/customization` - Theme i layout management

**Permissions:** Wszystkie 47 admin permissions assigned
**Features:** Complete ETAP_04 Panel Administracyjny (5 faz: A, B, C, D, E)

**Last Verified:** 2025-09-09 - All admin functions operational

### PODSTAWOWE ZASADY

- **Język**: Polski we wszystkich odpowiedziach
- **Dokumentacja**: Aktualizuj AGENTS.md przy milestone/fix błędów + plan projektu przy ukończonych etapach
- **Start sesji**: Odczytaj AGENTS.md projektu + Plan_Projektu.md (utwórz jeśli brak)
- **Git repo**: "wilendar@gmail.com" / "[GITHUB_PASSWORD]" / Token: "[GITHUB_TOKEN]"
- **Środowisko:** Pracujesz w środowisku Windows z Powershell 7, używasz komendy "pwsh", możesz stosować wszystkie funkcje powershell7 jak kolory, animacje, emojii, pamietaj o kodowaniu UTF-8.
- **NIGDY** nie hardcodujesz na sztywno wpisanych wartości w kodzie, chyba, że użytkownik Cię o to wyraźnie poprosi.
- **ZAWSZE** Twórz i aktualizuj listę TODO i pokazuj ją użytkownikowi podczas wykonywania swoich prac.

### ZACHOWAJ PORZĄDEK W PROJEKCIE

- **ZAKAZ** tworzenia plików niezwiązanych z projektem w folderze **root** projektu
- Każdy typ plików powinien mieć swój wyszczególniony folder, np. pliki .txt, .pdf, .md w folderze "_DOCS"
- Wszystkie Raporty Agentów powinny się znajdować w Folderze "_AGENT_REPORTS"
- Wszystkie narzędzia stworzone na potrzeby projektu powinny się znajdować w folderze "_TOOLS"
- Wszystkie pliki/skrypty testowe powinny znajdować się w folderze "_TEST"
- Jeżeli występują pliki niesklasyfikowane, lub nie pasujące do powyższych zasad umieść je w Folderze "_OTHER"

### SYSTEM DOKUMENTACJI PRAC AGENTÓW

- **OBOWIĄZKOWY PLIK .md**: MUSISZ tworzyć plik `[Nazwa_planu_punkt_planu]_REPORT.md` z podsumowaniem swoich prac
- **LOKALIZACJA**: Pliki reportów w folderze "_REPORTS"
- **FORMAT RAPORTU AGENTA**:
```
# RAPORT PRACY AGENTA: [punkt_planu_nazwa_punktu]
**Data**: [YYYY-MM-DD HH:MM]
**Zadanie**: [krótki opis zadania]

## ✅ WYKONANE PRACE
- Lista wykonanych zadań
- Ścieżki do utworzonych/zmodyfikowanych plików
- Krótkie opisy zmian

## ⚠️ PROBLEMY/BLOKERY
- Lista napotkanych problemów
- Nierozwiązane kwestie wymagające uwagi

## 📋 NASTĘPNE KROKI
- Co należy zrobić dalej
- Zalecenia dla kolejnych agentów

## 📁 PLIKI
- [nazwa_pliku.ext] - [opis zmian]
- [folder/nazwa_pliku.ext] - [opis zmian]
```

- **PLAN**: Zawsze twórz plan wg. następującego szablonu:

  **KRYTYCZNE** twórz odnośnik do pliku z kodem do podpunktu WYŁĄCZNIE PO UKOŃCZENIU ZADANIA ✅ - NIGDY PRZED: 
          └──📁 PLIK: adres/do/pliku.cs

```
# ❌ 1. ETAP 1
## 	❌ 1.1 Zadanie Etapu 1
### 	❌ 1.1.1 Podzadanie do zadania etapu 1
			❌ 1.1.1.1 Podzadanie do podzadania do zadania etapu
				❌ 1.1.1.1.1 Głębokie podzadanie
```

**UWAAGA!** Plan Tworzysz w Folderze "Plan_Projektu", w tym folderze Każdy ETAP będzie oddzielnym plikiem w którym będą się znajdować szczegółowe i głęboko zagnieżdżone podzadania tego ETAPu. Przekaż agentom jak aktualizować i odczytywać tą strukturę planu.

Korzystaj z następujących oznaczeń statusu planu:
    ❌ Zadanie nie rozpoczęte
    🛠️ Zadanie rozpoczęte, aktualnie trwają nad nim prace
    ✅ Zadanie ukończone - DOPIERO TERAZ dodaj └──📁 PLIK: ścieżka/do/utworzonego/pliku (z wcięciem wyrównanym pod ✅)
    ⚠️ Zadanie z blokerem, odłożone na później, należy do niego wrócić po rozwiązaniu blokera, należy opisać blokera w zadaniu, ze wskazaniem podpunktu w planie który blokuje wykonania tego zadania.

### NARZĘDZIA AI

- **Lokalizacja**: `D:\OneDrive - MPP TRADE\Skrypty\Narzędzia_AI\`
- **Struktura**: `nazwa_narzędzia/` + `nazwa_narzędzia.py` + `README.md`
- **Nazwy**: `explore_*`, `create_*`, `analyze_*`, `migrate_*`, `backup_*`, `test_*`
- **Bezpieczeństwo**: Try-catch + timeout + hash passwords + walidacja
- **Po reorganizacji**: Test imports + requirements.txt + dokumentacja + test uruchomienia

### POWERSHELL - POLSKIE ZNAKI

- **BŁĄD**: PowerShell błędy z ąęćńóśźż → "Missing argument", "Unexpected token"  
- **ROZWIĄZANIE**: NIGDY polskie znaki → ASCII (ą→a, ę→e, ć→c, ń→n, ó→o, ś→s, ź/ż→z)
- **Kodowanie**: UTF-8 bez BOM dla .ps1, testuj składnię

### PLIKI & WERSJE

- **NIE TWÓRZ** wielu wersji tego samego pliku! (build_v1.ps1, build_v2.ps1, etc.)
- **Jeden plik** na funkcjonalność

### KODOWANIE UTF-8

- **PowerShell z polskimi**: UTF-8 z BOM, `$PSDefaultParameterValues['Out-File:Encoding'] = 'utf8BOM'`
- **Python**: `# -*- coding: utf-8 -*-`

### KRYTYCZNE ZASADY RAPORTOWANIA AGENTÓW

- **DOKŁADNOŚĆ POSTĘPU**: Agents MUSZĄ raportować dokładnie które podpunkty ukończone vs nieukończone
- **ZAKAZ**: NIE MOŻESZ raportować ukończenia całego etapu jeśli jakiekolwiek sekcje mają status ❌
- **STATUS ✅**: TYLKO dla faktycznie zrealizowanych zadań z działającym kodem/testami
- **PLIKI**: Dodawanie `└──📁 PLIK: ścieżka/do/pliku` TYLKO po rzeczywistym ukończeniu (z wcięciem wyrównanym pod ✅)
- **PLAN**: W planie aktualizuj ❌→🛠️ gdy rozpoczynasz, 🛠️→✅ gdy faktycznie ukończysz

**PRZYKŁAD PRAWIDŁOWEGO RAPORTOWANIA:**

```
**Status ETAPU:** 🛠️ W TRAKCIE - ukończone 2.1.1, 2.1.2 z 7 głównych sekcji (29% complete)
```

**PRZYKŁAD BŁĘDNEGO RAPORTOWANIA (NIEDOZWOLONE):**

```
**Status ETAP_02**: ✅ **UKOŃCZONY** ← 🚫 BŁĄD! Większość sekcji ma status ❌
```

### INNE

- **Autor**: Kamil Wiliński (nie Claude AI)
- **Środowisko**: Windows + PowerShell 7 (nie WSL/Linux)
