# PPM-CC-Laravel - Prestashop Product Manager

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel)
![Livewire](https://img.shields.io/badge/Livewire-3.x-4E56A6?style=for-the-badge&logo=livewire)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php)
![MariaDB](https://img.shields.io/badge/MariaDB-10.11-003545?style=for-the-badge&logo=mariadb)

**Aplikacja klasy enterprise do centralnego zarządzania produktami na wielu sklepach Prestashop jednocześnie.**

## 📋 Opis Projektu

PPM-CC-Laravel to zaawansowany system Product Information Management (PIM) dedykowany dla organizacji MPP TRADE, umożliwiający:

- 🏪 **Multi-store management** - zarządzanie produktami na wielu sklepach Prestashop z jednego miejsca
- 📊 **Import/Export XLSX** - masowe operacje z zaawansowanym mapowaniem kolumn
- 🔗 **Integracje ERP** - Baselinker, Subiekt GT, Microsoft Dynamics
- 👥 **System uprawnień** - 7 poziomów użytkowników (Admin → Użytkownik)
- 🎯 **Dopasowania pojazdów** - Model/Oryginał/Zamiennik dla branży automotive
- 💰 **Grupy cenowe** - 8 grup cenowych z elastycznym zarządzaniem
- 🔍 **Inteligentna wyszukiwarka** - podpowiedzi, obsługa błędów i literówek

## 🚀 Quick Start

### Wymagania Systemowe

- **PHP**: 8.3+ (dostępne na serwerze Hostido.net.pl)
- **Laravel**: 12.x
- **Baza danych**: MariaDB 10.11.13+
- **Node.js**: 18.17.0+ (dla lokalnego build assets)
- **Composer**: 2.8.5+

### Środowisko Produkcyjne

Aplikacja działa na:
- **URL**: https://ppm.mpptrade.pl
- **Serwer**: Hostido.net.pl
- **Laravel Path**: `/domains/ppm.mpptrade.pl/public_html/`
- **Baza**: `host379076_ppm@localhost`

### Hybrydowy Workflow Development

```powershell
# 1. Lokalna praca z kodem (bez PHP/bazy)
code .  # VS Code

# 2. Deploy na serwer przez PowerShell
.\_TOOLS\hostido_deploy.ps1

# 3. Test na produkcji
# https://ppm.mpptrade.pl

# 4. Build assets lokalnie
npm install
npm run build
.\_TOOLS\hostido_frontend_deploy.ps1
```

## 📁 Struktura Projektu

```
PPM-CC-Laravel/
├── app/
│   ├── Http/Controllers/      # Controllers Laravel
│   ├── Livewire/             # Komponenty Livewire 3.x
│   ├── Models/               # Modele Eloquent
│   └── Services/             # Business logic services
├── database/
│   ├── migrations/           # Migracje bazy danych
│   └── seeders/             # Seeders danych
├── resources/
│   ├── views/               # Blade templates
│   ├── js/                  # JavaScript + Alpine.js
│   └── css/                 # TailwindCSS styles
├── docs/                    # Dokumentacja projektu
├── _TOOLS/                  # Skrypty PowerShell deployment
├── _AGENT_REPORTS/          # Raporty agentów AI
└── Plan_Projektu/           # Plan 12 etapów projektu
```

## ⚡ Tech Stack

### Backend
- **Laravel 12.x** - Framework PHP
- **Livewire 3.x** - Full-stack komponenty
- **Spatie Laravel Permission** - System uprawnień
- **Laravel Excel** - Import/Export XLSX
- **MariaDB 10.11** - Baza danych

### Frontend
- **Blade Templates** - System templatek
- **TailwindCSS 4.0** - Styling
- **Alpine.js 3.15** - Interakcje JavaScript
- **Vite 7.x** - Asset bundling

### DevOps & Tools
- **PowerShell 7** - Deployment scripts
- **SSH/SFTP** - Połączenie z serwerem
- **Hostido.net.pl** - Hosting produkcyjny
- **WinSCP** - Transfer plików

## 🛠️ Instalacja

### Szczegółowe instrukcje instalacji:
📚 **[docs/INSTALLATION.md](docs/INSTALLATION.md)** - Kompletny przewodnik instalacji krok po kroku

### Szybka instalacja na serwerze (wykonana):

```bash
# SSH na serwer Hostido
ssh -p 64321 host379076@host379076.hostido.net.pl

# Laravel zainstalowany w public_html
cd /domains/ppm.mpptrade.pl/public_html/

# Pakiety zainstalowane
composer install --no-dev --optimize-autoloader

# Konfiguracja .env (wykonana)
php artisan key:generate
php artisan migrate
```

## 🚀 Deployment

### Hybrydowy workflow deployment:
📚 **[docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)** - Szczegółowe instrukcje deployment

### Podstawowe komendy:

```powershell
# Deployment aplikacji
.\_TOOLS\hostido_deploy.ps1

# Build i upload assets
.\_TOOLS\hostido_build.ps1

# Quick upload (development)
.\_TOOLS\hostido_deploy.ps1 -UploadOnly

# Health check aplikacji
.\_TOOLS\hostido_deploy.ps1 -HealthCheck
```

## 🏗️ Architektura

### System użytkowników (7 poziomów):
1. **Admin** - pełny dostęp + zarządzanie
2. **Menadżer** - zarządzanie produktami + import/export
3. **Redaktor** - edycja opisów/zdjęć
4. **Magazynier** - panel dostaw
5. **Handlowiec** - rezerwacje z kontenera
6. **Reklamacje** - panel reklamacji
7. **Użytkownik** - odczyt + wyszukiwarka

### Szczegółowa dokumentacja architektury:
📚 **[docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)** - Kompletny opis architektury systemu

## 🔌 Integracje

### Prestashop API
- Multi-store support (8.x/9.x compatibility)
- Synchronizacja produktów, kategorii, cen
- Dedykowane opisy i kategorie per sklep

### ERP Systems
- **Baselinker** - Priorytet #1
- **Subiekt GT** - Import/eksport + mapowanie
- **Microsoft Dynamics** - Enterprise integration

### Import/Export
- **XLSX Files** - Mapowanie kolumn POJAZDY/CZĘŚCI
- **Kluczowe kolumny**: ORDER, Parts Name, U8 Code, MRF CODE, Qty, Ctn no.
- **System kontenerów** - dokumenty odprawy (.zip, .xlsx, .pdf, .xml)

## 🎯 Status Projektu - ETAP_01 UKOŃCZONY ✅

### Ukończone funkcjonalności:
- ✅ Laravel 12.x zainstalowany i działający na https://ppm.mpptrade.pl
- ✅ MariaDB skonfigurowana (host379076_ppm)
- ✅ SSH/SFTP połączenie skonfigurowane (port 64321)
- ✅ Kluczowe pakiety w composer.json:
  - Livewire 3.x
  - Laravel Excel 3.1
  - Spatie Permissions 6.x
- ✅ PowerShell deployment scripts w _TOOLS/
- ✅ TailwindCSS + Alpine.js + Vite frontend stack

### Następny etap:
🚧 **ETAP_02** - Modele bazy danych i migracje

## 📖 Dokumentacja

| Dokument | Opis |
|----------|------|
| [INSTALLATION.md](docs/INSTALLATION.md) | Szczegółowa instalacja krok po kroku |
| [DEPLOYMENT.md](docs/DEPLOYMENT.md) | Hybrydowy workflow i skrypty PowerShell |
| [ARCHITECTURE.md](docs/ARCHITECTURE.md) | Architektura systemu i komponenty |
| [Plan_Projektu/](Plan_Projektu/) | 12 etapów rozwoju projektu |
| [CLAUDE.md](CLAUDE.md) | Instrukcje dla Claude Code |

## 🔧 Development

### Komendy composer:
```bash
# Testy jakości kodu
composer phpstan        # Analiza statyczna
composer cs-fix        # Formatowanie PSR-12
composer quality       # Kombinacja powyższych

# Testy
composer test          # PHPUnit
php artisan test       # Laravel tests
```

### Livewire komponenty:
```bash
# Tworzenie komponentów
php artisan make:livewire ProductList
php artisan make:livewire Admin/Dashboard
```

### Database operations:
```bash
# Migracje
php artisan migrate
php artisan migrate:rollback
php artisan migrate:refresh --seed
```

## 🤝 Współpraca

### System agentów AI:
📚 **[AI_AGENTS_GUIDE.md](AI_AGENTS_GUIDE.md)** - Przewodnik systemu agentów

### Raportowanie prac:
- Wszystkie raporty w folderze `_AGENT_REPORTS/`
- Plan projektu w `Plan_Projektu/` (12 etapów)
- Statusy: ❌ (nie rozpoczęte), 🛠️ (w trakcie), ✅ (ukończone)

## 📞 Support

**Właściciel projektu**: MPP TRADE  
**Framework**: Laravel 12.x (LTS)  
**Dokumentacja Laravel**: https://laravel.com/docs/12.x  
**Dokumentacja Livewire**: https://livewire.laravel.com/docs/quickstart  

## 📄 Licencja

Proprietary - MPP TRADE © 2024

---

**Status**: 🟢 Production Ready (ETAP_01 ukończony)  
**Wersja**: 1.0.0 (Laravel 12.28.1)  
**Ostatnia aktualizacja**: 2024-09-08