# PPM-CC-Laravel - Architektura Stron i Menu

**Projekt:** PrestaShop Product Manager (PPM)
**Klient:** MPP TRADE
**Wersja Dokumentu:** 2.0
**Data Utworzenia:** 2025-10-22
**Ostatnia Aktualizacja:** 2025-10-22

---

## 📢 UWAGA: Dokumentacja Podzielona na Moduły

**Ze względu na wielkość dokumentacji (2000+ linii), została ona podzielona na 21 modułów tematycznych dla lepszej czytelności i łatwości utrzymania.**

### 📁 Lokalizacja Modułów

**Folder:** `_DOCS/ARCHITEKTURA_PPM/`

**Struktura:**
- **README.md** - Główny indeks z linkami do wszystkich modułów
- **01-04:** Podstawy architektury (Cel, Menu, Routing, Uprawnienia)
- **05-16:** Szczegółowe opisy stron (Dashboard, Sklepy, Produkty, Cennik, etc.)
- **17-21:** Guidelines (UI/UX, Design System, Responsive, Checklist, Status)

---

## 🚀 Szybki Dostęp

### 📖 [PEŁNA DOKUMENTACJA → README.md](ARCHITEKTURA_PPM/README.md)

**Rozpocznij od głównego indeksu**, który zawiera linki do wszystkich 21 modułów tematycznych.

---

## 📋 Lista Modułów (Quick Reference)

### Podstawy (01-04)
1. **[Cel Dokumentu](ARCHITEKTURA_PPM/01_CEL_DOKUMENTU.md)** - Założenia i cele architektury
2. **[Struktura Menu](ARCHITEKTURA_PPM/02_STRUKTURA_MENU.md)** - Hierarchia menu v2.0 (reorganizacja)
3. **[Routing Table](ARCHITEKTURA_PPM/03_ROUTING_TABLE.md)** - Kompletna tabela 49 route'ów
4. **[Macierz Uprawnień](ARCHITEKTURA_PPM/04_MACIERZ_UPRAWNIEN.md)** - 7-poziomowy system ról

### Szczegółowe Opisy Stron (05-16)
5. **[Dashboard](ARCHITEKTURA_PPM/05_DASHBOARD.md)** - Role-based dashboards (7 wersji)
6. **[Sklepy PrestaShop](ARCHITEKTURA_PPM/06_SKLEPY_PRESTASHOP.md)** - Zarządzanie połączeniami i sync
7. **[Produkty](ARCHITEKTURA_PPM/07_PRODUKTY.md)** - System produktów + formularz 12-tab + Import/Export
8. **[Cennik](ARCHITEKTURA_PPM/08_CENNIK.md)** - Grupy cenowe i zarządzanie cenami
9. **[Warianty & Cechy](ARCHITEKTURA_PPM/09_WARIANTY_CECHY.md)** - Warianty, cechy pojazdów, dopasowania
10. **[Dostawy & Kontenery](ARCHITEKTURA_PPM/10_DOSTAWY_KONTENERY.md)** - System dostaw i przyjęć
11. **[Zamówienia](ARCHITEKTURA_PPM/11_ZAMOWIENIA.md)** - Zamówienia i rezerwacje z kontenera
12. **[Reklamacje](ARCHITEKTURA_PPM/12_REKLAMACJE.md)** - System reklamacji
13. **[Raporty & Statystyki](ARCHITEKTURA_PPM/13_RAPORTY_STATYSTYKI.md)** - Business Intelligence
14. **[System (Admin Panel)](ARCHITEKTURA_PPM/14_SYSTEM_ADMIN.md)** - 8 podsekcji admin (✅ COMPLETED)
15. **[Profil Użytkownika](ARCHITEKTURA_PPM/15_PROFIL_UZYTKOWNIKA.md)** - Zarządzanie profilem
16. **[Pomoc](ARCHITEKTURA_PPM/16_POMOC.md)** - Dokumentacja i wsparcie

### Guidelines (17-21)
17. **[UI/UX Guidelines](ARCHITEKTURA_PPM/17_UI_UX_GUIDELINES.md)** - Zasady projektowania interfejsu
18. **[Design System](ARCHITEKTURA_PPM/18_DESIGN_SYSTEM.md)** - Paleta kolorów, typografia, komponenty
19. **[Responsive Design](ARCHITEKTURA_PPM/19_RESPONSIVE_DESIGN.md)** - Breakpoints i mobile-first
20. **[Implementation Checklist](ARCHITEKTURA_PPM/20_IMPLEMENTATION_CHECKLIST.md)** - Checklista implementacji
21. **[Status Implementacji](ARCHITEKTURA_PPM/21_STATUS_IMPLEMENTACJI.md)** - Aktualny status projektu (35%)

---

## 🔑 Kluczowe Zmiany v2.0

### 1. Reorganizacja Menu
- ❌ **Usunięto:** Kategoria "ZARZĄDZANIE"
- ✅ **Przeniesiono:** Import/Export → PRODUKTY
- ✅ **Przeniesiono:** Integracje ERP → SYSTEM (dynamiczna lista)

### 2. Role-Based Dashboards
- 7 różnych wersji dashboard per rola użytkownika
- Optimized UX dla każdej roli (Admin, Menadżer, Magazynier, etc.)

### 3. Unified Import System
- CSV + XLSX → jeden interfejs "Import z pliku"
- Routing: `/admin/products/import`

### 4. Dynamic ERP Integrations
- Plugin-based architecture
- Możliwość dodawania custom integrations
- Route: `/admin/integrations/{slug}`

---

## 📊 Statystyki Dokumentacji

**Total Modules:** 21 modułów tematycznych
**Total Routes:** 49 route'ów aplikacji
**Total Sections:** 12 głównych sekcji menu
**Total Roles:** 7-poziomowy system uprawnień

**Coverage:**
- ✅ 100% coverage głównych funkcjonalności
- ✅ Szczegółowe UI/UX patterns
- ✅ Implementation guidelines
- ✅ Status tracking

---

## 📞 Dla Kogo Ta Dokumentacja?

**Deweloperzy:**
- Routing table
- UI/UX guidelines
- Design system
- Implementation checklist

**Product Ownerzy:**
- Status implementacji
- Business requirements
- Feature coverage

**Designerzy:**
- Design system
- UI patterns
- Responsive guidelines

**Testerzy:**
- Feature checklist
- Permission matrix
- Expected behavior

---

## 🎯 Następne Kroki

1. **Przeczytaj:** [README.md](ARCHITEKTURA_PPM/README.md) - główny indeks
2. **Zacznij od:** [Cel Dokumentu](ARCHITEKTURA_PPM/01_CEL_DOKUMENTU.md) - zrozumienie założeń
3. **Struktura:** [Struktura Menu](ARCHITEKTURA_PPM/02_STRUKTURA_MENU.md) - przegląd nawigacji
4. **Routing:** [Routing Table](ARCHITEKTURA_PPM/03_ROUTING_TABLE.md) - mapa URL
5. **Uprawnienia:** [Macierz Uprawnień](ARCHITEKTURA_PPM/04_MACIERZ_UPRAWNIEN.md) - kto ma dostęp

---

**Projekt:** PPM-CC-Laravel
**Klient:** MPP TRADE
**Tech Stack:** Laravel 12.x + Livewire 3.x + Alpine.js
**Deployment:** ppm.mpptrade.pl (Hostido)

**Ostatnia aktualizacja:** 2025-10-22
