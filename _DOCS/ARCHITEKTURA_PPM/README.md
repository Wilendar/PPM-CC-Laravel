# PPM-CC-Laravel - Architektura Stron i Menu

**Projekt:** PrestaShop Product Manager (PPM)
**Klient:** MPP TRADE
**Wersja Dokumentu:** 2.0
**Data Utworzenia:** 2025-10-22
**Ostatnia Aktualizacja:** 2025-10-22
**Changelog:** v2.0 - Reorganizacja menu, role-based dashboards, modularyzacja dokumentacji

---

## 📚 O Tej Dokumentacji

Ta dokumentacja została podzielona na moduły dla lepszej czytelności i łatwości utrzymania. Każdy moduł opisuje konkretny aspekt architektury aplikacji PPM.

---

## 📋 Moduły Dokumentacji

### Podstawy Architektury

1. **[Cel Dokumentu](01_CEL_DOKUMENTU.md)** - Założenia i cele architektury
2. **[Struktura Menu](02_STRUKTURA_MENU.md)** - Hierarchiczna struktura menu aplikacji (v2.0)
3. **[Routing Table](03_ROUTING_TABLE.md)** - Kompletna tabela routingu
4. **[Macierz Uprawnień](04_MACIERZ_UPRAWNIEN.md)** - 7-poziomowy system ról

### Szczegółowe Opisy Stron

5. **[Dashboard](05_DASHBOARD.md)** - Role-based dashboard z różnymi widokami
6. **[Sklepy PrestaShop](06_SKLEPY_PRESTASHOP.md)** - Zarządzanie połączeniami i synchronizacją
7. **[Produkty](07_PRODUKTY.md)** - System zarządzania produktami + Import/Export
8. **[Cennik](08_CENNIK.md)** - Grupy cenowe i zarządzanie cenami
9. **[Warianty & Cechy](09_WARIANTY_CECHY.md)** - System wariantów i dopasowań pojazdów
10. **[Dostawy & Kontenery](10_DOSTAWY_KONTENERY.md)** - System dostaw i przyjęć magazynowych
11. **[Zamówienia](11_ZAMOWIENIA.md)** - Zarządzanie zamówieniami i rezerwacjami
12. **[Reklamacje](12_REKLAMACJE.md)** - System reklamacji
13. **[Raporty & Statystyki](13_RAPORTY_STATYSTYKI.md)** - Business Intelligence
14. **[System (Admin Panel)](14_SYSTEM_ADMIN.md)** - Panel administracyjny
15. **[Profil Użytkownika](15_PROFIL_UZYTKOWNIKA.md)** - Zarządzanie profilem i aktywnością
16. **[Pomoc](16_POMOC.md)** - Dokumentacja i wsparcie

### Guidelines i Design System

17. **[UI/UX Guidelines](17_UI_UX_GUIDELINES.md)** - Zasady projektowania interfejsu
18. **[Design System](18_DESIGN_SYSTEM.md)** - Paleta kolorów, typografia, komponenty
19. **[Responsive Design](19_RESPONSIVE_DESIGN.md)** - Zasady responsywności
20. **[Implementation Checklist](20_IMPLEMENTATION_CHECKLIST.md)** - Checklista implementacji
21. **[Status Implementacji](21_STATUS_IMPLEMENTACJI.md)** - Aktualny status projektu

---

## 🔑 Kluczowe Zmiany v2.0

### 1. Reorganizacja Menu
- ❌ **Usunięto:** Kategoria "ZARZĄDZANIE"
- ✅ **Przeniesiono:** Import/Export → sekcja PRODUKTY
- ✅ **Przeniesiono:** Integracje ERP → sekcja SYSTEM (dynamiczna lista)

### 2. Role-Based Dashboards
- 7 różnych wersji dashboard per rola użytkownika
- Optimized UX dla każdej roli (Admin, Menadżer, Magazynier, etc.)

### 3. Unified Import System
- CSV + XLSX → jeden interfejs "Import z pliku"
- Wspólny routing: `/admin/products/import`

### 4. Eksport Masowy Redesign
- Usunięto osobną stronę "Sklepy > Eksport masowy"
- Przycisk "Eksportuj wszystko" w Lista Produktów

### 5. Dynamic ERP Integrations
- Plugin-based architecture
- Możliwość dodawania custom integrations

---

## 🎯 Szybki Start

1. **Zacznij od:** [Cel Dokumentu](01_CEL_DOKUMENTU.md) - zrozumienie założeń
2. **Struktura menu:** [Struktura Menu](02_STRUKTURA_MENU.md) - przegląd nawigacji
3. **Routing:** [Routing Table](03_ROUTING_TABLE.md) - kompletna mapa URL
4. **Uprawnienia:** [Macierz Uprawnień](04_MACIERZ_UPRAWNIEN.md) - kto ma dostęp do czego

**Dla deweloperów:**
- UI/UX: [UI/UX Guidelines](17_UI_UX_GUIDELINES.md)
- Design: [Design System](18_DESIGN_SYSTEM.md)
- Responsive: [Responsive Design](19_RESPONSIVE_DESIGN.md)

**Dla PM/Product Ownerów:**
- Status: [Status Implementacji](21_STATUS_IMPLEMENTACJI.md)
- Checklist: [Implementation Checklist](20_IMPLEMENTATION_CHECKLIST.md)

---

## 📞 Kontakt

**Projekt:** PPM-CC-Laravel
**Klient:** MPP TRADE
**Tech Stack:** Laravel 12.x + Livewire 3.x + Alpine.js
**Deployment:** ppm.mpptrade.pl (Hostido)

---

**Ostatnia aktualizacja:** 2025-10-22
