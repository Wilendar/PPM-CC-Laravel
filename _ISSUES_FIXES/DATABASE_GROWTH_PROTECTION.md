# DATABASE GROWTH PROTECTION

**Data incydentu:** 2025-01-19
**Severity:** CRITICAL
**Czas naprawy:** ~2h

---

## Symptomy

1. Tabele w bazie danych rosly do gigabajtow bez wiedzy administratora
2. Baza danych zajmowala ponad 120 GB miejsca na dysku
3. Glowne problematyczne tabele:
   - `telescope_entries`: **43 GB** (~866k rekordow)
   - `price_history`: **74 GB** (~222k rekordow)
   - `telescope_entries_tags`: **41 MB** (~434k rekordow)

## Przyczyna

1. **BRAK scheduled task dla `telescope:prune`** - Telescope byl wlaczony przez dlugi czas bez czyszczenia
2. **BRAK wywolania `PriceHistory::cleanOldEntries()`** - metoda istniala ale nie byla schedulowana
3. **Ogromne JSON blobs w price_history** - srednia 335 KB/rekord (old_values, new_values)
4. **Brak monitoringu rozmiaru tabel** - problem narastal niewidocznie

## Rozwiazanie

### 1. Natychmiastowe czyszczenie (2025-01-19)

```bash
# Telescope - usunieto 2,019,028 rekordow
php artisan telescope:prune --hours=1

# Price history - TRUNCATE (uzytkownik zdecydowal o usunieciu historii)
TRUNCATE TABLE price_history;
```

**Wynik:** ~117 GB -> ~8 MB

### 2. Nowe artisan commands

| Command | Plik | Opis |
|---------|------|------|
| `db:health-check` | `app/Console/Commands/DatabaseHealthCheck.php` | Monitoring rozmiaru tabel z alertami email |
| `logs:cleanup` | `app/Console/Commands/CleanupLogTables.php` | Uniwersalne czyszczenie tabel logowych |
| `price-history:cleanup` | `app/Console/Commands/CleanupPriceHistory.php` | Czyszczenie historii cen |

### 3. Konfiguracja retention policies

Plik: `config/database-cleanup.php`

| Tabela | Retention | Opis |
|--------|-----------|------|
| telescope_entries | 2 dni | Debugging data |
| price_history | 90 dni | Audit trail |
| sync_jobs | 30 dni | Job tracking |
| sync_logs | 14 dni | Sync details |
| integration_logs | 30 dni | ERP logs |
| job_progress | 7 dni | Queue progress |
| failed_jobs | 30 dni | Failed jobs |
| notifications | 90 dni | User notifications |

### 4. Scheduled tasks

Plik: `routes/console.php`

| Task | Czas | Opis |
|------|------|------|
| `telescope:prune --hours=48` | 03:00 | Telescope cleanup |
| `price-history:cleanup --days=90` | 03:30 | Price history cleanup |
| `logs:cleanup` | 04:00 | Other log tables cleanup |
| `db:health-check --alert` | 06:00 | Daily monitoring + alerts |

### 5. Progi alertow (thresholds)

| Tabela | Warning | Critical |
|--------|---------|----------|
| telescope_entries | 100 MB | 500 MB |
| price_history | 500 MB | 2 GB |
| sync_jobs | 50 MB | 200 MB |
| integration_logs | 50 MB | 200 MB |

## Weryfikacja

```bash
# Sprawdz rozmiary tabel
php artisan db:health-check

# Dry-run czyszczenia
php artisan logs:cleanup --dry-run

# Sprawdz scheduled tasks
php artisan schedule:list
```

## Zapobieganie

1. **Scheduler MUSI dzialac** - CRON na produkcji:
   ```
   * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
   ```

2. **Codzienne alerty** - `db:health-check --alert` wysyla email przy przekroczeniu progow

3. **Nie dodawaj nowych tabel logowych bez retention policy** - dodaj do `config/database-cleanup.php`

4. **Monitoruj rozmiar bazy** - regularnie sprawdzaj `db:health-check`

## Pliki zmienione/utworzone

- `app/Console/Commands/DatabaseHealthCheck.php` (nowy)
- `app/Console/Commands/CleanupLogTables.php` (nowy)
- `app/Console/Commands/CleanupPriceHistory.php` (nowy)
- `config/database-cleanup.php` (nowy)
- `routes/console.php` (zaktualizowany)

## Uwagi

- **Telescope jest wylaczony w produkcji** (`TELESCOPE_ENABLED=false`) - ale trzeba czyscic stare dane
- **price_history moze miec setki KB per rekord** - ogromne JSON blobs z old_values/new_values
- **MySQL nie zwalnia miejsca po DELETE** - wymaga OPTIMIZE TABLE lub TRUNCATE

---

**Autor:** Claude Code Agent
**Data dokumentu:** 2025-01-19
