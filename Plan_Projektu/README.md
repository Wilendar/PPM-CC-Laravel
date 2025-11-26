# 📋 PLAN ROZWOJU APLIKACJI PPM
## Prestashop Product Manager - System PIM klasy Enterprise

**UWAGA** WYŁĄCZ autoryzację AdminMiddleware na czas developmentu! PRZED OSTATECZNYM DEPLOY UPEWNIJ SIĘ ŻE AUTORYZACJA JEST WSZĘDZIE WŁĄCZONA !

**Ostatnia aktualizacja:** 2025-11-18  
**Status projektu:** 🛠️ W TRAKCIE OPRACOWANIA  
**Szacowany czas realizacji:** 20 tygodni  

---

## 🎯 OPIS PROJEKTU

Prestashop Product Manager (PPM) to zaawansowany system PIM (Product Information Management) klasy enterprise, zaprojektowany dla organizacji MPP Trade. Aplikacja służy jako centralny hub produktowy, integrujący wiele sklepów PrestaShop z różnymi systemami ERP.

### Kluczowe funkcjonalności:
- 🏪 **Multi-shop PrestaShop** - zarządzanie produktami na wielu sklepach jednocześnie
- 🔗 **Integracje ERP** - Baselinker, Subiekt GT, Microsoft Dynamics
- 👥 **7-poziomowy system uprawnień** - od Admina po Użytkownika
- 📊 **Import/Export XLSX** - z dynamicznym mapowaniem kolumn
- 🚚 **System dostaw** - kontenery, zamówienia, dokumenty odpraw
- 🔍 **Inteligentna wyszukiwarka** - z autosugestiami i tolerancją błędów
- 📱 **Aplikacja magazynowa Android** - do przyjęć dostaw

---

## 🏗️ ARCHITEKTURA TECHNICZNA

### Stack technologiczny:
- **Backend:** PHP 8.3 + Laravel 12.x
- **Frontend:** Blade + Livewire 3.x + Alpine.js
- **Database:** MySQL (produkcja) / MariaDB (lokalne)
- **Build:** Vite (tylko lokalne buildy)
- **Import:** Laravel-Excel (PhpSpreadsheet)
- **Cache:** Redis lub database driver
- **Autoryzacja:** Laravel Socialite (Google + Microsoft)

### Hosting i deploy:
- **Serwer:** Hostido.net.pl (host379076.hostido.net.pl)
- **Domena:** ppm.mpptrade.pl
- **Deploy:** SSH/SFTP hybrydowy (lokalnie → serwer)
- **Baza:** MariaDB (localhost:3306)

---

## 📚 STRUKTURA PLANU - 13 ETAPÓW

### 🔵 FAZA 1: FUNDAMENT (Tygodnie 1-4)
| Etap | Nazwa | Status | Czas | Priorytet |
|------|-------|---------|------|-----------|
| [01](ETAP_01_Fundament.md) | Fundament i Architektura Projektu | ✅ UKOŃCZONY | 40h | 🔴 KRYTYCZNY |
| [02](ETAP_02_Modele_Bazy.md) | Modele i Struktura Bazy Danych | ✅ UKOŃCZONY | 35h | 🔴 KRYTYCZNY |
| [03](ETAP_03_Autoryzacja.md) | System Autoryzacji i Uprawnień | ✅ UKOŃCZONY | 30h | 🟡 WYSOKI |

### 🟢 FAZA 2: CORE FUNKCJONALNOŚCI (Tygodnie 5-10)
| Etap | Nazwa | Status | Czas | Priorytet |
|------|-------|---------|------|-----------|
| [04](ETAP_04_Panel_Admin.md) | Panel Administracyjny (zob. ETAP_04a_Panel_Admin_CC.md) | ✅ UKOŃCZONY (FAZA A-E wdrożone) | 45h | 🟡 WYSOKI |
| [05](ETAP_05_Produkty.md) | Moduł Produktów - Rdzeń Aplikacji (statusy w pod-ETAPach 05a-05d) | 🛠️ W TRAKCIE (~50%: 05a 77%, 05b 52%, 05c po FAZIE 3, 05d 25%) | 60h | 🔴 KRYTYCZNY |
| [06](ETAP_06_Import_Export.md) | System Import/Export XLSX | ❌ NIE ROZPOCZĘTE | 40h | 🟡 WYSOKI |

**Uwaga ETAP 05:** szczegółowe statusy i zakres znajdują się w `ETAP_05a_Produkty.md`, `ETAP_05b_Produkty_Warianty.md`, `ETAP_05c_Produkty_Cechy.md` oraz `ETAP_05d_Produkty_Dopasowania.md`.

### 🟡 FAZA 3: INTEGRACJE (Tygodnie 11-15)
| Etap | Nazwa | Status | Czas | Priorytet |
|------|-------|---------|------|-----------|
| [07](ETAP_07_Prestashop_API.md) | Integracja PrestaShop API | 🛠️ W TRAKCIE (FAZY 1-3 ukończone; FAZA 5 w toku; FAZA 9 ~40%) | 50h | 🔴 KRYTYCZNY |
| [08](ETAP_08_ERP_Integracje.md) | Integracje z Systemami ERP | ❌ NIE ROZPOCZĘTE | 45h | 🟡 WYSOKI |
| [09](ETAP_09_Wyszukiwanie.md) | System Wyszukiwania | ❌ NIE ROZPOCZĘTY | 35h | 🟢 ŚREDNI |

### 🔴 FAZA 4: ZAAWANSOWANE FUNKCJE (Tygodnie 16-20)
| Etap | Nazwa | Status | Czas | Priorytet |
|------|-------|---------|------|-----------|
| [10](ETAP_10_Dostawy.md) | System Dostaw i Kontenerów | ❌ NIE ROZPOCZĘTY | 50h | 🟡 WYSOKI |
| [11](ETAP_11_Dopasowania.md) | System Dopasowań i Wariantów | ❌ NIE ROZPOCZĘTY | 40h | 🟢 ŚREDNI |
| [12](ETAP_12_UI_Deploy.md) | UI/UX, Testy i Deploy Produkcyjny | ❌ NIE ROZPOCZĘTY | 45h | 🔴 KRYTYCZNY |
| [13](ETAP_13_Sync_Panel_UX_Refactoring.md) | Sync Panel UX Refactoring | ✅ UKOŃCZONY (2025-11-18) | 68h (1h real) | 🟡 WYSOKI |

**TOTAL:** ~583 godzin (≈ 23 tygodnie przy 25h/tydzień)

---

## 👥 SYSTEM RÓL I UPRAWNIEŃ

### Hierarchia użytkowników (7 poziomów):
1. **🔴 Admin** - Pełne uprawnienia + zarządzanie użytkownikami/sklepami
2. **🟡 Menadżer** - CRUD produktów + import/export + ERP
3. **🟢 Redaktor** - Edycja opisów, zdjęć, kategorii (bez usuwania)
4. **🔵 Magazynier** - Panel dostaw + edycja kontenerów
5. **🟣 Handlowiec** - Panel zamówień + rezerwacje towarów
6. **🟠 Reklamacje** - System reklamacji + uprawnienia użytkownika
7. **⚪ Użytkownik** - Tylko odczyt i wyszukiwanie produktów

---

## 🔄 METODYKA PRACY

### Rozwój hybrydowy:
1. **Lokalne tworzenie:** Kod, testy, debugowanie
2. **Automatyczny deploy:** SSH/SFTP na serwer produkcyjny
3. **Weryfikacja online:** Testowanie na ppm.mpptrade.pl
4. **Iteracyjne poprawki:** Feedback loop

### Narzędzia deweloperskie:
- **IDE:** VS Code z rozszerzeniami Laravel
- **Wersjonowanie:** Git z tagami per etap
- **Deploy:** Skrypty PowerShell SSH/SFTP
- **Testowanie:** PHPUnit + Laravel Dusk
- **Monitoring:** Laravel Telescope + custom dashboards

---

## 📊 METRYKI PROJEKTU

### Przewidywana struktura kodu:
- **Modele:** ~45 plików Eloquent
- **Migracje:** ~60 plików migracji
- **Kontrolery:** ~35 kontrolerów
- **Livewire komponenty:** ~50 komponentów
- **Blade views:** ~80 widoków
- **API endpoints:** ~40 endpointów
- **Testy:** ~150 testów

### Główne integracje:
- **PrestaShop 8/9:** API REST
- **Baselinker:** API REST + Webhooks
- **Subiekt GT:** DLL/.NET Bridge
- **Microsoft Dynamics:** OData API
- **Google Workspace:** OAuth2 + Drive API
- **Microsoft Entra:** OAuth2 + Graph API

---

## 🎯 DEFINICJA GOTOWOŚCI (DoD)

Każdy etap uznawany jest za ukończony gdy:
- ✅ Wszystkie zadania mają status ✅
- ✅ Kod przechodzi testy jednostkowe
- ✅ Funkcjonalność działa na serwerze produkcyjnym
- ✅ Dokumentacja jest aktualna
- ✅ Raport agenta został utworzony w _AGENT_REPORTS/

---

## 📞 KONTAKT I WSPARCIE

**Deweloper:** Claude Code AI + Kamil Wiliński  
**E-mail:** wilendar@gmail.com  
**Organizacja:** MPP Trade  
**Repozytorium:** Lokalny projekt Git  
**Backup:** Google Drive + OneDrive synchronizacja  

---

## 🔍 LEGENDA STATUSÓW

- ❌ **Nie rozpoczęte** - Zadanie czeka na realizację
- 🛠️ **W trakcie** - Aktualnie trwają prace
- ✅ **Ukończone** - Zadanie zakończone i przetestowane
- ⚠️ **Zablokowane** - Czeka na rozwiązanie blokera
- 🔄 **Do przeglądu** - Wymaga weryfikacji

---

**Stworzony:** 2025-09-05 przez Claude Code AI  
**Ostatnia aktualizacja:** 2025-11-18  
**Wersja planu:** 1.0  
