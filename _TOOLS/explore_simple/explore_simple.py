#!/usr/bin/env python3
"""
Prosta eksploracja bazy danych bez zewnętrznych bibliotek
Używa tylko subprocess i standardowych bibliotek Python
"""

import subprocess
import sys
import json
from datetime import datetime

# Konfiguracja bazy
SERVER = '10.9.20.100'
DATABASE = 'MPP_TRADE'
USERNAME = 'sa'
PASSWORD = 'xzHdHT%f4BtG'

def run_sql_query(query, description=""):
    """Uruchamia zapytanie SQL przez sqlcmd"""
    print(f"\n🔍 {description}")
    print(f"SQL: {query.strip()}")
    print("-" * 50)
    
    cmd = [
        'sqlcmd',
        '-S', SERVER,
        '-d', DATABASE,
        '-U', USERNAME,
        '-P', PASSWORD,
        '-Q', query,
        '-h', '-1',  # Bez nagłówków
        '-s', '|',   # Separator |
        '-W',        # Usuń końcowe spacje
        '-b'         # Zatrzymaj na błędzie
    ]
    
    try:
        result = subprocess.run(cmd, capture_output=True, text=True, timeout=30)
        
        if result.returncode == 0:
            output_lines = result.stdout.strip().split('\n')
            valid_lines = []
            
            for line in output_lines:
                clean_line = line.strip()
                # Pomijaj puste linie i linie z informacjami systemowymi
                if clean_line and not clean_line.startswith('(') and not clean_line.startswith('Changed'):
                    valid_lines.append(clean_line)
            
            if valid_lines:
                print("✅ WYNIKI:")
                for i, line in enumerate(valid_lines[:20], 1):  # Pierwsze 20 wyników
                    print(f"{i:2d}. {line}")
                
                if len(valid_lines) > 20:
                    print(f"... i {len(valid_lines) - 20} więcej wyników")
            else:
                print("📄 Zapytanie wykonane, ale brak wyników")
                
            return valid_lines
            
        else:
            print(f"❌ BŁĄD SQL:")
            print(result.stderr)
            return []
            
    except subprocess.TimeoutExpired:
        print("⏰ TIMEOUT - zapytanie trwało zbyt długo")
        return []
    except FileNotFoundError:
        print("❌ sqlcmd nie jest zainstalowany lub niedostępny w PATH")
        return []
    except Exception as e:
        print(f"❌ BŁĄD: {e}")
        return []

def explore_database():
    """Główna funkcja eksploracji"""
    print("🔍 EKSPLORACJA BAZY DANYCH SUBIEKT GT")
    print(f"Serwer: {SERVER}")
    print(f"Baza: {DATABASE}")
    print("=" * 60)
    
    # 1. Test podstawowy
    test_result = run_sql_query("SELECT 1 as test_connection", "Test połączenia")
    
    if not test_result:
        print("\n❌ Nie można połączyć się z bazą danych!")
        print("Sprawdź:")
        print("- Czy serwer jest dostępny")
        print("- Czy dane logowania są poprawne")
        print("- Czy sqlcmd jest zainstalowany")
        return
    
    print("\n✅ Połączenie z bazą działa!")
    
    # 2. Lista wszystkich tabel
    print("\n" + "="*60)
    all_tables = run_sql_query("""
        SELECT TABLE_NAME, TABLE_SCHEMA, TABLE_TYPE
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_TYPE = 'BASE TABLE'
        ORDER BY TABLE_NAME
    """, "WSZYSTKIE TABELE W BAZIE")
    
    # 3. Tabele zawierające 'dokument'
    print("\n" + "="*60)
    doc_tables = run_sql_query("""
        SELECT TABLE_NAME, TABLE_SCHEMA
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_TYPE = 'BASE TABLE' 
        AND TABLE_NAME LIKE '%dokument%'
        ORDER BY TABLE_NAME
    """, "TABELE Z TEKSTEM 'DOKUMENT'")
    
    # 4. Tabele zawierające kontrahent/klient
    print("\n" + "="*60)
    client_tables = run_sql_query("""
        SELECT TABLE_NAME, TABLE_SCHEMA
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_TYPE = 'BASE TABLE' 
        AND (TABLE_NAME LIKE '%kontrahent%' 
             OR TABLE_NAME LIKE '%klient%' 
             OR TABLE_NAME LIKE '%customer%')
        ORDER BY TABLE_NAME
    """, "TABELE Z KLIENTAMI/KONTRAHENTAMI")
    
    # 5. Kolumny zawierające kwoty
    print("\n" + "="*60)
    amount_columns = run_sql_query("""
        SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE COLUMN_NAME LIKE '%kwota%' 
           OR COLUMN_NAME LIKE '%wartosc%'
           OR COLUMN_NAME LIKE '%brutto%'
           OR COLUMN_NAME LIKE '%amount%'
           OR COLUMN_NAME LIKE '%value%'
        ORDER BY TABLE_NAME, COLUMN_NAME
    """, "KOLUMNY Z KWOTAMI")
    
    # 6. Kolumny z datami
    print("\n" + "="*60)
    date_columns = run_sql_query("""
        SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE COLUMN_NAME LIKE '%data%' 
           OR COLUMN_NAME LIKE '%date%'
        ORDER BY TABLE_NAME, COLUMN_NAME
    """, "KOLUMNY Z DATAMI")
    
    # 7. Kolumny z email
    print("\n" + "="*60)
    email_columns = run_sql_query("""
        SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE COLUMN_NAME LIKE '%email%' 
           OR COLUMN_NAME LIKE '%mail%'
        ORDER BY TABLE_NAME, COLUMN_NAME
    """, "KOLUMNY Z EMAIL")
    
    # 8. Jeśli znaleźliśmy tabele z dokumentami, sprawdź ich strukturę
    if doc_tables:
        print("\n" + "="*60)
        print("📋 STRUKTURA TABEL Z DOKUMENTAMI:")
        
        for table_line in doc_tables[:3]:  # Pierwsze 3 tabele
            if '|' in table_line:
                table_name = table_line.split('|')[0].strip()
                
                print(f"\n📄 TABELA: {table_name}")
                run_sql_query(f"""
                    SELECT TOP 5 COLUMN_NAME, DATA_TYPE, IS_NULLABLE, CHARACTER_MAXIMUM_LENGTH
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_NAME = '{table_name}'
                    ORDER BY ORDINAL_POSITION
                """, f"Struktura tabeli {table_name}")
                
                # Przykładowe dane z tabeli
                run_sql_query(f"""
                    SELECT TOP 3 *
                    FROM {table_name}
                """, f"Przykładowe dane z {table_name}")
    
    # 9. Sprawdź tabele z klientami
    if client_tables:
        print("\n" + "="*60)
        print("👥 STRUKTURA TABEL Z KLIENTAMI:")
        
        for table_line in client_tables[:2]:  # Pierwsze 2 tabele
            if '|' in table_line:
                table_name = table_line.split('|')[0].strip()
                
                print(f"\n👤 TABELA: {table_name}")
                run_sql_query(f"""
                    SELECT TOP 5 COLUMN_NAME, DATA_TYPE, IS_NULLABLE
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_NAME = '{table_name}'
                    ORDER BY ORDINAL_POSITION
                """, f"Struktura tabeli {table_name}")
    
    # 10. Podsumowanie
    print("\n" + "="*60)
    print("📊 PODSUMOWANIE EKSPLORACJI")
    print("="*60)
    print(f"✅ Połączenie z bazą: OK")
    print(f"📋 Tabele łącznie: {len(all_tables) if all_tables else 0}")
    print(f"📄 Tabele z dokumentami: {len(doc_tables) if doc_tables else 0}")
    print(f"👥 Tabele z klientami: {len(client_tables) if client_tables else 0}")
    print(f"💰 Kolumny z kwotami: {len(amount_columns) if amount_columns else 0}")
    print(f"📅 Kolumny z datami: {len(date_columns) if date_columns else 0}")
    print(f"📧 Kolumny z email: {len(email_columns) if email_columns else 0}")
    
    print("\n💡 NASTĘPNE KROKI:")
    if doc_tables:
        print("1. ✅ Znaleziono tabele z dokumentami - można budować zapytania")
    else:
        print("1. ❌ Brak tabel z dokumentami - sprawdź nazewnictwo")
    
    if client_tables:
        print("2. ✅ Znaleziono tabele z klientami - można łączyć dane")
    else:
        print("2. ❌ Brak tabel z klientami - sprawdź nazewnictwo")
    
    if amount_columns:
        print("3. ✅ Znaleziono kolumny z kwotami - dopasowanie możliwe")
    else:
        print("3. ❌ Brak kolumn z kwotami - sprawdź inne nazwy")
    
    print("\n🔧 REKOMENDACJE DLA APLIKACJI:")
    print("- Użyj znalezionych nazw tabel w zapytaniach SQL")
    print("- Zbuduj mapowanie kolumn na podstawie odkrytej struktury")
    print("- Przetestuj zapytania łączące dokumenty z klientami")
    
    print(f"\n🕐 Eksploracja zakończona: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")

if __name__ == "__main__":
    try:
        explore_database()
    except KeyboardInterrupt:
        print("\n\n⏹️ Eksploracja przerwana przez użytkownika")
    except Exception as e:
        print(f"\n❌ Nieoczekiwany błąd: {e}")
        sys.exit(1)