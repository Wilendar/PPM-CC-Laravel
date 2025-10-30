# 04. Macierz Uprawnień

[◀ Powrót do spisu treści](README.md)

---

## 🔐 7-Poziomowy System Ról

### Hierarchia Uprawnień

```
┌─────────────────────────────────────┐
│  1. ADMIN                           │ ← Pełny dostęp (superuser)
├─────────────────────────────────────┤
│  2. MENADŻER                        │ ← Zarządzanie produktami + sync + import/export
├─────────────────────────────────────┤
│  3. REDAKTOR                        │ ← Edycja opisów/zdjęć (bez usuwania)
├─────────────────────────────────────┤
│  4. MAGAZYNIER                      │ ← Panel dostaw i kontenery
├─────────────────────────────────────┤
│  5. HANDLOWIEC                      │ ← Rezerwacje z kontenera (bez cen zakupu)
├─────────────────────────────────────┤
│  6. REKLAMACJE                      │ ← Panel reklamacji
├─────────────────────────────────────┤
│  7. UŻYTKOWNIK                      │ ← Odczyt + wyszukiwarka (podstawowy dostęp)
└─────────────────────────────────────┘
```

**Zasada Dziedziczenia:**
- Wyższe poziomy dziedziczą uprawnienia niższych
- Przykład: **Admin** ma wszystkie uprawnienia **Menadżera** + dodatkowe Admin-only

---

## 📋 Kompletna Macierz Uprawnień

### CORE

| STRONA / FUNKCJA | Admin | Menadżer | Redaktor | Magazynier | Handlowiec | Reklamacje | Użytkownik |
|------------------|-------|----------|----------|------------|------------|------------|------------|
| **DASHBOARD** |||||||
| Dashboard | ✅ (pełny) | ✅ (produkty+sync) | ✅ (produkty) | ✅ (dostawy) | ✅ (zamówienia) | ✅ (reklamacje) | ✅ (basic) |

**Szczegóły Dashboard per Rola:**
- **Admin:** KPI wszystkich obszarów, błędy sync, alerty systemowe, quick actions: Dodaj sklep, Import CSV, Ustawienia
- **Menadżer:** KPI produktów, sync status, magazyny, quick actions: Dodaj produkt, Import CSV, Eksport
- **Redaktor:** Ostatnie edycje, produkty bez zdjęć, quick actions: Edytuj produkt, Wyszukaj
- **Magazynier:** Dostawy, kontenery, przyjęcia, quick actions: Nowa dostawa, Przyjęcie magazynowe
- **Handlowiec:** Zamówienia, rezerwacje, quick actions: Nowe zamówienie, Rezerwuj z kontenera
- **Reklamacje:** Reklamacje pending, timeline, quick actions: Nowa reklamacja, Zamknij reklamację
- **Użytkownik:** Wyszukiwarka, ostatnie produkty, basic statistics (read-only)

---

### SKLEPY PRESTASHOP

| STRONA / FUNKCJA | Admin | Menadżer | Redaktor | Magazynier | Handlowiec | Reklamacje | Użytkownik |
|------------------|-------|----------|----------|------------|------------|------------|------------|
| Lista sklepów | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Dodaj/Edytuj sklep | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Synchronizacja | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

**Szczegóły:**
- **Admin:** Pełny dostęp (create, edit, delete, sync, test connection)
- **Menadżer:** View only (lista sklepów, status sync) - może zobaczyć które sklepy są dostępne
- **Redaktor:** View only (jak Menadżer)
- **Pozostali:** Brak dostępu (nie potrzebują widzieć konfiguracji sklepów)

---

### PRODUKTY

| STRONA / FUNKCJA | Admin | Menadżer | Redaktor | Magazynier | Handlowiec | Reklamacje | Użytkownik |
|------------------|-------|----------|----------|------------|------------|------------|------------|
| Lista produktów (odczyt) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Dodaj/Usuń produkt | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Edycja produktu | ✅ | ✅ | 🟡 (bez usuwania) | ❌ | ❌ | ❌ | ❌ |
| Import z pliku | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Historie importów | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Eksport do CSV | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Zarządzanie kategoriami | ✅ | ✅ | 🟡 (bez usuwania) | ❌ | ❌ | ❌ | ❌ |
| Wyszukiwarka | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

**Szczegóły Redaktor (🟡):**
- **Może:** Edytować opisy (krótki/długi), zdjęcia, meta SEO, notatki wewnętrzne
- **Może:** Edytować kategorie (przypisać produkty do kategorii)
- **NIE MOŻE:** Usuwać produktów, zdjęć, kategorii
- **NIE MOŻE:** Zmieniać SKU, cen, stanów magazynowych, dopasowań pojazdów

---

### CENNIK

| STRONA / FUNKCJA | Admin | Menadżer | Redaktor | Magazynier | Handlowiec | Reklamacje | Użytkownik |
|------------------|-------|----------|----------|------------|------------|------------|------------|
| Grupy cenowe | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Edycja cen | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Aktualizacja masowa | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Ceny widoczne | ✅ | ✅ | ✅ | ✅ | 🟡 (bez zakupu) | ✅ | ✅ |

**Szczegóły Handlowiec (🟡):**
- **Widzi:** Ceny detaliczne, Dealer Standard/Premium, Warsztat
- **NIE WIDZI:** Cen zakupu (Purchase Price)
- **NIE MOŻE:** Edytować cen

**Szczegóły Redaktor:**
- **Widzi:** Wszystkie grupy cenowe (read-only)
- **NIE MOŻE:** Edytować cen

---

### WARIANTY & CECHY

| STRONA / FUNKCJA | Admin | Menadżer | Redaktor | Magazynier | Handlowiec | Reklamacje | Użytkownik |
|------------------|-------|----------|----------|------------|------------|------------|------------|
| Zarządzanie wariantami | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Cechy pojazdów | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Dopasowania części | ✅ | ✅ | 🟡 (read-only) | ❌ | ❌ | ❌ | ❌ |

**Szczegóły Redaktor (🟡):**
- **Może:** Edytować warianty (atrybuty, zdjęcia)
- **Może:** Edytować cechy pojazdów (VIN, Engine No., etc.)
- **Może:** Przeglądać dopasowania części
- **NIE MOŻE:** Tworzyć/usuwać wariantów, dopasowań

---

### DOSTAWY & KONTENERY

| STRONA / FUNKCJA | Admin | Menadżer | Redaktor | Magazynier | Handlowiec | Reklamacje | Użytkownik |
|------------------|-------|----------|----------|------------|------------|------------|------------|
| Lista dostaw | ✅ | ✅ | ❌ | ✅ | 🟡 (read-only) | ❌ | ❌ |
| Szczegóły kontenera | ✅ | ✅ | ❌ | ✅ | 🟡 (read-only) | ❌ | ❌ |
| Przyjęcia magazynowe | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |
| Dokumenty odpraw | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |
| Edycja ilości | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |
| Zamknięcie dostawy | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |

**Szczegóły Magazynier:**
- **Pełny dostęp:** Dostawy, kontenery, przyjęcia, dokumenty
- **Może:** Edytować ilości, statusy, upload dokumentów, zamknąć dostawę

**Szczegóły Handlowiec (🟡):**
- **Widzi:** Listę dostaw (read-only)
- **Widzi:** Dostępność produktów w kontenerach (do rezerwacji)
- **NIE MOŻE:** Edytować, zamykać dostaw, przyjmować magazynowo

---

### ZAMÓWIENIA

| STRONA / FUNKCJA | Admin | Menadżer | Redaktor | Magazynier | Handlowiec | Reklamacje | Użytkownik |
|------------------|-------|----------|----------|------------|------------|------------|------------|
| Lista zamówień | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ |
| Nowe zamówienie | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ |
| Rezerwacje z kontenera | ✅ | ✅ | ❌ | 🟡 (read-only) | ✅ | ❌ | ❌ |
| Historia zamówień | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ |

**Szczegóły Handlowiec:**
- **Pełny dostęp:** Zamówienia, rezerwacje z kontenera
- **OGRANICZENIE:** Nie widzi cen zakupu (tylko Detaliczne/Dealer)

**Szczegóły Magazynier (🟡):**
- **Widzi:** Rezerwacje (read-only) - do pakowania/wysyłki
- **NIE MOŻE:** Tworzyć nowych rezerwacji, zamówień

---

### REKLAMACJE

| STRONA / FUNKCJA | Admin | Menadżer | Redaktor | Magazynier | Handlowiec | Reklamacje | Użytkownik |
|------------------|-------|----------|----------|------------|------------|------------|------------|
| Lista reklamacji | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |
| Nowa reklamacja | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |
| Edycja reklamacji | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |
| Zamknij reklamację | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |
| Archiwum | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |

**Szczegóły Reklamacje:**
- **Pełny dostęp:** Panel reklamacji (CRUD + timeline + attachments)

---

### RAPORTY & STATYSTYKI

| STRONA / FUNKCJA | Admin | Menadżer | Redaktor | Magazynier | Handlowiec | Reklamacje | Użytkownik |
|------------------|-------|----------|----------|------------|------------|------------|------------|
| Raporty produktowe | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Raporty finansowe | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Raporty magazynowe | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |
| Eksport raportów | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |

**Szczegóły Magazynier:**
- **Dostęp TYLKO:** Raporty magazynowe (stany, ruchy, dostawy)
- **NIE MOŻE:** Przeglądać raportów produktowych/finansowych

---

### SYSTEM (Admin Panel)

| STRONA / FUNKCJA | Admin | Menadżer | Redaktor | Magazynier | Handlowiec | Reklamacje | Użytkownik |
|------------------|-------|----------|----------|------------|------------|------------|------------|
| Ustawienia systemu | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Zarządzanie użytkownikami | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Integracje ERP | ✅ | 🟡 (sync only) | ❌ | ❌ | ❌ | ❌ | ❌ |
| Backup & Restore | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Konserwacja bazy | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Logi systemowe | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Monitoring | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| API Management | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

**Szczegóły Menadżer (🟡):**
- **Może:** Uruchomić synchronizację ERP (trigger sync)
- **Może:** Przeglądać logi synchronizacji
- **NIE MOŻE:** Konfigurować integracji ERP (API keys, credentials)

---

### PROFIL UŻYTKOWNIKA

| STRONA / FUNKCJA | Admin | Menadżer | Redaktor | Magazynier | Handlowiec | Reklamacje | Użytkownik |
|------------------|-------|----------|----------|------------|------------|------------|------------|
| Edycja profilu | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Aktywne sesje | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Historia aktywności | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Ustawienia powiadomień | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

**Wszyscy:** Pełny dostęp do własnego profilu

---

### POMOC

| STRONA / FUNKCJA | Admin | Menadżer | Redaktor | Magazynier | Handlowiec | Reklamacje | Użytkownik |
|------------------|-------|----------|----------|------------|------------|------------|------------|
| Dokumentacja | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Skróty klawiszowe | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Wsparcie techniczne | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

**Wszyscy:** Pełny dostęp do pomocy

---

## 🔑 Legenda

| Symbol | Znaczenie |
|--------|-----------|
| ✅ | Pełny dostęp (CRUD: Create, Read, Update, Delete) |
| 🟡 | Ograniczony dostęp (szczegóły w opisie sekcji) |
| ❌ | Brak dostępu (hidden from menu + route middleware block) |

---

## 🛡️ Implementacja Middleware

### Role Middleware

```php
// app/Http/Middleware/CheckRole.php

public function handle($request, Closure $next, ...$roles)
{
    $user = $request->user();

    if (!$user) {
        return redirect('/login');
    }

    // Hierarchia ról (Admin ma wszystkie uprawnienia)
    $roleHierarchy = [
        'admin' => ['admin', 'manager', 'editor', 'magazynier', 'handlowiec', 'reklamacje', 'user'],
        'manager' => ['manager', 'editor', 'user'],
        'editor' => ['editor', 'user'],
        'magazynier' => ['magazynier', 'user'],
        'handlowiec' => ['handlowiec', 'user'],
        'reklamacje' => ['reklamacje', 'user'],
        'user' => ['user'],
    ];

    $userRole = $user->role;
    $allowedRoles = $roleHierarchy[$userRole] ?? ['user'];

    foreach ($roles as $role) {
        if (in_array($role, $allowedRoles)) {
            return $next($request);
        }
    }

    abort(403, 'Unauthorized access.');
}
```

### Usage w Routes

```php
// Admin only
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/users', [UserController::class, 'index']);
});

// Menadżer lub wyżej
Route::middleware(['auth', 'role:manager'])->group(function () {
    Route::get('/admin/products/create', [ProductController::class, 'create']);
});

// Wszyscy zalogowani
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/products', [ProductController::class, 'index']);
});
```

---

## 📖 Nawigacja

- **Poprzedni moduł:** [03. Routing Table](03_ROUTING_TABLE.md)
- **Następny moduł:** [05. Dashboard](05_DASHBOARD.md)
- **Powrót:** [Spis treści](README.md)
