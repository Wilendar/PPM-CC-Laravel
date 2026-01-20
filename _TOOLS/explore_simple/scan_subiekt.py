#!/usr/bin/env python3
"""
Skaner struktury bazy Subiekt GT - bez emoji dla Windows
Zapisuje wyniki do pliku JSON
"""

import subprocess
import json
from datetime import datetime

# Konfiguracja bazy
SERVER = '10.9.20.100'
DATABASE = 'MPP_TRADE'
USERNAME = 'sa'
PASSWORD = 'xzHdHT%f4BtG'

OUTPUT_FILE = '../../_DOCS/SUBIEKT_GT_DATABASE_SCHEMA.json'
OUTPUT_MD = '../../_DOCS/SUBIEKT_GT_DATABASE_SCHEMA.md'

def run_sql_query(query):
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
        result = subprocess.run(cmd, capture_output=True, text=True, timeout=60)

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
        print("sqlcmd not found")
        return []
    except Exception as e:
        print(f"Error: {e}")
        return []

def scan_database():
    """Skanuje strukture bazy i zapisuje do JSON/MD"""
    print(f"Scanning database: {DATABASE} on {SERVER}")
    print("=" * 60)

    schema = {
        "database": DATABASE,
        "server": SERVER,
        "scanned_at": datetime.now().isoformat(),
        "tables": {},
        "summary": {}
    }

    # 1. Test polaczenia
    print("Testing connection...")
    test = run_sql_query("SELECT 1 as test")
    if not test:
        print("Connection failed!")
        return None
    print("Connection OK")

    # 2. Pobierz wszystkie tabele
    print("\nGetting all tables...")
    tables_raw = run_sql_query("""
        SELECT TABLE_NAME
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_TYPE = 'BASE TABLE'
        ORDER BY TABLE_NAME
    """)

    all_tables = [t.strip() for t in tables_raw if t.strip()]
    print(f"Found {len(all_tables)} tables")

    # 3. Dla kazdej tabeli pobierz kolumny
    print("\nScanning table structures...")

    for i, table_name in enumerate(all_tables):
        if i % 20 == 0:
            print(f"  Progress: {i}/{len(all_tables)} tables...")

        columns_raw = run_sql_query(f"""
            SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE,
                   ISNULL(CAST(CHARACTER_MAXIMUM_LENGTH AS VARCHAR), '') as MAX_LEN
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = '{table_name}'
            ORDER BY ORDINAL_POSITION
        """)

        columns = []
        for col_line in columns_raw:
            if '|' in col_line:
                parts = col_line.split('|')
                if len(parts) >= 3:
                    columns.append({
                        "name": parts[0].strip(),
                        "type": parts[1].strip(),
                        "nullable": parts[2].strip() == 'YES',
                        "max_length": parts[3].strip() if len(parts) > 3 else None
                    })

        # Pobierz liczbe rekordow (szybkie COUNT)
        count_raw = run_sql_query(f"SELECT COUNT(*) FROM [{table_name}]")
        row_count = int(count_raw[0]) if count_raw and count_raw[0].isdigit() else 0

        schema["tables"][table_name] = {
            "columns": columns,
            "row_count": row_count
        }

    # 4. Kategoryzuj tabele
    print("\nCategorizing tables...")

    product_tables = [t for t in all_tables if 'tow' in t.lower() or 'product' in t.lower()]
    customer_tables = [t for t in all_tables if 'kh' in t.lower() or 'kontrahent' in t.lower() or 'klient' in t.lower()]
    document_tables = [t for t in all_tables if 'dok' in t.lower() or 'dokument' in t.lower()]
    stock_tables = [t for t in all_tables if 'stan' in t.lower() or 'mag' in t.lower()]
    price_tables = [t for t in all_tables if 'cen' in t.lower() or 'price' in t.lower()]

    schema["summary"] = {
        "total_tables": len(all_tables),
        "product_tables": product_tables,
        "customer_tables": customer_tables,
        "document_tables": document_tables,
        "stock_tables": stock_tables,
        "price_tables": price_tables
    }

    # 5. Zapisz JSON
    print(f"\nSaving to {OUTPUT_FILE}...")
    with open(OUTPUT_FILE, 'w', encoding='utf-8') as f:
        json.dump(schema, f, indent=2, ensure_ascii=False)

    # 6. Generuj MD
    print(f"Generating {OUTPUT_MD}...")
    generate_markdown(schema)

    print("\nDone!")
    return schema

def generate_markdown(schema):
    """Generuje plik Markdown z dokumentacja"""

    md = f"""# Subiekt GT Database Schema

**Database:** {schema['database']}
**Server:** {schema['server']}
**Scanned:** {schema['scanned_at']}

## Summary

| Category | Count | Tables |
|----------|-------|--------|
| Total Tables | {schema['summary']['total_tables']} | - |
| Product Tables | {len(schema['summary']['product_tables'])} | {', '.join(schema['summary']['product_tables'][:5])}... |
| Customer Tables | {len(schema['summary']['customer_tables'])} | {', '.join(schema['summary']['customer_tables'][:5])}... |
| Document Tables | {len(schema['summary']['document_tables'])} | {', '.join(schema['summary']['document_tables'][:5])}... |
| Stock Tables | {len(schema['summary']['stock_tables'])} | {', '.join(schema['summary']['stock_tables'][:5])}... |
| Price Tables | {len(schema['summary']['price_tables'])} | {', '.join(schema['summary']['price_tables'][:5])}... |

## Key Tables

"""

    # Dodaj kluczowe tabele
    key_tables = ['tw__Towar', 'tw_Cena', 'tw_Stan', 'kh__Kontrahent', 'dok__Dokument',
                  'sl_Magazyn', 'sl_RodzajCeny', 'sl_StawkaVAT']

    for table_name in key_tables:
        if table_name in schema['tables']:
            table = schema['tables'][table_name]
            md += f"\n### {table_name}\n\n"
            md += f"**Rows:** {table['row_count']}\n\n"
            md += "| Column | Type | Nullable |\n"
            md += "|--------|------|----------|\n"

            for col in table['columns'][:30]:  # Max 30 kolumn
                md += f"| {col['name']} | {col['type']} | {'Yes' if col['nullable'] else 'No'} |\n"

            if len(table['columns']) > 30:
                md += f"| ... | ({len(table['columns']) - 30} more columns) | ... |\n"

    md += "\n## All Tables\n\n"
    md += "| Table | Columns | Rows |\n"
    md += "|-------|---------|------|\n"

    for table_name, table_data in sorted(schema['tables'].items()):
        md += f"| {table_name} | {len(table_data['columns'])} | {table_data['row_count']} |\n"

    with open(OUTPUT_MD, 'w', encoding='utf-8') as f:
        f.write(md)

if __name__ == "__main__":
    scan_database()
