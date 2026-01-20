#!/usr/bin/env python3
"""
Wyszukuje tabele zawierajace nazwy poziomow cenowych w bazie Subiekt GT
Uzywa pyodbc zamiast sqlcmd
"""

import pyodbc

# Konfiguracja bazy
SERVER = '10.9.20.100'
DATABASE = 'MPP_TRADE'
USERNAME = 'sa'
PASSWORD = 'xzHdHT%f4BtG'

# Wartosci do wyszukania (nazwy poziomow cenowych z Subiekta)
SEARCH_VALUES = ['HuHa', 'Warsztat Premium', 'Detaliczna', 'MRF-MPP']

def get_connection():
    """Tworzy polaczenie z baza"""
    conn_str = f'DRIVER={{ODBC Driver 17 for SQL Server}};SERVER={SERVER};DATABASE={DATABASE};UID={USERNAME};PWD={PASSWORD}'
    return pyodbc.connect(conn_str)

def search_all_tables():
    """Przeszukuje wszystkie tabele w poszukiwaniu nazw poziomow cenowych"""

    print("=" * 70)
    print("WYSZUKIWANIE NAZW POZIOMOW CENOWYCH W BAZIE SUBIEKT GT")
    print(f"Baza: {DATABASE} na {SERVER}")
    print("=" * 70)

    try:
        conn = get_connection()
        cursor = conn.cursor()
        print("\n1. Polaczenie OK")
    except Exception as e:
        print(f"\nBLAD POLACZENIA: {e}")
        return

    # Pobierz tabele z kolumnami tekstowymi
    print("\n2. Pobieranie struktury bazy...")

    cursor.execute("""
        SELECT t.TABLE_NAME, c.COLUMN_NAME
        FROM INFORMATION_SCHEMA.TABLES t
        JOIN INFORMATION_SCHEMA.COLUMNS c ON t.TABLE_NAME = c.TABLE_NAME
        WHERE t.TABLE_TYPE = 'BASE TABLE'
        AND c.DATA_TYPE IN ('varchar', 'nvarchar', 'char', 'nchar', 'text', 'ntext')
        ORDER BY t.TABLE_NAME, c.COLUMN_NAME
    """)

    tables = {}
    for row in cursor.fetchall():
        table, column = row
        if table not in tables:
            tables[table] = []
        tables[table].append(column)

    print(f"   Znaleziono {len(tables)} tabel z kolumnami tekstowymi")

    # Szukaj wartosci
    print("\n3. Wyszukiwanie wartosci...")
    found_results = []

    for search_value in SEARCH_VALUES:
        print(f"\n   Szukam: '{search_value}'")
        safe_value = search_value.replace("'", "''")

        table_count = 0
        for table_name, columns in tables.items():
            table_count += 1

            if table_count % 50 == 0:
                print(f"      Przeszukano {table_count}/{len(tables)} tabel...")

            for column_name in columns:
                try:
                    query = f"""
                        SELECT TOP 1 1
                        FROM [{table_name}]
                        WHERE [{column_name}] LIKE '%{safe_value}%'
                    """
                    cursor.execute(query)
                    if cursor.fetchone():
                        found_results.append({
                            'search_value': search_value,
                            'table': table_name,
                            'column': column_name
                        })
                        print(f"   >>> ZNALEZIONO: {table_name}.{column_name}")
                except:
                    pass  # Ignoruj bledy pojedynczych zapytan

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

        # Pokaz najbardziej prawdopodobna tabele
        print("\n" + "-" * 50)
        most_likely = max(tables_found.items(), key=lambda x: len(x[1]))
        print(f"NAJBARDZIEJ PRAWDOPODOBNA TABELA: {most_likely[0]}")
        print(f"(Znaleziono {len(most_likely[1])} z {len(SEARCH_VALUES)} szukanych wartosci)")

        # Pokaz strukture tej tabeli
        print(f"\nStruktura tabeli {most_likely[0]}:")
        cursor.execute(f"""
            SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = '{most_likely[0]}'
            ORDER BY ORDINAL_POSITION
        """)
        for row in cursor.fetchall():
            print(f"  {row[0]} ({row[1]}, nullable: {row[2]})")

        # Pokaz przykladowe dane
        print(f"\nPrzykladowe dane z {most_likely[0]} (TOP 15):")
        cursor.execute(f"SELECT TOP 15 * FROM [{most_likely[0]}]")
        columns = [desc[0] for desc in cursor.description]
        print(f"  Kolumny: {columns}")
        print()
        for row in cursor.fetchall():
            print(f"  {row}")

    else:
        print("\nNie znaleziono zadnych dopasowania.")

    conn.close()
    return found_results

if __name__ == "__main__":
    search_all_tables()
