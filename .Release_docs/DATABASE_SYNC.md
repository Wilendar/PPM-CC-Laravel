# PPM - Synchronizacja Bazy Danych

## 1. Przeglad

Skrypt `sync-db.ps1` synchronizuje baze produkcyjna (`host379076_ppm`) na lokalna (`ppm_local`).

Lokalizacja: `.Release_docs/scripts/sync-db.ps1`

## 2. Tryby pracy

### Full (domyslny)
```powershell
.\.Release_docs\scripts\sync-db.ps1 -Full
```
Pelny dump z routines i triggers. Ignoruje: telescope_entries, sessions, cache, cache_locks.

### Schema Only
```powershell
.\.Release_docs\scripts\sync-db.ps1 -SchemaOnly
```
Tylko struktura tabel (bez danych). Szybki, do testowania migracji.

### Exclude Large
```powershell
.\.Release_docs\scripts\sync-db.ps1 -ExcludeLarge
```
Jak Full, ale dodatkowo ignoruje duze tabele: `price_history`, `audit_logs`.
Zalecane dla codziennej pracy - znacznie szybsze.

### Single Table
```powershell
.\.Release_docs\scripts\sync-db.ps1 -Table products
```
Synchronizuje tylko wskazana tabele.

## 3. Parametry dodatkowe

| Parametr | Opis |
|----------|------|
| `-Verbose` | Szczegolowe logi operacji |

## 4. Jak to dziala

```
1. SSH (plink) na serwer produkcyjny
2. mysqldump --single-transaction --routines --triggers
3. gzip na serwerze (kompresja)
4. pscp download na lokalna maszyne
5. Dekompresja (GZipStream lub 7-Zip)
6. mysql import do ppm_local
7. Cleanup plikow tymczasowych (serwer + local)
```

**WAZNE:** Dump wykonywany jest via SSH, bo baza produkcyjna jest na `localhost` serwera (nie jest dostepna z zewnatrz na porcie 3306).

## 5. Ignorowane tabele

### Zawsze ignorowane
| Tabela | Powod |
|--------|-------|
| telescope_entries | Debug data, bardzo duza |
| telescope_entries_tags | Powiazana z telescope |
| telescope_monitoring | Powiazana z telescope |
| sessions | Sesje uzytkownikow - bezuzyteczne lokalnie |
| cache | Cache runtime |
| cache_locks | Cache locks |

### Ignorowane z -ExcludeLarge
| Tabela | Powod |
|--------|-------|
| price_history | Moze byc bardzo duza (historyczne ceny) |
| audit_logs | Logi audytu - duze |

## 6. Restore z dumpa

Jesli masz istniejacy plik dumpa:
```powershell
# Skompresowany (.gz)
# Windows - uzyj 7-Zip lub PowerShell:
$stream = [System.IO.File]::OpenRead("dump.sql.gz")
$gzip = New-Object System.IO.Compression.GZipStream($stream, [System.IO.Compression.CompressionMode]::Decompress)
# ... lub po prostu:
7z x dump.sql.gz

# Import
mysql -u root ppm_local < dump.sql
```

## 7. Strategia migracji

### Kiedy sync (pelny dump)
- Nowy developer - pierwszy setup
- Duze zmiany w strukturze bazy na produkcji
- Potrzeba aktualnych danych do testow
- Po dluzszej przerwie w pracy

### Kiedy migrate
- Codzienne zmiany w kodzie
- Tworzenie nowych migracji
- Testowanie migracji przed deployem

### Zalecany workflow
```
1. Pierwszy raz: sync-db.ps1 -ExcludeLarge
2. Codziennie: php artisan migrate
3. Co tydzien: sync-db.ps1 -ExcludeLarge (odswiezenie danych)
4. Przed release: sync-db.ps1 -Full (pelna weryfikacja)
```

## 8. Bezpieczenstwo

- Dump zawiera REALNE dane produkcyjne (adresy, zamowienia)
- NIE commituj dumpow do repo
- NIE przechowuj dumpow w folderach synchronizowanych do chmury
- Po zakonczeniu pracy: usun lokalne pliki .sql / .sql.gz
- Pliki tymczasowe sa automatycznie czyszczone przez skrypt

## 9. Troubleshooting

### "plink: command not found"
Zainstaluj PuTTY lub dodaj sciezke do PuTTY do PATH.

### "mysql: command not found"
Dodaj sciezke XAMPP do PATH: `C:\xampp\mysql\bin\`

### Timeout przy duzym dumpie
Uzyj `-ExcludeLarge` aby pominac duze tabele.

### "Access denied" na serwerze
Sprawdz dane w `hostido-config.ps1` - haslo DB, user, nazwa bazy.
