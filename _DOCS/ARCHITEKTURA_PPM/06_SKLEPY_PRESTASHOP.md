# 06. Sklepy PrestaShop

[◀ Powrót do spisu treści](README.md)

---

## 🏪 Sklepy PrestaShop - Przegląd

Zarządzanie połączeniami do wielu sklepów PrestaShop (v8.x / v9.x) z centralnego miejsca.

**Uprawnienia:**
- **Admin:** Pełny dostęp (create, edit, delete, sync)
- **Menadżer:** View only (lista + status)
- **Redaktor:** View only (lista + status)

---

## 6.1 Lista Sklepów

**Route:** `/admin/shops`
**Controller:** ShopController@index
**Middleware:** auth, role:manager+

### Tabela Sklepów

| Kolumna | Opis | Typ |
|---------|------|-----|
| Nazwa sklepu | Nazwa własna (edytowalna) | Tekstowe |
| URL + Logo | URL sklepu + miniatura logo | Link + Image |
| Wersja PrestaShop | 8.x / 9.x (auto-detect lub manual) | Badge |
| Status połączenia | Online / Offline (real-time test) | Status badge |
| Ostatnia synchronizacja | Timestamp ostatniej sync | Datetime |
| Liczba produktów | Produkty zsynchronizowane / total | Number |
| Akcje | Edit, Sync, Delete | Buttons |

### Header Actions

```
[+ Dodaj Sklep]  [🔌 Test Wszystkich Połączeń]
```

### Filtry

- **Status:** Wszystkie / Aktywne / Nieaktywne
- **Wersja:** Wszystkie / 8.x / 9.x
- **Sortowanie:** Nazwa / Data dodania / Ostatnia sync

### Bulk Operations

```
☑️ Zaznacz wszystkie (5 sklepów)

[🔄 Synchronizuj Zaznaczone]  [📤 Eksport Masowy]  [❌ Dezaktywuj]
```

**Eksport Masowy:**
- Produkty wybranych sklepów
- Modal z opcjami (zdjęcia, kategorie, cechy)

---

## 6.2 Dodaj/Edytuj Sklep

**Route:** `/admin/shops/create`, `/admin/shops/{id}/edit`
**Controller:** ShopController@create / ShopController@edit
**Middleware:** auth, role:admin

### Formularz Konfiguracji (Tabs)

#### Tab 1: Dane Połączenia

```
┌─────────────────────────────────────────────────┐
│ Nazwa sklepu *                                  │
│ [YCF Official Store                          ]  │
├─────────────────────────────────────────────────┤
│ URL sklepu *                                    │
│ [https://ycf.pl                              ]  │
│   ✅ Walidacja URL: OK                          │
├─────────────────────────────────────────────────┤
│ Klucz API *                                     │
│ [••••••••••••••••••••••••••]  [👁 Pokaż]        │
│   [🔌 Test Połączenia]                          │
├─────────────────────────────────────────────────┤
│ Wersja PrestaShop *                             │
│ ○ 8.x   ● 9.x                                   │
├─────────────────────────────────────────────────┤
│ Status                                          │
│ ☑️ Aktywny (synchronizacja włączona)            │
└─────────────────────────────────────────────────┘
```

**Walidacja:**
- URL: must be valid URL, https preferred
- API Key: required, test connection on blur
- Wersja: auto-detect if possible (API response)

#### Tab 2: Mapowania

**Grupy Cenowe (PPM → PrestaShop)**

| Grupa Cenowa PPM | PrestaShop Group ID | Mapowanie |
|------------------|---------------------|-----------|
| Detaliczna | [dropdown: 1 - General Public] | ✅ |
| Dealer Standard | [dropdown: 2 - Wholesale] | ✅ |
| Dealer Premium | [dropdown: custom] | ⚠️ Custom |

**Magazyny (PPM → PrestaShop)**

| Magazyn PPM | PrestaShop Warehouse | Domyślny |
|-------------|----------------------|----------|
| MPPTRADE | [dropdown: Main Warehouse] | ● |
| Pitbike.pl | [dropdown: Secondary] | ○ |

**Kategorie (Tree View Picker)**

```
📂 PrestaShop Categories
  ├─ 🏍️ Motocykle
  │   ├─ ☑️ Elektryczne (mapped: Pojazdy > Elektryczne)
  │   └─ ☑️ Spalinowe (mapped: Pojazdy > Spalinowe)
  └─ 🔧 Części
      └─ ☑️ Silniki (mapped: Części > Silniki)

[↻ Pobierz Kategorie z PrestaShop]
```

#### Tab 3: Dopasowania

**Wybór Marek Pojazdów dla Tego Sklepu**

```
Filtrowanie:
  Producent: [dropdown: Wszystkie / YCF / Pitbike / ...]
  Marka: [search: ________________]

Wybrane Marki (12):
  ✅ YCF Pilot 50      [❌ Usuń]
  ✅ YCF Pilot 110     [❌ Usuń]
  ✅ Pitbike 125cc     [❌ Usuń]
  ...

[+ Dodaj Markę]
```

**Funkcja "Banowania":**
- Global models dostępne dla wszystkich sklepów
- Możliwość wykluczenia konkretnych modeli per sklep

#### Tab 4: Ustawienia Synchronizacji

```
┌─────────────────────────────────────────────────┐
│ Częstotliwość Auto-Sync                         │
│ ○ Wyłączona                                     │
│ ○ Co 15 minut                                   │
│ ● Co 1 godzinę                                  │
│ ○ Co 6 godzin                                   │
│ ○ Co 24 godziny                                 │
├─────────────────────────────────────────────────┤
│ Co synchronizować?                              │
│ ☑️ Zdjęcia produktów                             │
│ ☑️ Stany magazynowe                              │
│ ☑️ Ceny (wszystkie grupy cenowe)                 │
│ ☑️ Kategorie                                     │
│ ☑️ Opisy produktów                               │
│ ☐ Cechy i dopasowania pojazdów                  │
└─────────────────────────────────────────────────┘
```

### Footer Actions

```
[💾 Zapisz i Testuj Połączenie]  [💾 Zapisz]  [❌ Anuluj]
```

---

## 6.3 Synchronizacja

**Route:** `/admin/shops/sync`
**Controller:** ShopSyncController@index
**Middleware:** auth, role:manager+

### Status Panel (Per Sklep)

```
┌────────────────────────────────────────────────────┐
│ 🏪 YCF Official Store                              │
│                                                    │
│ Ostatnia sync: 2025-10-22 10:30 (15 min temu)     │
│ Status: ✅ Success                                 │
│ Progress: ████████████████████ 100% (450/450)     │
│                                                    │
│ [🔄 Synchronizuj Teraz]  [📋 Pokaż Logi]           │
└────────────────────────────────────────────────────┘
```

**Real-time Progress Bar:**
```javascript
// Livewire wire:poll podczas synchronizacji
<div wire:poll.1s>
    <div class="progress-bar" style="width: {{ $progress }}%"></div>
    <span>{{ $current }} / {{ $total }} produktów</span>
</div>
```

### Sync Actions (Globalne)

```
[🔄 Synchronizuj Wszystkie Sklepy]
[⚡ Tylko Zmiany (Delta Sync)]
[🔁 Full Sync (Wszystko)]
```

**Delta Sync vs Full Sync:**
- **Delta:** Tylko produkty zmienione od ostatniej sync (szybsze)
- **Full:** Wszystkie produkty (wolniejsze, do recovery)

### Sync Logs (Tabela)

| Timestamp | Sklep | Typ | Status | Produkty | Czas | Szczegóły |
|-----------|-------|-----|--------|----------|------|-----------|
| 2025-10-22 10:30 | YCF | Export | ✅ Success | 450 | 2m 15s | [View] |
| 2025-10-22 09:00 | Pitbike | Import | ⚠️ Partial | 320/350 | 3m 45s | [View] [Retry] |
| 2025-10-22 08:00 | YCF | Update | ❌ Failed | 0/450 | - | [View] [Retry] |

**Filtry:**
- Data (date range)
- Sklep (multi-select)
- Status (Success / Failed / Partial)
- Typ (Export / Import / Update)

**Akcje:**
- **View:** Modal z full log (line-by-line)
- **Retry:** Ponowna próba synchronizacji (tylko failed/partial)

### Sync Log Details (Modal)

```
═══════════════════════════════════════════════
SYNC LOG: YCF Official Store
Type: Export | Status: ⚠️ Partial Success
Started: 2025-10-22 09:00:00
Finished: 2025-10-22 09:03:45
Duration: 3m 45s
═══════════════════════════════════════════════

✅ SUCCESS (320 products)
  - PROD-001 | Export OK | 2s
  - PROD-002 | Export OK | 1s
  ...

❌ ERRORS (30 products)
  - PROD-450 | Image upload failed: timeout
  - PROD-451 | Category not found: ID 999
  ...

[📥 Download Full Log (TXT)]  [🔄 Retry Failed Only]  [✖ Close]
```

---

## 6.4 Eksport Masowy (v2.0: Przeniesiono do Produkty)

**⚠️ ZMIANA v2.0:** Ta funkcja została przeniesiona do `/admin/products` jako przycisk "Eksportuj wszystko do CSV".

**Dlaczego?**
- Eksport = akcja na produktach (logicznie w Lista Produktów)
- Kontekst: jestem na liście produktów → mogę exportować
- Spójność z bulk operations

**Nowa Lokalizacja:**
- Route: `/admin/products` (Lista Produktów)
- Przycisk: "📤 Eksportuj do CSV" w header actions
- Modal: Wybór sklepów docelowych + opcje eksportu

---

## 🎨 UI/UX Patterns

### Connection Status Badge

```html
<span class="status-badge status-online">
    <span class="status-dot"></span>
    Online
</span>

<span class="status-badge status-offline">
    <span class="status-dot"></span>
    Offline (2h)
</span>
```

```css
.status-badge.status-online .status-dot {
    background: #10b981; /* green */
    animation: pulse 2s infinite;
}

.status-badge.status-offline .status-dot {
    background: #ef4444; /* red */
}
```

### Test Connection (Real-time)

```javascript
// Alpine.js + Livewire
<button
    @click="$wire.testConnection()"
    :disabled="testing"
    x-data="{ testing: false }"
>
    <span x-show="!testing">🔌 Test Połączenia</span>
    <span x-show="testing">⏳ Testowanie...</span>
</button>

<div x-show="$wire.connectionStatus === 'success'">
    ✅ Połączenie OK (PrestaShop 9.0.1)
</div>
<div x-show="$wire.connectionStatus === 'error'">
    ❌ Błąd: Invalid API Key
</div>
```

---

## 📖 Nawigacja

- **Poprzedni moduł:** [05. Dashboard](05_DASHBOARD.md)
- **Następny moduł:** [07. Produkty](07_PRODUKTY.md)
- **Powrót:** [Spis treści](README.md)
