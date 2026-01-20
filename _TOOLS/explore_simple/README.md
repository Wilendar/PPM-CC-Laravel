# 🔧 Explore Simple

**Pochodzenie**: Projekt P24 Matcher v1.0.0  
**Wersja**: 1.0.0  
**Data utworzenia**: 2025-07-01

## Opis

Uproszczona wersja eksploracji bazy danych używająca tylko standardowych bibliotek Python i subprocess. Nie wymaga pandas ani pyodbc - używa bezpośrednio sqlcmd.

## Funkcje

- ✅ **Bez zewnętrznych zależności** (tylko standardowe biblioteki Python)
- ✅ **Eksploracja przez subprocess + sqlcmd**
- ✅ **Bezpieczne zapytania z timeout**
- ✅ **Szczegółowe raportowanie błędów**
- ✅ **Formatowane wyświetlanie wyników**
- ✅ **Analiza struktury tabel i kolumn**

## Użycie

```bash
cd /mnt/d/OneDrive\ -\ MPP\ TRADE/Skrypty/Narzędzia_AI/explore_simple/
python explore_simple.py
```

## Kluczowe zalety

### 🚀 **Minimalne wymagania**
- Tylko Python + sqlcmd
- Brak potrzeby instalacji pip packages
- Działa wszędzie gdzie jest sqlcmd

### 🛡️ **Bezpieczeństwo**
- Timeout 30 sekund na zapytanie
- Obsługa błędów dla każdego zapytania
- Walidacja wyników

### 📊 **Kompletna analiza**
- Wszystkie tabele w bazie
- Tabele z dokumentami
- Tabele z klientami/kontrahentami
- Kolumny z kwotami, datami, emailami
- Struktura wybranych tabel
- Przykładowe dane

## Konfiguracja

```python
# Ustawienia połączenia
SERVER = '10.9.20.100'
DATABASE = 'MPP_TRADE'
USERNAME = 'sa'
PASSWORD = 'xzHdHT%f4BtG'
```

## Wymagania systemowe

- **Python 3.6+** (tylko standardowe biblioteki)
- **sqlcmd** zainstalowany i dostępny w PATH
- **Dostęp do SQL Server** (firewall, uprawnienia)

## Instalacja sqlcmd

### Ubuntu/WSL:
```bash
sudo apt-get install mssql-tools
```

### Windows:
Sqlcmd jest częścią SQL Server Management Studio lub SQL Server Command Line Utilities.

## Przykładowe wyjście

```
🔍 EKSPLORACJA BAZY DANYCH SUBIEKT GT
Serwer: 10.9.20.100
Baza: MPP_TRADE
============================================================

🔍 Test połączenia
Query: SELECT 1 as test_connection
--------------------------------------------------
✅ WYNIKI:
 1. 1

🔍 WSZYSTKIE TABELE W BAZIE
Query: SELECT TABLE_NAME, TABLE_SCHEMA, TABLE_TYPE...
--------------------------------------------------
✅ WYNIKI:
 1. dok__Dokument|dbo|BASE TABLE
 2. kh__Kontrahent|dbo|BASE TABLE
```

## Kiedy używać

1. **Środowiska z ograniczeniami** - gdy nie można instalować pip packages
2. **Szybka diagnoza** - gdy potrzebujesz szybko sprawdzić strukturę bazy
3. **Problemy z pyodbc** - gdy sterowniki ODBC nie działają poprawnie
4. **Środowiska produkcyjne** - minimalizacja zależności

## Rozwiązywanie problemów

### Problem: sqlcmd nie znaleziony
**Rozwiązanie**: 
```bash
# Ubuntu/WSL
sudo apt-get install mssql-tools

# Dodaj do PATH
export PATH="$PATH:/opt/mssql-tools/bin"
```

### Problem: Timeout zapytań
**Rozwiązanie**: Zwiększ timeout w funkcji run_sql_query()

### Problem: Błędy połączenia
**Rozwiązanie**: Sprawdź dostępność serwera i firewall

## Przykład użycia w skryptach

```python
from explore_simple import run_sql_query

# Użyj funkcji w własnym kodzie
tables = run_sql_query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES")
for table in tables:
    print(f"Tabela: {table}")
```

## Historia zmian

### v1.0.0 (2025-07-01)
- Pierwsze wydanie z projektu P24 Matcher
- Kompletna funkcjonalność bez zewnętrznych zależności
- Wsparcie dla timeout i obsługi błędów