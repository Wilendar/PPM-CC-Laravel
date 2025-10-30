# 01. Cel Dokumentu

[◀ Powrót do spisu treści](README.md)

---

## 🎯 Cel Dokumentu

Zaprojektowana kompleksowa struktura menu i stron aplikacji **PPM (PrestaShop Product Manager)** bazująca na:

### Źródła Architektury

- **12 ETAPÓW** planu projektu z `Plan_Projektu/`
- **Specyfikacja** z `_init.md` - wymagania klienta MPP TRADE
- **Obecny stan** implementacji z `routes/web.php` i navigation
- **7-poziomowy system** uprawnień (Admin → Użytkownik)
- **Role-based UI** - różne dashboards i funkcjonalności per rola

### Główne Założenia

#### 1. Enterprise-Grade Application
- Klasa enterprise - bez skrótów i uproszczeń
- Wszystko konfigurowane przez admin (zero hardcode'u)
- Bezpieczeństwo: walidacja, sanitization, error handling
- Best practices: Laravel + Prestashop oficjalna dokumentacja

#### 2. Multi-Store Support
- Zarządzanie wieloma sklepami PrestaShop jednocześnie
- Dedykowane dane per sklep (opisy, kategorie, cechy)
- Centralized hub produktów dla organizacji MPP TRADE
- Synchronizacja bi-directional z monitoring rozbieżności

#### 3. Hierarchia Uprawnień (7 poziomów)
1. **Admin** - pełny dostęp + zarządzanie użytkownikami/sklepami/ERP
2. **Menadżer** - zarządzanie produktami + eksport + import CSV/ERP
3. **Redaktor** - edycja opisów/zdjęć + eksport (bez usuwania produktów)
4. **Magazynier** - panel dostaw (bez rezerwacji z kontenera)
5. **Handlowiec** - rezerwacje z kontenera (bez widoczności cen zakupu)
6. **Reklamacje** - panel reklamacji
7. **Użytkownik** - odczyt + wyszukiwarka

#### 4. Role-Based UI (NOWOŚĆ v2.0)
- Różne dashboards per rola użytkownika
- Optimized UX dla każdej roli
- Smart filtering menu items based on permissions
- Context-aware quick actions

#### 5. Modułowa Architektura
- Sekcje menu logicznie pogrupowane
- Routing RESTful + resource-based
- Separation of concerns (controllers, services, models)
- Reusable components (Livewire + Alpine.js)

### Kluczowe Funkcjonalności

#### Zarządzanie Produktami
- **SKU jako główny klucz** (universal identifier)
- Kategorie wielopoziomowe (5 poziomów: Kategoria→Kategoria4)
- Grupy cenowe (7 grup: Detaliczna, Dealer Standard/Premium, etc.)
- Stany magazynowe (multiple warehouses)
- Warianty produktów (atrybuty: kolor, rozmiar, etc.)

#### System Dopasowań Pojazdów
- Cechy: Model, Oryginał, Zamiennik
- Format eksportu: osobne wpisy per model
- Filtrowanie per sklep PrestaShop
- Global models z możliwością "banowania" na wybranych sklepach

#### Import/Export System
- **Import XLSX:** Mapowanie kolumn z predefiniowanymi szablonami
- **CSV Import/Export:** Unified interface (oba formaty)
- **System kontenerów:** id_kontener + dokumenty odprawy
- **Weryfikacja:** Sprawdzanie poprawności przed eksportem

#### Multi-Store Sync
- Status synchronizacji (monitoring rozbieżności)
- Dedykowane dane per sklep (różne opisy/kategorie/cechy)
- Mapowanie: grupy cenowe, magazyny, kategorie
- Conflict resolution strategies

#### Integracje ERP
- **Baselinker** (priorytet #1)
- **Subiekt GT** (import/eksport + mapowanie magazynów)
- **Microsoft Dynamics** (zaawansowana integracja business)
- **Plugin-based architecture** (możliwość dodawania custom)

### Struktura Dokumentacji

Dokumentacja podzielona na **21 modułów** dla lepszej czytelności:

**Podstawy (01-04):**
- Cel, Menu, Routing, Uprawnienia

**Szczegóły Stron (05-16):**
- Dashboard, Sklepy, Produkty, Cennik, Warianty, Dostawy, Zamówienia, Reklamacje, Raporty, System, Profil, Pomoc

**Guidelines (17-21):**
- UI/UX, Design System, Responsive, Checklist, Status

### Użytkownicy Dokumentacji

#### Dla Deweloperów
- Kompletny routing table
- UI/UX guidelines i design system
- Responsive design patterns
- Implementation checklist

#### Dla Product Ownerów
- Status implementacji
- Business requirements coverage
- Feature completeness tracking

#### Dla Designerów
- Design system (kolory, typografia, komponenty)
- UI patterns i reusable components
- Responsive breakpoints

#### Dla Testerów
- Feature checklist
- Permission matrix (co testować per rola)
- Expected behavior descriptions

---

## 📖 Nawigacja

- **Następny moduł:** [02. Struktura Menu](02_STRUKTURA_MENU.md)
- **Powrót:** [Spis treści](README.md)
