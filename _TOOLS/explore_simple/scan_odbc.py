#!/usr/bin/env python3
"""
Skaner struktury bazy Subiekt GT - wersja ODBC
"""

import pyodbc
import json
from datetime import datetime
import os

# Konfiguracja
SERVER = '10.9.20.100'
DATABASE = 'MPP_TRADE'
USERNAME = 'sa'
PASSWORD = 'xzHdHT%f4BtG'

# Output files
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
OUTPUT_JSON = os.path.join(SCRIPT_DIR, '..', '..', '_DOCS', 'SUBIEKT_GT_DATABASE_SCHEMA.json')
OUTPUT_MD = os.path.join(SCRIPT_DIR, '..', '..', '_DOCS', 'SUBIEKT_GT_DATABASE_SCHEMA.md')

def get_connection():
    """Tworzy polaczenie ODBC"""
    conn_str = (
        f"DRIVER={{ODBC Driver 17 for SQL Server}};"
        f"SERVER={SERVER};"
        f"DATABASE={DATABASE};"
        f"UID={USERNAME};"
        f"PWD={PASSWORD};"
        f"TrustServerCertificate=yes;"
    )
    return pyodbc.connect(conn_str)

def scan_database():
    """Skanuje baze i tworzy indeks"""
    print(f"Connecting to {DATABASE} on {SERVER}...")

    try:
        conn = get_connection()
        cursor = conn.cursor()
        print("Connected!")
    except Exception as e:
        print(f"Connection failed: {e}")
        return None

    schema = {
        "database": DATABASE,
        "server": SERVER,
        "scanned_at": datetime.now().isoformat(),
        "tables": {},
        "key_tables": {},
        "summary": {}
    }

    # 1. Get all tables
    print("\nGetting tables...")
    cursor.execute("""
        SELECT TABLE_NAME
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_TYPE = 'BASE TABLE'
        ORDER BY TABLE_NAME
    """)
    all_tables = [row[0] for row in cursor.fetchall()]
    print(f"Found {len(all_tables)} tables")

    # 2. Get columns for each table
    print("\nScanning table structures...")
    for i, table_name in enumerate(all_tables):
        if i % 50 == 0:
            print(f"  Progress: {i}/{len(all_tables)}...")

        cursor.execute("""
            SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, CHARACTER_MAXIMUM_LENGTH
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
        """, table_name)

        columns = []
        for row in cursor.fetchall():
            columns.append({
                "name": row[0],
                "type": row[1],
                "nullable": row[2] == 'YES',
                "max_length": row[3]
            })

        schema["tables"][table_name] = {
            "columns": columns,
            "column_count": len(columns)
        }

    # 3. Get detailed info for key tables
    print("\nGetting details for key tables...")
    key_tables = [
        'tw__Towar', 'tw_Cena', 'tw_Stan', 'tw_Grupa', 'tw_Producent',
        'kh__Kontrahent', 'dok__Dokument', 'dok_Pozycja',
        'sl_Magazyn', 'sl_RodzajCeny', 'sl_StawkaVAT', 'sl_JednMiary'
    ]

    for table_name in key_tables:
        if table_name in schema["tables"]:
            try:
                cursor.execute(f"SELECT COUNT(*) FROM [{table_name}]")
                count = cursor.fetchone()[0]
                schema["tables"][table_name]["row_count"] = count
                schema["key_tables"][table_name] = schema["tables"][table_name]
                print(f"  {table_name}: {count} rows, {len(schema['tables'][table_name]['columns'])} columns")
            except Exception as e:
                print(f"  {table_name}: Error - {e}")

    # 4. Categorize tables
    product_tables = [t for t in all_tables if 'tw' in t.lower() or 'towar' in t.lower()]
    customer_tables = [t for t in all_tables if 'kh' in t.lower() or 'kontrahent' in t.lower()]
    document_tables = [t for t in all_tables if 'dok' in t.lower()]
    reference_tables = [t for t in all_tables if 'sl_' in t.lower()]

    schema["summary"] = {
        "total_tables": len(all_tables),
        "product_tables": product_tables,
        "customer_tables": customer_tables,
        "document_tables": document_tables,
        "reference_tables": reference_tables
    }

    conn.close()

    # 5. Save JSON
    print(f"\nSaving to {OUTPUT_JSON}...")
    os.makedirs(os.path.dirname(OUTPUT_JSON), exist_ok=True)
    with open(OUTPUT_JSON, 'w', encoding='utf-8') as f:
        json.dump(schema, f, indent=2, ensure_ascii=False)

    # 6. Generate MD
    print(f"Generating {OUTPUT_MD}...")
    generate_markdown(schema)

    print("\nDone!")
    print(f"\nKey table: tw__Towar columns:")
    if 'tw__Towar' in schema['key_tables']:
        for col in schema['key_tables']['tw__Towar']['columns']:
            print(f"  - {col['name']} ({col['type']})")

    return schema

def generate_markdown(schema):
    """Generuje dokumentacje MD"""
    md = f"""# Subiekt GT Database Schema (MPP_TRADE)

**Database:** {schema['database']}
**Server:** {schema['server']}
**Scanned:** {schema['scanned_at']}
**Total Tables:** {schema['summary']['total_tables']}

## Summary

| Category | Count |
|----------|-------|
| Product tables (tw*) | {len(schema['summary']['product_tables'])} |
| Customer tables (kh*) | {len(schema['summary']['customer_tables'])} |
| Document tables (dok*) | {len(schema['summary']['document_tables'])} |
| Reference tables (sl_*) | {len(schema['summary']['reference_tables'])} |

## Key Tables

"""

    for table_name, table_data in schema.get('key_tables', {}).items():
        row_count = table_data.get('row_count', 'N/A')
        md += f"\n### {table_name}\n\n"
        md += f"**Rows:** {row_count}  \n"
        md += f"**Columns:** {table_data['column_count']}\n\n"
        md += "| Column | Type | Nullable |\n"
        md += "|--------|------|----------|\n"

        for col in table_data['columns']:
            nullable = 'Yes' if col['nullable'] else 'No'
            col_type = col['type']
            if col['max_length']:
                col_type += f"({col['max_length']})"
            md += f"| `{col['name']}` | {col_type} | {nullable} |\n"

    md += "\n## All Tables\n\n"
    md += "| Table | Columns |\n"
    md += "|-------|---------|\n"

    for table_name in sorted(schema['tables'].keys()):
        col_count = schema['tables'][table_name]['column_count']
        md += f"| {table_name} | {col_count} |\n"

    with open(OUTPUT_MD, 'w', encoding='utf-8') as f:
        f.write(md)

if __name__ == "__main__":
    scan_database()
