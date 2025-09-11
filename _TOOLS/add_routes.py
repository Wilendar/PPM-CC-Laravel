lines = []
with open("routes/web.php", "r", encoding="utf-8") as f:
    lines = f.readlines()

# Znajdź miejsce do wstawienia routes (przed końcem admin group)
insert_pos = -1
for i, line in enumerate(lines):
    if "Route::get(\"/integrations/{connection}/edit\"" in line:
        # Znajdź następną linię z "});" 
        for j in range(i+1, len(lines)):
            if lines[j].strip() == "});":
                insert_pos = j
                break
        break

if insert_pos != -1:
    # Wczytaj nowe routes
    with open("temp_routes_faza_c.txt", "r", encoding="utf-8") as f:
        new_routes = f.read()
    
    # Wstaw przed "});"
    lines.insert(insert_pos, new_routes + "\n")
    
    # Zapisz zaktualizowany plik
    with open("routes/web.php", "w", encoding="utf-8") as f:
        f.writelines(lines)
    
    print("FAZA C routes added successfully")
else:
    print("Could not find insertion point")
