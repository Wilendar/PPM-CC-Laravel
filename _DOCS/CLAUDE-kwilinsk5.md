# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Projekt: PPM (Prestashop Product Manager)

Aplikacja PIM (Product Information Management) klasy enterprise do zarządzania produktami na wielu sklepach PrestaShop jednocześnie. Aplikacja budowana jako hub produktowy dla organizacji MPP Trade.

## Tech Stack & Environment

**Backend**: PHP 8.3 + Laravel 12.x
**Frontend**: Blade + Livewire 3.x + Alpine.js 
**Database**: MySQL (hosting) / MariaDB MySQL (hosting)
**Cache/Kolejki**: Redis lub driver database
**Build**: Vite (tylko lokalne buildy)
**Import**: Laravel-Excel (PhpSpreadsheet)
**Autoryzacja**: Laravel Socialite (Google Workspace + Microsoft Entra ID) - implementacja na końcu projektu

## Środowisko rozwojowe

- **OS**: Windows + PowerShell 7
- **Kodowanie**: UTF-8 (bez BOM dla .ps1)
- **Język**: Polski we wszystkich odpowiedziach i komentarzach
- **Hosting**: MyDevil.net (s53.mydevil.net)
- **Domena produkcyjna**: ppm.mpptrade.pl
- **MySQL**: pgsql53.mydevil.net:5432, baza: p1070_ppm

## Kluczowe komendy

```bash
# Rozwój lokalny
composer install
php artisan serve
npm install && npm run dev

# Build produkcyjny
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migracje
php artisan migrate
php artisan migrate:rollback

# Testy
php artisan test
./vendor/bin/phpunit

# Deploy na serwer produkcyjny (SSH)
ssh mpptrade@s53.mydevil.net
```

## Architektura aplikacji

### Struktura użytkowników i uprawnień
- **Admin**: Pełne uprawnienia + zarządzanie użytkownikami, sklepami, ERP
- **Menadżer**: CRUD produktów + eksport + import masowy z plików/ERP
- **Redaktor**: Edycja opisów, zdjęć, kategorii, cech produktów
- **Magazynier**: Panel dostaw + edycja kontenerów (bez rezerwacji)
- **Handlowiec**: Panel zamówień + rezerwacja towarów z kontenerów
- **Reklamacje**: Panel reklamacji + uprawnienia użytkownika
- **Użytkownik**: Wyszukiwanie i odczyt produktów (bez wyświetlania bez zapytania)

### Model danych produktu
**Klucz**: SKU (unikalny indeks główny)
**Podstawowe**: Nazwa, Kategoria (wielopoziomowa), Opisy (HTML WYSIWYG)
**Cennik**: 7 grup cenowych (Detaliczna, Dealer Standard/Premium, Warsztat/Premium, Szkółka-Komis-Drop, Pracownik)
**Stany**: Wielomagazynowe (MPPTRADE, Pitbike.pl, Cameraman, Otopit, INFMS, Reklamacje + konfigurowalne)
**Dostawy**: Status, data dostawy, kontener, lokalizacja magazynowa
**Media**: Max 20 zdjęć (jpg,jpeg,png,webp)
**Warianty**: SKU wariantu, dedykowane ceny/stany/zdjęcia/lokalizacje

### Integracje
**PrestaShop API**: Dwukierunkowa synchronizacja produktów, kategorii, cech, zdjęć
**ERP Systems**: Baselinker, Subiekt GT, Microsoft Dynamics
**Import XLSX**: Mapowanie kolumn, szablony dla części/pojazdów
**System dopasowań**: Model->Oryginał/Zamiennik dla części zamiennych

### Funkcje specjalne
**Wyszukiwarka**: Inteligentna z podpowiedziami, obsługa błędów, tryb dokładny
**System dostaw**: Kontenery, zamówienia, aplikacja magazynowa Android
**Mapowanie**: Kategorie, magazyny, grupy cenowe między systemami
**Weryfikacja**: Rozbieżności między aplikacją a PrestaShop/ERP

## Struktura folderów projektu

```
/
├── _DOCS/          # Dokumentacja (.txt, .pdf, .md)
├── _AGENT_REPORTS/ # Raporty pracy agentów
├── _TOOLS/         # Narzędzia projektowe  
├── _TEST/          # Pliki i skrypty testowe
├── _OTHER/         # Niesklasyfikowane pliki
├── Plan_Projektu/  # Plany etapów (osobne pliki MD)
├── References/     # Mockupy UI i pliki źródłowe
└── [kod Laravel]   # Struktura standardowa Laravel
```

## Zasady rozwoju

- **ZAKAZ**: Hardcodowania wartości (chyba że wyraźnie określono)
- **ZAWSZE**: Używaj TodoWrite do trackowania zadań
- **PLAN**: Wykonywanie punkt po punkcie, etap po etapie
- **RAPORTOWANIE**: Dokładne statusy ❌→🛠️→✅ z linkami do plików
- **BEZPIECZEŃSTWO**: Try-catch, timeout, walidacja, hash haseł
- **JAKOŚĆ**: Kod klasy enterprise, bez skrótów i uproszczeń

## Deployment

Aplikacja działa hybrydowo:
1. Rozwój lokalny (edycja, testowanie)
2. Automatyczny deploy na ppm.mpptrade.pl dla weryfikacji
3. Każda zmiana musi być przetestowana na serwerze produkcyjnym

## Dokumentacja API

- **PrestaShop**: https://devdocs.prestashop-project.org/8/ + https://devdocs.prestashop-project.org/9/
- **BaseLinker**: https://api.baselinker.com/
- **Microsoft Dynamics**: https://learn.microsoft.com/en-us/dynamics365/business-central/

## Uwagi bezpieczeństwa

- Uwierzytelnianie OAuth (Google Workspace + Microsoft)
- Whitelist adresów email (tylko zaproszeni użytkownicy)
- Brak publicznej rejestracji
- Admin zakłada pierwszy profil przy instalacji