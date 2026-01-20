#!/usr/bin/env python3
"""
Wyszukuje tabele zawierajace nazwy poziomow cenowych w bazie Subiekt GT
Szuka: HuHa, Warsztat, Premium, Standard, Detaliczna, MRF-MPP
"""

import subprocess
import json

# Konfiguracja bazy (z scan_subiekt.py)
SERVER = '10.9.20.100'
DATABASE = 'MPP_TRADE'
USERNAME = 'sa'
PASSWORD = 'xzHdHT%f4BtG'

# Wartosci do wyszukania (nazwy poziomow cenowych)
SEARCH_VALUES = ['HuHa', 'Warsztat Premium', 'Szkola-Komis-Drop', 'MRF-MPP']

def run_sql_query(query, timeout=60):
    """Uruchamia zapytanie SQL przez sqlcmd"""
    cmd = [
        'sqlcmd',
        '-S', SERVER,
        '-d', DATABASE,
        '-U', USERNAME,
        '-P', PASSWORD,
        '-Q', query,
        '-h', '-1',
        '-s', '|',
        '-W',
        '-b'
    ]

    try:
        result = subprocess.run(cmd, capture_output=True, text=True, timeout=timeout)

        if result.returncode == 0:
            output_lines = result.stdout.strip().split('\n')
            valid_lines = []

            for line in output_lines:
                clean_line = line.strip()
                if clean_line and not clean_line.startswith('(') and not clean_line.startswith('Changed'):
                    valid_lines.append(clean_line)

            return valid_lines
        else:
            print(f"SQL Error: {result.stderr}")
            return []

    except subprocess.TimeoutExpired:
        print("Query timeout")
        return []
    except FileNotFoundError:
        print("sqlcmd not found - install SQL Server Command Line Utilities")
        return []
    except Exception as e:
        print(f"Error: {e}")
        return []

def get_text_columns():
    """Pobiera liste tabel z kolumnami tekstowymi"""
    print("Pobieranie listy tabel z kolumnami tekstowymi...")

    query = """
        SELECT
            t.TABLE_NAME,
            c.COLUMN_NAME
        FROM INFORMATION_SCHEMA.TABLES t
        JOIN INFORMATION_SCHEMA.COLUMNS c ON t.TABLE_NAME = c.TABLE_NAME
        WHERE t.TABLE_TYPE = 'BASE TABLE'
        AND c.DATA_TYPE IN ('varchar', 'nvarchar', 'char', 'nchar', 'text', 'ntext')
        ORDER BY t.TABLE_NAME, c.COLUMN_NAME
    """

    results = run_sql_query(query)

    tables = {}
    for line in results:
        if '|' in line:
            parts = line.split('|')
            if len(parts) >= 2:
                table = parts[0].strip()
                column = parts[1].strip()
                if table not in tables:
                    tables[table] = []
                tables[table].append(column)

    return tables

def search_value_in_table(table_name, column_name, search_value):
    """Szuka wartosci w konkretnej kolumnie tabeli"""
    # Escape apostrofow
    safe_value = search_value.replace("'", "''")

    query = f"""
        SELECT TOP 1 '{table_name}' as tbl, '{column_name}' as col, [{column_name}] as val
        FROM [{table_name}]
        WHERE [{column_name}] LIKE '%{safe_value}%'
    """

    results = run_sql_query(query, timeout=10)
    return len(results) > 0

def search_all_tables():
    """Przeszukuje wszystkie tabele w poszukiwaniu nazw poziomow cenowych"""

    print("=" * 70)
    print("WYSZUKIWANIE NAZW POZIOMOW CENOWYCH W BAZIE SUBIEKT GT")
    print(f"Baza: {DATABASE} na {SERVER}")
    print("=" * 70)

    # Test polaczenia
    print("\n1. Testowanie polaczenia...")
    test = run_sql_query("SELECT 1 as test")
    if not test:
        print("BLAD: Nie mozna polaczyc z baza!")
        return
    print("   OK - Polaczenie dziala")

    # Pobierz tabele z kolumnami tekstowymi
    print("\n2. Pobieranie struktury bazy...")
    tables = get_text_columns()
    print(f"   Znaleziono {len(tables)} tabel z kolumnami tekstowymi")

    # Szukaj wartosci
    print("\n3. Wyszukiwanie wartosci...")

    found_results = []

    for search_value in SEARCH_VALUES:
        print(f"\n   Szukam: '{search_value}'")

        table_count = 0
        for table_name, columns in tables.items():
            table_count += 1

            if table_count % 50 == 0:
                print(f"      Przeszukano {table_count}/{len(tables)} tabel...")

            for column_name in columns:
                try:
                    if search_value_in_table(table_name, column_name, search_value):
                        found_results.append({
                            'search_value': search_value,
                            'table': table_name,
                            'column': column_name
                        })
                        print(f"   >>> ZNALEZIONO: {table_name}.{column_name}")
                except:
                    pass  # Ignoruj bledy pojedynczych tabel

    # Podsumowanie
    print("\n" + "=" * 70)
    print("PODSUMOWANIE")
    print("=" * 70)

    if found_results:
        print(f"\nZnaleziono {len(found_results)} dopasowania:\n")

        # Grupuj po tabeli
        tables_found = {}
        for r in found_results:
            t = r['table']
            if t not in tables_found:
                tables_found[t] = []
            tables_found[t].append(r)

        for table, matches in tables_found.items():
            print(f"\n  TABELA: {table}")
            for m in matches:
                print(f"    - Kolumna: {m['column']}, Wartosc: '{m['search_value']}'")

        # Pokaz najbardziej prawdopodobna tabele (najwiecej dopasowania)
        print("\n" + "-" * 50)
        most_likely = max(tables_found.items(), key=lambda x: len(x[1]))
        print(f"NAJBARDZIEJ PRAWDOPODOBNA TABELA: {most_likely[0]}")
        print(f"(Znaleziono {len(most_likely[1])} z {len(SEARCH_VALUES)} szukanych wartosci)")

        # Pokaz strukture tej tabeli
        print(f"\nStruktura tabeli {most_likely[0]}:")
        struct_query = f"""
            SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = '{most_likely[0]}'
            ORDER BY ORDINAL_POSITION
        """
        struct_results = run_sql_query(struct_query)
        for line in struct_results:
            print(f"  {line}")

        # Pokaz przykladowe dane
        print(f"\nPrzykladowe dane z {most_likely[0]}:")
        data_query = f"SELECT TOP 15 * FROM [{most_likely[0]}]"
        data_results = run_sql_query(data_query)
        for line in data_results[:20]:
            print(f"  {line}")

    else:
        print("\nNie znaleziono zadnych dopasowania.")
        print("Sprawdz czy wartosci sa poprawne lub sprobuj innych wartosci.")

    return found_results

if __name__ == "__main__":
    search_all_tables()
