# RAPORT ANALIZY: Dane techniczne Excel - Cechy Pojazdow

**Data**: 2025-12-02
**Plik zrodlowy**: `References/Karta Pojazdu-Dane techniczne.xlsx`
**Arkusz**: `Dane techniczne`

## Statystyki

| Metryka | Wartosc |
|---------|---------|
| Liczba wierszy (pojazdy) | 1041 |
| Liczba kolumn (cechy) | 113 |
| Wiersz z naglowkami | 3 |
| Poczatek danych | Wiersz 4 |

## Grupy Cech

### 1. IDENTYFIKACJA (kol. M-S)

| Nazwa kolumny | Typ | Przyklad |
|---------------|-----|----------|
| Marka | select | KAYO, YCF, PITSTER PRO |
| Indeks (SKU) | text | BG-KAYO-ES50 |
| Model | text | Buggy KAYO eS50 |
| Typ | select | Buggy, Pit Bike, Quad, Cross |
| Grupy | select | Elektryczne, Spalinowe |

### 2. SILNIK (kol. T-Y, plus dodatkowe)

| Nazwa kolumny | Typ | Jednostka | Przyklad |
|---------------|-----|-----------|----------|
| Pojemnosc silnika | number | cm3 | 125, 190, Nie dotyczy |
| Moc (KM) przy ilu RPM | text | KM@RPM | 12 przy 8500 |
| Moc (W) | number | W | 500, 1000 |
| Ilosc oleju w silniku | number | ml | 600, 800 |
| Oznaczenie silnika | text | - | YX140, ZS190 |
| Typ silnika | select | - | Elektryczny, Spalinowy 4-suwowy |
| Liczba zaworow | number | - | 2, 4 |
| Stopien sprezania | text | - | 9.5:1 |

### 3. UKLAD NAPEDOWY (kol. AA-AI)

| Nazwa kolumny | Typ | Przyklad |
|---------------|-----|----------|
| Skrzynia (ile biegow) | number/text | 4, 6, Nie dotyczy |
| Bieg wsteczny | bool | Tak, Nie |
| Rodzaj skrzyni biegow | select | Automatyczna, Manualna, Polautomatyczna |
| Uklad biegow | text | 1-N-2-3-4 |
| Zebatka przod | number | 14, 15, 17 |
| Zebatka tyl | number | 37, 40, 42 |
| Lancuch rozmiar | text | 420, 428, 520 |

### 4. WYMIARY (kol. AO-AU)

| Nazwa kolumny | Typ | Jednostka | Przyklad |
|---------------|-----|-----------|----------|
| Dlugosc pojazdu | number | cm | 105, 160, 195 |
| Szerokosc pojazdu | number | cm | 71, 80, 90 |
| Wysokosc pojazdu | number | cm | 74, 110, 120 |
| Wysokosc do siedzenia | number | cm | 17, 55, 85 |
| Przesiwt | number | cm | 7, 11.5, 25 |
| Rozstaw osi pojazdu | number | cm | 84, 95, 130 |
| Waga pojazdu | number | kg | 46.2, 80, 125 |

### 5. ZAWIESZENIE (kol. BI-BW)

| Nazwa kolumny | Typ | Jednostka | Przyklad |
|---------------|-----|-----------|----------|
| Marka amortyzatora przod | text | - | KAYO, DNM, FASTACE |
| Dlugosc amortyzatora przod | number | mm | 620, 730, 810 |
| Regulacje amortyzatora przod (COM) | bool/text | - | Tak, Nie |
| Regulacje amortyzatora przod (REB) | bool/text | - | Tak, Nie |
| Rama | select | - | Stalowa, Aluminiowa |
| Wahacz tyl | select | - | Stalowy, Aluminiowy |
| Wahacz dlugosc | number | mm | 350, 400, 450 |

### 6. HAMULCE (kol. BX-CC)

| Nazwa kolumny | Typ | Przyklad |
|---------------|-----|----------|
| Rodzaj ukladu hamulcowego | select | Tarczowy hydrauliczny, Tarczowy na linke |
| Zacisk przod | text | 2 Zaciski 2 Tloczkowe |
| Zacisk tyl | text | 1 Zacisk 1 Tloczkowy |
| Srednica tarczy hamulcowej przod | number | 190, 220, 270 |
| Srednica tarczy hamulcowej tyl | number | 150, 160, 190 |

### 7. KOLA (kol. AJ-AL, CD)

| Nazwa kolumny | Typ | Przyklad |
|---------------|-----|----------|
| Rozmiar felgi przod | number | 4, 10, 12, 14, 17 (cale) |
| Rozmiar felgi tyl | number | 4, 10, 12, 14, 17 (cale) |
| Rozmiar opon przod | text | 3.00x4, 70/100-17, 2.75-10 |
| Rozmiar opon tyl | text | 3.00x4, 90/100-14, 3.00-10 |
| Obrecze kol | select | Stalowe, Aluminiowe |

### 8. POJAZDY ELEKTRYCZNE (kol. AE, BL-BM, CP)

| Nazwa kolumny | Typ | Jednostka | Przyklad |
|---------------|-----|-----------|----------|
| Tryby predkosci | number | - | 2, 3 |
| Napiecie Volt | number | V | 36, 48, 60, 72 |
| Pojemnosc akumulatora | number | Ah | 9, 12, 20, 30 |
| Typ akumulatora/baterii | select | - | Kwasowo olowiowy, Litowo jonowy |
| Zasieg | number | km | 20, 40, 60 |

### 9. POJAZDY SPALINOWE (kol. AV-BC, CF-CI)

| Nazwa kolumny | Typ | Przyklad |
|---------------|-----|----------|
| Chlodzony powietrzem | bool | Tak, Nie |
| Chlodzony ciecza | bool | Tak, Nie |
| Chlodzony olejem | bool | Tak, Nie |
| Chlodnica oleju | bool | Tak, Nie |
| Wtrysk paliwa | bool | Tak, Nie |
| Marka gaznika | text | MIKUNI, KEIHIN, PWK |
| Model gaznika | text | VM22, PE24, PWK28 |
| Airbox | bool/text | Tak, Nie |
| Rozmiar filtra | number | mm |
| Pojemnosc zbiornika | number | L | 3, 4.5, 6 |
| Dedykowany olej silnikowy | text | 10W40, 15W50 |
| Rozrusznik nozny | bool | Tak, Nie |
| Rozrusznik elektryczny | bool | Tak, Nie |
| Rozrusznik na linke | bool | Tak, Nie |

### 10. DOKUMENTACJA (kol. CR-DI)

| Nazwa kolumny | Typ | Przyklad |
|---------------|-----|----------|
| Instrukcja oblugi EN | text (URL) | link lub Brak danych |
| Instrukcja obslugi PL | text (URL) | link lub Brak danych |
| Service Manual | text (URL) | link lub Brak danych |
| Katalog czesci pojazdu z fabryki | text (URL) | link |
| Katalog czesci pojazdu MPP TRADE | text (URL) | link |
| Katalog czesci silnika z fabryki | text (URL) | link |
| Katalog czesci silnika MPP TRADE | text (URL) | link |
| Warunki gwarancji | text (URL) | link |
| Link do karty Trello produktu MPP | text (URL) | https://trello.com/... |

### 11. INNE (kol. AG-AN, plus dodatkowe)

| Nazwa kolumny | Typ | Przyklad |
|---------------|-----|----------|
| Stopka boczna | bool | Tak, Nie, Nie dotyczy |
| Stojak w zestawie | bool | Tak, Nie |
| Seryjny cichy tlumik | bool | Tak, Nie |
| Mozliwosc montazu wiekszych kol | bool | Tak, Nie |
| Mozliwosc montazu wyzszego siedzenia | bool | Tak, Nie |
| Zalecany wiek minimalny | number | 3, 5, 8, 14 |
| Maksymalna waga uzytkownika | number | 40, 60, 100, 130 |
| Okres gwarancji | text | 3 Miesiace, 6 Miesiecy, 12 Miesiecy |

## Wzorce Wartosci Specjalnych

| Wartosc | Znaczenie | Czestotliwosc |
|---------|-----------|---------------|
| `Nie dotyczy` | Cecha nieadekwatna dla typu pojazdu | Wysoka (pojazdy elektryczne vs spalinowe) |
| `Brak danych` | Informacja do uzupelnienia | Srednia |
| `Tak` / `Nie` | Wartosc boolowska | Wysoka |
| Puste | Brak wartosci | Niska |

## Rekomendacje dla PPM

### 1. Typy wartosci w PPM

```php
// FeatureType value_types
'text'   // Dowolny tekst (Model, Oznaczenie silnika)
'number' // Liczba z jednostka (Moc, Waga, Wymiary)
'bool'   // Tak/Nie (Bieg wsteczny, Rozrusznik elektryczny)
'select' // Wybor z listy (Marka, Typ, Rodzaj skrzyni)
'url'    // NOWY - Link URL (Instrukcje, Trello)
```

### 2. Warunkowosc cech

System powinien obslugiwac:
- **Grupy warunkowe** - np. cechy "Elektryczne" pokazane tylko dla pojazdow elektrycznych
- **Wartosc "Nie dotyczy"** - automatycznie ustawiana dla nieadekwatnych cech
- **Szablony per typ** - Pit Bike, Quad, Buggy maja rozne zestawy cech

### 3. Jednostki

Jednostki powinny byc przechowywane oddzielnie:
```php
// FeatureType::$unit
'cm', 'mm', 'kg', 'L', 'ml', 'V', 'Ah', 'W', 'KM', 'RPM', 'cale (")'
```

### 4. Grupy cech

```php
// FeatureGroup::CODES
'identyfikacja', 'silnik', 'naped', 'wymiary',
'zawieszenie', 'hamulce', 'kola', 'elektryczne',
'spalinowe', 'dokumentacja', 'inne'
```

### 5. Mapowanie do PrestaShop

| PPM Group | PS Feature Group | PS Feature Names |
|-----------|------------------|------------------|
| wymiary | Wymiary | Dlugosc, Szerokosc, Wysokosc, Waga |
| silnik | Silnik | Pojemnosc, Moc, Typ silnika |
| kola | Kola i opony | Rozmiar felg, Rozmiar opon |
| zawieszenie | Zawieszenie | Amortyzatory, Rama |

## Nastepne kroki

1. **Migracja** - Dodac nowe FeatureTypes zgodnie z analiza
2. **Seeder** - Utworzyc VehicleFeaturesSeeder z pelna biblioteka cech
3. **Szablony** - Utworzyc szablony per typ pojazdu (Pit Bike, Quad, Buggy, Cross)
4. **Import** - Mechanizm importu z Excel do PPM
5. **Sync PS** - Mapowanie cech PPM <-> PrestaShop features

---

**Wygenerowano**: 2025-12-02
**Zrodlo**: ImportExcel PowerShell module
