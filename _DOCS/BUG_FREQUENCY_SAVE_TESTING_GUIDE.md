# INSTRUKCJA TESTOWANIA: Frequency Save Fix

**Bug**: Po zapisie konfiguracji synchronizacji, wartość `autoSyncFrequency` wraca do poprzedniej wartości

**Fix**: Session-based guard zapobiega nadpisywaniu przez `mount()`

**Status**: ✅ Deployed na produkcję - gotowe do testowania

---

## 🧪 SCENARIUSZE TESTOWE

### TEST 1: Basic Save & Persist

**Kroki**:
1. Otwórz: https://ppm.mpptrade.pl/admin/shops/sync
2. Zaloguj się: `admin@mpptrade.pl / Admin123!MPP`
3. Rozwiń sekcję **"Konfiguracja synchronizacji"**
4. Znajdź pole **"Częstotliwość automatycznej synchronizacji"**
5. Zmień wartość z **"hourly"** na **"daily"**
6. Kliknij **"Zapisz konfigurację"**

**Oczekiwany rezultat**:
- ✅ Flash message: "Konfiguracja synchronizacji została zapisana pomyślnie!"
- ✅ Pole pozostaje na wartości **"daily"** (NIE wraca do "hourly")
- ✅ Strona nie wymaga odświeżenia - zmiana widoczna od razu

---

### TEST 2: Page Refresh Persistence

**Kroki** (kontynuacja TEST 1):
1. Po zapisie naciśnij **F5** (odśwież stronę)
2. Rozwiń ponownie sekcję "Konfiguracja synchronizacji"
3. Sprawdź pole "Częstotliwość automatycznej synchronizacji"

**Oczekiwany rezultat**:
- ✅ Wartość nadal **"daily"** (załadowana z bazy danych)
- ✅ NIE wraca do "hourly"

---

### TEST 3: Multiple Changes

**Kroki**:
1. Zmień frequency: **"daily"** → **"weekly"**
2. Kliknij "Zapisz konfigurację"
3. Sprawdź czy pole pozostaje **"weekly"**
4. Zmień z powrotem: **"weekly"** → **"hourly"**
5. Kliknij "Zapisz konfigurację"
6. Sprawdź czy pole pozostaje **"hourly"**

**Oczekiwany rezultat**:
- ✅ Każda zmiana zapisuje się poprawnie
- ✅ Żadna wartość nie jest nadpisywana po zapisie

---

### TEST 4: Other Fields Interaction

**Kroki**:
1. Zmień frequency na **"daily"**
2. Zmień również **"Rozmiar paczki"** (np. z 10 na 20)
3. Zmień **"Limit czasu (sekundy)"** (np. z 300 na 600)
4. Kliknij "Zapisz konfigurację"

**Oczekiwany rezultat**:
- ✅ Wszystkie pola zachowują nowe wartości
- ✅ Frequency pozostaje "daily"
- ✅ Batch size pozostaje 20
- ✅ Timeout pozostaje 600

---

### TEST 5: Error Handling

**Kroki**:
1. Zmień frequency na **"daily"**
2. Ustaw **nieprawidłową wartość** w innym polu (np. batch size = -1)
3. Kliknij "Zapisz konfigurację"

**Oczekiwany rezultat**:
- ✅ Błąd walidacji wyświetlony
- ✅ Frequency pozostaje na wartości przed zapisem (nie nadpisana)
- ✅ Po poprawieniu błędu i zapisie - frequency zapisuje się poprawnie

---

## 🔍 WERYFIKACJA LOGÓW

**Jeśli masz dostęp SSH**:

```bash
# Połącz się z serwerem
ssh -p 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" \
    host379076@host379076.hostido.net.pl

# Sprawdź logi
cd domains/ppm.mpptrade.pl/public_html
tail -n 100 storage/logs/laravel.log | grep -A2 "sync_config_just_saved"
```

**Oczekiwane logi po zapisie**:
```
[2025-11-13 14:30:00] local.DEBUG: Skipping config reload - user just saved configuration
```

**NIE POWINNO BYĆ** (przed fixem):
```
[2025-11-13 14:30:00] local.DEBUG: loadSyncConfigurationFromDatabase() CALLED {"autoSyncFrequency_BEFORE":"daily"}
[2025-11-13 14:30:00] local.DEBUG: Frequency setting from DB {"value":"hourly",...}
[2025-11-13 14:30:00] local.DEBUG: Mapped setting to property {"key":"sync.schedule.frequency","value":"hourly"}
```

---

## 📊 WERYFIKACJA BAZY DANYCH

**Opcja 1: SSH + MySQL**:
```bash
# Połącz się z bazą
mysql -u host379076_ppm -p host379076_ppm

# Sprawdź frequency
SELECT key, value, updated_at
FROM system_settings
WHERE key = 'sync.schedule.frequency';
```

**Oczekiwany rezultat**:
```
+---------------------------+-------+---------------------+
| key                       | value | updated_at          |
+---------------------------+-------+---------------------+
| sync.schedule.frequency   | daily | 2025-11-13 14:30:00 |
+---------------------------+-------+---------------------+
```

**Opcja 2: PHP Script** (jeśli brak dostępu MySQL):
```bash
# Przygotowany skrypt
cd domains/ppm.mpptrade.pl/public_html
php _TEMP/check_freq_value.php
```

---

## ❌ KNOWN ISSUES (przed fixem)

**Symptom**: Po zapisie frequency wraca do poprzedniej wartości

**Root Cause**: Livewire wywołuje `mount()` → `loadSyncConfigurationFromDatabase()` po zapisie, co nadpisuje property

**Fix Applied**: Session guard blokuje reload jeśli user właśnie zapisał

---

## ✅ SUCCESS CRITERIA

**Fix działa poprawnie jeśli**:
- ✅ Zmiana frequency zapisuje się i persists po re-render
- ✅ Odświeżenie strony (F5) nie resetuje wartości
- ✅ Wielokrotne zmiany działają poprawnie
- ✅ Log pokazuje "Skipping config reload" po zapisie
- ✅ Baza danych zawiera aktualną wartość

---

## 🐛 RAPORTOWANIE BŁĘDÓW

**Jeśli fix NIE działa**:

1. **Sprawdź logi** (czy pokazuje "Skipping config reload"?)
2. **Sprawdź bazę danych** (czy wartość została zapisana?)
3. **Sprawdź cache** (czy został wyczyszczony po deployment?)
4. **Screenshot** strony z błędem
5. **Wyślij info** z powyższych kroków

---

## 📝 CLEANUP PLAN

**Po potwierdzeniu że działa idealnie**:

Usuń debug logi (zostaw tylko info/warning/error):
- `Log::debug('loadSyncConfigurationFromDatabase() CALLED', ...)`
- `Log::debug('saveSyncConfiguration CALLED', ...)`
- `Log::debug('BEFORE updateOrCreate', ...)`
- `Log::debug('AFTER updateOrCreate - verify', ...)`
- `Log::debug('Frequency setting from DB', ...)`
- `Log::debug('Mapped setting to property', ...)`

**ZOSTAW**:
- `Log::debug('Skipping config reload - user just saved configuration')` - przydatne dla troubleshooting
- `Log::info('Sync configuration saved to database', ...)` - business operation
- `Log::error(...)` - error handling

---

**Created**: 2025-11-13 14:35
**Agent**: livewire_specialist
**Related Report**: `_AGENT_REPORTS/livewire_specialist_frequency_mount_override_fix_2025-11-13_REPORT.md`
