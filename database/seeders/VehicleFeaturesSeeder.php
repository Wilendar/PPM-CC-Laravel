<?php

namespace Database\Seeders;

use App\Models\FeatureGroup;
use App\Models\FeatureType;
use Illuminate\Database\Seeder;

/**
 * Vehicle Features Seeder
 *
 * ETAP_07e FAZA 1.4.2 - Seeder 85 cech z analizy Excel
 *
 * Source: References/Karta Pojazdu-Dane techniczne.xlsx
 * Sheet: Dane techniczne (1041 rows, 113 columns)
 */
class VehicleFeaturesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // First ensure groups exist
        $this->call(FeatureGroupsSeeder::class);

        // Get group IDs
        $groups = FeatureGroup::pluck('id', 'code')->toArray();

        $features = $this->getFeatureDefinitions($groups);

        $position = 0;
        foreach ($features as $feature) {
            $position++;
            $feature['position'] = $position;

            FeatureType::updateOrCreate(
                ['code' => $feature['code']],
                $feature
            );
        }

        $this->command->info('Created ' . count($features) . ' feature types.');
    }

    /**
     * Get all feature definitions
     */
    private function getFeatureDefinitions(array $groups): array
    {
        return array_merge(
            $this->getIdentyfikacjaFeatures($groups),
            $this->getSilnikFeatures($groups),
            $this->getNapedFeatures($groups),
            $this->getWymiaryFeatures($groups),
            $this->getZawieszenieFeatures($groups),
            $this->getHamulceFeatures($groups),
            $this->getKolaFeatures($groups),
            $this->getElektryczneFeatures($groups),
            $this->getSpalinowFeatures($groups),
            $this->getDokumentacjaFeatures($groups),
            $this->getInneFeatures($groups)
        );
    }

    /**
     * IDENTYFIKACJA - col M-S
     */
    private function getIdentyfikacjaFeatures(array $groups): array
    {
        return [
            [
                'code' => 'marka',
                'name' => 'Marka',
                'value_type' => 'select',
                'unit' => null,
                'feature_group_id' => $groups['identyfikacja'],
                'group' => 'IDENTYFIKACJA',
                'input_placeholder' => 'Wybierz marke',
                'excel_column' => 'M',
                'prestashop_name' => 'Marka',
                'is_active' => true,
            ],
            [
                'code' => 'model',
                'name' => 'Model',
                'value_type' => 'text',
                'unit' => null,
                'feature_group_id' => $groups['identyfikacja'],
                'group' => 'IDENTYFIKACJA',
                'input_placeholder' => 'Nazwa modelu pojazdu',
                'excel_column' => 'O',
                'prestashop_name' => 'Model',
                'is_active' => true,
            ],
            [
                'code' => 'typ_pojazdu',
                'name' => 'Typ pojazdu',
                'value_type' => 'select',
                'unit' => null,
                'feature_group_id' => $groups['identyfikacja'],
                'group' => 'IDENTYFIKACJA',
                'input_placeholder' => 'Wybierz typ',
                'excel_column' => 'R',
                'prestashop_name' => 'Typ pojazdu',
                'is_active' => true,
            ],
            [
                'code' => 'grupa_pojazdu',
                'name' => 'Grupa pojazdu',
                'value_type' => 'select',
                'unit' => null,
                'feature_group_id' => $groups['identyfikacja'],
                'group' => 'IDENTYFIKACJA',
                'input_placeholder' => 'Elektryczne/Spalinowe',
                'excel_column' => 'S',
                'prestashop_name' => 'Grupa',
                'is_active' => true,
            ],
        ];
    }

    /**
     * SILNIK - col T-Y
     */
    private function getSilnikFeatures(array $groups): array
    {
        return [
            [
                'code' => 'pojemnosc_silnika',
                'name' => 'Pojemnosc silnika',
                'value_type' => 'number',
                'unit' => 'cm3',
                'feature_group_id' => $groups['silnik'],
                'group' => 'SILNIK',
                'input_placeholder' => 'np. 125, 190, 250',
                'excel_column' => 'T',
                'prestashop_name' => 'Pojemnosc silnika',
                'is_active' => true,
            ],
            [
                'code' => 'moc_km',
                'name' => 'Moc',
                'value_type' => 'text',
                'unit' => 'KM',
                'feature_group_id' => $groups['silnik'],
                'group' => 'SILNIK',
                'input_placeholder' => 'np. 12 przy 8500 RPM',
                'excel_column' => 'U',
                'prestashop_name' => 'Moc (KM)',
                'is_active' => true,
            ],
            [
                'code' => 'moc_w',
                'name' => 'Moc elektryczna',
                'value_type' => 'number',
                'unit' => 'W',
                'feature_group_id' => $groups['silnik'],
                'group' => 'SILNIK',
                'input_placeholder' => 'np. 500, 1000, 2000',
                'excel_column' => 'V',
                'prestashop_name' => 'Moc (W)',
                'conditional_group' => 'elektryczne',
                'is_active' => true,
            ],
            [
                'code' => 'ilosc_oleju_silnik',
                'name' => 'Ilosc oleju w silniku',
                'value_type' => 'number',
                'unit' => 'ml',
                'feature_group_id' => $groups['silnik'],
                'group' => 'SILNIK',
                'input_placeholder' => 'np. 600, 800, 1000',
                'excel_column' => 'X',
                'prestashop_name' => 'Ilosc oleju',
                'conditional_group' => 'spalinowe',
                'is_active' => true,
            ],
            [
                'code' => 'oznaczenie_silnika',
                'name' => 'Oznaczenie silnika',
                'value_type' => 'text',
                'unit' => null,
                'feature_group_id' => $groups['silnik'],
                'group' => 'SILNIK',
                'input_placeholder' => 'np. YX140, ZS190',
                'excel_column' => 'Y',
                'prestashop_name' => 'Oznaczenie silnika',
                'is_active' => true,
            ],
            [
                'code' => 'typ_silnika',
                'name' => 'Typ silnika',
                'value_type' => 'select',
                'unit' => null,
                'feature_group_id' => $groups['silnik'],
                'group' => 'SILNIK',
                'input_placeholder' => 'Wybierz typ silnika',
                'excel_column' => 'CP',
                'prestashop_name' => 'Typ silnika',
                'is_active' => true,
            ],
            [
                'code' => 'liczba_zaworow',
                'name' => 'Liczba zaworow',
                'value_type' => 'number',
                'unit' => null,
                'feature_group_id' => $groups['silnik'],
                'group' => 'SILNIK',
                'input_placeholder' => 'np. 2, 4',
                'excel_column' => 'CU',
                'prestashop_name' => 'Liczba zaworow',
                'conditional_group' => 'spalinowe',
                'is_active' => true,
            ],
            [
                'code' => 'stopien_sprezania',
                'name' => 'Stopien sprezania',
                'value_type' => 'text',
                'unit' => null,
                'feature_group_id' => $groups['silnik'],
                'group' => 'SILNIK',
                'input_placeholder' => 'np. 9.5:1',
                'excel_column' => 'BA',
                'prestashop_name' => 'Stopien sprezania',
                'conditional_group' => 'spalinowe',
                'is_active' => true,
            ],
        ];
    }

    /**
     * UKLAD NAPEDOWY - col AA-AI
     */
    private function getNapedFeatures(array $groups): array
    {
        return [
            [
                'code' => 'skrzynia_biegow',
                'name' => 'Skrzynia biegow',
                'value_type' => 'text',
                'unit' => null,
                'feature_group_id' => $groups['naped'],
                'group' => 'NAPED',
                'input_placeholder' => 'np. 4, 6, Automatyczna',
                'excel_column' => 'AF',
                'prestashop_name' => 'Skrzynia biegow',
                'is_active' => true,
            ],
            [
                'code' => 'bieg_wsteczny',
                'name' => 'Bieg wsteczny',
                'value_type' => 'bool',
                'unit' => null,
                'feature_group_id' => $groups['naped'],
                'group' => 'NAPED',
                'input_placeholder' => null,
                'excel_column' => 'AG',
                'prestashop_name' => 'Bieg wsteczny',
                'is_active' => true,
            ],
            [
                'code' => 'rodzaj_skrzyni',
                'name' => 'Rodzaj skrzyni biegow',
                'value_type' => 'select',
                'unit' => null,
                'feature_group_id' => $groups['naped'],
                'group' => 'NAPED',
                'input_placeholder' => 'Manualna/Automatyczna/CVT',
                'excel_column' => 'AH',
                'prestashop_name' => 'Rodzaj skrzyni',
                'is_active' => true,
            ],
            [
                'code' => 'uklad_biegow',
                'name' => 'Uklad biegow',
                'value_type' => 'text',
                'unit' => null,
                'feature_group_id' => $groups['naped'],
                'group' => 'NAPED',
                'input_placeholder' => 'np. 1-N-2-3-4',
                'excel_column' => 'AI',
                'prestashop_name' => 'Uklad biegow',
                'conditional_group' => 'spalinowe',
                'is_active' => true,
            ],
            [
                'code' => 'zebatka_przod',
                'name' => 'Zebatka przod',
                'value_type' => 'number',
                'unit' => 'zeby',
                'feature_group_id' => $groups['naped'],
                'group' => 'NAPED',
                'input_placeholder' => 'np. 14, 15, 17',
                'excel_column' => 'CV',
                'prestashop_name' => 'Zebatka przod',
                'is_active' => true,
            ],
            [
                'code' => 'zebatka_tyl',
                'name' => 'Zebatka tyl',
                'value_type' => 'number',
                'unit' => 'zeby',
                'feature_group_id' => $groups['naped'],
                'group' => 'NAPED',
                'input_placeholder' => 'np. 37, 40, 42',
                'excel_column' => 'CW',
                'prestashop_name' => 'Zebatka tyl',
                'is_active' => true,
            ],
            [
                'code' => 'lancuch_rozmiar',
                'name' => 'Lancuch rozmiar',
                'value_type' => 'text',
                'unit' => null,
                'feature_group_id' => $groups['naped'],
                'group' => 'NAPED',
                'input_placeholder' => 'np. 420, 428, 520',
                'excel_column' => 'BP',
                'prestashop_name' => 'Rozmiar lancucha',
                'is_active' => true,
            ],
        ];
    }

    /**
     * WYMIARY - col AO-AU, AL
     */
    private function getWymiaryFeatures(array $groups): array
    {
        return [
            [
                'code' => 'dlugosc',
                'name' => 'Dlugosc pojazdu',
                'value_type' => 'number',
                'unit' => 'cm',
                'feature_group_id' => $groups['wymiary'],
                'group' => 'WYMIARY',
                'input_placeholder' => 'np. 105, 160, 195',
                'excel_column' => 'AP',
                'prestashop_name' => 'Dlugosc',
                'is_active' => true,
            ],
            [
                'code' => 'szerokosc',
                'name' => 'Szerokosc pojazdu',
                'value_type' => 'number',
                'unit' => 'cm',
                'feature_group_id' => $groups['wymiary'],
                'group' => 'WYMIARY',
                'input_placeholder' => 'np. 71, 80, 90',
                'excel_column' => 'AO',
                'prestashop_name' => 'Szerokosc',
                'is_active' => true,
            ],
            [
                'code' => 'wysokosc',
                'name' => 'Wysokosc pojazdu',
                'value_type' => 'number',
                'unit' => 'cm',
                'feature_group_id' => $groups['wymiary'],
                'group' => 'WYMIARY',
                'input_placeholder' => 'np. 74, 110, 120',
                'excel_column' => 'AQ',
                'prestashop_name' => 'Wysokosc',
                'is_active' => true,
            ],
            [
                'code' => 'wysokosc_siedzenia',
                'name' => 'Wysokosc do siedzenia',
                'value_type' => 'number',
                'unit' => 'cm',
                'feature_group_id' => $groups['wymiary'],
                'group' => 'WYMIARY',
                'input_placeholder' => 'np. 55, 75, 85',
                'excel_column' => 'W',
                'prestashop_name' => 'Wysokosc siedzenia',
                'is_active' => true,
            ],
            [
                'code' => 'przeswit',
                'name' => 'Przeswit',
                'value_type' => 'number',
                'unit' => 'cm',
                'feature_group_id' => $groups['wymiary'],
                'group' => 'WYMIARY',
                'input_placeholder' => 'np. 7, 15, 25',
                'excel_column' => 'AR',
                'prestashop_name' => 'Przeswit',
                'is_active' => true,
            ],
            [
                'code' => 'rozstaw_osi',
                'name' => 'Rozstaw osi',
                'value_type' => 'number',
                'unit' => 'cm',
                'feature_group_id' => $groups['wymiary'],
                'group' => 'WYMIARY',
                'input_placeholder' => 'np. 84, 95, 130',
                'excel_column' => 'AS',
                'prestashop_name' => 'Rozstaw osi',
                'is_active' => true,
            ],
            [
                'code' => 'waga',
                'name' => 'Waga pojazdu',
                'value_type' => 'number',
                'unit' => 'kg',
                'feature_group_id' => $groups['wymiary'],
                'group' => 'WYMIARY',
                'input_placeholder' => 'np. 46, 80, 125',
                'excel_column' => 'AL',
                'prestashop_name' => 'Waga',
                'is_active' => true,
            ],
        ];
    }

    /**
     * ZAWIESZENIE - col BI-BW
     */
    private function getZawieszenieFeatures(array $groups): array
    {
        return [
            [
                'code' => 'amortyzator_przod_marka',
                'name' => 'Amortyzator przod - marka',
                'value_type' => 'text',
                'unit' => null,
                'feature_group_id' => $groups['zawieszenie'],
                'group' => 'ZAWIESZENIE',
                'input_placeholder' => 'np. KAYO, DNM, FASTACE',
                'excel_column' => 'BI',
                'prestashop_name' => 'Marka amortyzatora przod',
                'is_active' => true,
            ],
            [
                'code' => 'amortyzator_przod_dlugosc',
                'name' => 'Amortyzator przod - dlugosc',
                'value_type' => 'number',
                'unit' => 'mm',
                'feature_group_id' => $groups['zawieszenie'],
                'group' => 'ZAWIESZENIE',
                'input_placeholder' => 'np. 620, 730, 810',
                'excel_column' => 'BJ',
                'prestashop_name' => 'Dlugosc amortyzatora przod',
                'is_active' => true,
            ],
            [
                'code' => 'amortyzator_przod_regulacja_com',
                'name' => 'Regulacja COM przod',
                'value_type' => 'bool',
                'unit' => null,
                'feature_group_id' => $groups['zawieszenie'],
                'group' => 'ZAWIESZENIE',
                'input_placeholder' => null,
                'excel_column' => 'BK',
                'prestashop_name' => 'Regulacja COM przod',
                'is_active' => true,
            ],
            [
                'code' => 'amortyzator_przod_regulacja_reb',
                'name' => 'Regulacja REB przod',
                'value_type' => 'bool',
                'unit' => null,
                'feature_group_id' => $groups['zawieszenie'],
                'group' => 'ZAWIESZENIE',
                'input_placeholder' => null,
                'excel_column' => 'BL',
                'prestashop_name' => 'Regulacja REB przod',
                'is_active' => true,
            ],
            [
                'code' => 'amortyzator_tyl_marka',
                'name' => 'Amortyzator tyl - marka',
                'value_type' => 'text',
                'unit' => null,
                'feature_group_id' => $groups['zawieszenie'],
                'group' => 'ZAWIESZENIE',
                'input_placeholder' => 'np. KAYO, DNM, FASTACE',
                'excel_column' => 'CD',
                'prestashop_name' => 'Marka amortyzatora tyl',
                'is_active' => true,
            ],
            [
                'code' => 'amortyzator_tyl_dlugosc',
                'name' => 'Amortyzator tyl - dlugosc',
                'value_type' => 'number',
                'unit' => 'mm',
                'feature_group_id' => $groups['zawieszenie'],
                'group' => 'ZAWIESZENIE',
                'input_placeholder' => 'np. 300, 350, 400',
                'excel_column' => 'CE',
                'prestashop_name' => 'Dlugosc amortyzatora tyl',
                'is_active' => true,
            ],
            [
                'code' => 'rama_material',
                'name' => 'Rama',
                'value_type' => 'select',
                'unit' => null,
                'feature_group_id' => $groups['zawieszenie'],
                'group' => 'ZAWIESZENIE',
                'input_placeholder' => 'Stalowa/Aluminiowa',
                'excel_column' => 'BM',
                'prestashop_name' => 'Rama',
                'is_active' => true,
            ],
            [
                'code' => 'wahacz_tyl_material',
                'name' => 'Wahacz tyl',
                'value_type' => 'select',
                'unit' => null,
                'feature_group_id' => $groups['zawieszenie'],
                'group' => 'ZAWIESZENIE',
                'input_placeholder' => 'Stalowy/Aluminiowy',
                'excel_column' => 'BN',
                'prestashop_name' => 'Wahacz tyl',
                'is_active' => true,
            ],
            [
                'code' => 'wahacz_dlugosc',
                'name' => 'Wahacz dlugosc',
                'value_type' => 'number',
                'unit' => 'mm',
                'feature_group_id' => $groups['zawieszenie'],
                'group' => 'ZAWIESZENIE',
                'input_placeholder' => 'np. 350, 400, 450',
                'excel_column' => 'BO',
                'prestashop_name' => 'Dlugosc wahacza',
                'is_active' => true,
            ],
        ];
    }

    /**
     * HAMULCE - col BX-CC
     */
    private function getHamulceFeatures(array $groups): array
    {
        return [
            [
                'code' => 'uklad_hamulcowy',
                'name' => 'Rodzaj ukladu hamulcowego',
                'value_type' => 'select',
                'unit' => null,
                'feature_group_id' => $groups['hamulce'],
                'group' => 'HAMULCE',
                'input_placeholder' => 'Tarczowy hydrauliczny/na linke',
                'excel_column' => 'BU',
                'prestashop_name' => 'Uklad hamulcowy',
                'is_active' => true,
            ],
            [
                'code' => 'zacisk_przod',
                'name' => 'Zacisk przod',
                'value_type' => 'text',
                'unit' => null,
                'feature_group_id' => $groups['hamulce'],
                'group' => 'HAMULCE',
                'input_placeholder' => 'np. 2 Zaciski 2 Tloczkowe',
                'excel_column' => 'BV',
                'prestashop_name' => 'Zacisk hamulcowy przod',
                'is_active' => true,
            ],
            [
                'code' => 'zacisk_tyl',
                'name' => 'Zacisk tyl',
                'value_type' => 'text',
                'unit' => null,
                'feature_group_id' => $groups['hamulce'],
                'group' => 'HAMULCE',
                'input_placeholder' => 'np. 1 Zacisk 1 Tloczkowy',
                'excel_column' => 'BW',
                'prestashop_name' => 'Zacisk hamulcowy tyl',
                'is_active' => true,
            ],
            [
                'code' => 'tarcza_przod',
                'name' => 'Tarcza hamulcowa przod',
                'value_type' => 'number',
                'unit' => 'mm',
                'feature_group_id' => $groups['hamulce'],
                'group' => 'HAMULCE',
                'input_placeholder' => 'np. 190, 220, 270',
                'excel_column' => 'BX',
                'prestashop_name' => 'Srednica tarczy przod',
                'is_active' => true,
            ],
            [
                'code' => 'tarcza_tyl',
                'name' => 'Tarcza hamulcowa tyl',
                'value_type' => 'number',
                'unit' => 'mm',
                'feature_group_id' => $groups['hamulce'],
                'group' => 'HAMULCE',
                'input_placeholder' => 'np. 150, 160, 190',
                'excel_column' => 'BY',
                'prestashop_name' => 'Srednica tarczy tyl',
                'is_active' => true,
            ],
        ];
    }

    /**
     * KOLA I OPONY - col AJ-AL, BQ-BT, BZ
     */
    private function getKolaFeatures(array $groups): array
    {
        return [
            [
                'code' => 'felga_przod',
                'name' => 'Rozmiar felgi przod',
                'value_type' => 'number',
                'unit' => 'cale',
                'feature_group_id' => $groups['kola'],
                'group' => 'KOLA',
                'input_placeholder' => 'np. 10, 12, 14, 17',
                'excel_column' => 'AJ',
                'prestashop_name' => 'Rozmiar felgi przod',
                'is_active' => true,
            ],
            [
                'code' => 'felga_tyl',
                'name' => 'Rozmiar felgi tyl',
                'value_type' => 'number',
                'unit' => 'cale',
                'feature_group_id' => $groups['kola'],
                'group' => 'KOLA',
                'input_placeholder' => 'np. 10, 12, 14, 17',
                'excel_column' => 'AK',
                'prestashop_name' => 'Rozmiar felgi tyl',
                'is_active' => true,
            ],
            [
                'code' => 'opona_przod',
                'name' => 'Rozmiar opony przod',
                'value_type' => 'text',
                'unit' => null,
                'feature_group_id' => $groups['kola'],
                'group' => 'KOLA',
                'input_placeholder' => 'np. 70/100-17, 2.75-10',
                'excel_column' => 'BQ',
                'prestashop_name' => 'Rozmiar opony przod',
                'is_active' => true,
            ],
            [
                'code' => 'opona_tyl',
                'name' => 'Rozmiar opony tyl',
                'value_type' => 'text',
                'unit' => null,
                'feature_group_id' => $groups['kola'],
                'group' => 'KOLA',
                'input_placeholder' => 'np. 90/100-14, 3.00-10',
                'excel_column' => 'BR',
                'prestashop_name' => 'Rozmiar opony tyl',
                'is_active' => true,
            ],
            [
                'code' => 'obrecze_material',
                'name' => 'Material obreczy kol',
                'value_type' => 'select',
                'unit' => null,
                'feature_group_id' => $groups['kola'],
                'group' => 'KOLA',
                'input_placeholder' => 'Stalowe/Aluminiowe',
                'excel_column' => 'BZ',
                'prestashop_name' => 'Obrecze kol',
                'is_active' => true,
            ],
        ];
    }

    /**
     * POJAZDY ELEKTRYCZNE - col AE, BG-BH, CQ
     */
    private function getElektryczneFeatures(array $groups): array
    {
        return [
            [
                'code' => 'tryby_predkosci',
                'name' => 'Tryby predkosci',
                'value_type' => 'number',
                'unit' => null,
                'feature_group_id' => $groups['elektryczne'],
                'group' => 'ELEKTRYCZNE',
                'input_placeholder' => 'np. 2, 3',
                'excel_column' => 'AE',
                'prestashop_name' => 'Tryby predkosci',
                'conditional_group' => 'elektryczne',
                'is_active' => true,
            ],
            [
                'code' => 'napiecie',
                'name' => 'Napiecie',
                'value_type' => 'number',
                'unit' => 'V',
                'feature_group_id' => $groups['elektryczne'],
                'group' => 'ELEKTRYCZNE',
                'input_placeholder' => 'np. 36, 48, 60, 72',
                'excel_column' => 'BG',
                'prestashop_name' => 'Napiecie',
                'conditional_group' => 'elektryczne',
                'is_active' => true,
            ],
            [
                'code' => 'pojemnosc_baterii',
                'name' => 'Pojemnosc akumulatora',
                'value_type' => 'number',
                'unit' => 'Ah',
                'feature_group_id' => $groups['elektryczne'],
                'group' => 'ELEKTRYCZNE',
                'input_placeholder' => 'np. 9, 12, 20, 30',
                'excel_column' => 'BH',
                'prestashop_name' => 'Pojemnosc baterii',
                'conditional_group' => 'elektryczne',
                'is_active' => true,
            ],
            [
                'code' => 'typ_baterii',
                'name' => 'Typ akumulatora',
                'value_type' => 'select',
                'unit' => null,
                'feature_group_id' => $groups['elektryczne'],
                'group' => 'ELEKTRYCZNE',
                'input_placeholder' => 'Kwasowo-olowiowy/Litowo-jonowy',
                'excel_column' => 'BF',
                'prestashop_name' => 'Typ baterii',
                'conditional_group' => 'elektryczne',
                'is_active' => true,
            ],
            [
                'code' => 'zasieg',
                'name' => 'Zasieg',
                'value_type' => 'number',
                'unit' => 'km',
                'feature_group_id' => $groups['elektryczne'],
                'group' => 'ELEKTRYCZNE',
                'input_placeholder' => 'np. 20, 40, 60',
                'excel_column' => 'CQ',
                'prestashop_name' => 'Zasieg',
                'conditional_group' => 'elektryczne',
                'is_active' => true,
            ],
        ];
    }

    /**
     * POJAZDY SPALINOWE - col AT-BC, CF-CI
     */
    private function getSpalinowFeatures(array $groups): array
    {
        return [
            [
                'code' => 'chlodzenie_powietrzem',
                'name' => 'Chlodzony powietrzem',
                'value_type' => 'bool',
                'unit' => null,
                'feature_group_id' => $groups['spalinowe'],
                'group' => 'SPALINOWE',
                'input_placeholder' => null,
                'excel_column' => 'AT',
                'prestashop_name' => 'Chlodzony powietrzem',
                'conditional_group' => 'spalinowe',
                'is_active' => true,
            ],
            [
                'code' => 'chlodzenie_ciecza',
                'name' => 'Chlodzony ciecza',
                'value_type' => 'bool',
                'unit' => null,
                'feature_group_id' => $groups['spalinowe'],
                'group' => 'SPALINOWE',
                'input_placeholder' => null,
                'excel_column' => 'AU',
                'prestashop_name' => 'Chlodzony ciecza',
                'conditional_group' => 'spalinowe',
                'is_active' => true,
            ],
            [
                'code' => 'chlodzenie_olejem',
                'name' => 'Chlodzony olejem',
                'value_type' => 'bool',
                'unit' => null,
                'feature_group_id' => $groups['spalinowe'],
                'group' => 'SPALINOWE',
                'input_placeholder' => null,
                'excel_column' => 'AV',
                'prestashop_name' => 'Chlodzony olejem',
                'conditional_group' => 'spalinowe',
                'is_active' => true,
            ],
            [
                'code' => 'gaznik_marka',
                'name' => 'Marka gaznika',
                'value_type' => 'text',
                'unit' => null,
                'feature_group_id' => $groups['spalinowe'],
                'group' => 'SPALINOWE',
                'input_placeholder' => 'np. MIKUNI, KEIHIN, PWK',
                'excel_column' => 'AX',
                'prestashop_name' => 'Marka gaznika',
                'conditional_group' => 'spalinowe',
                'is_active' => true,
            ],
            [
                'code' => 'gaznik_model',
                'name' => 'Model gaznika',
                'value_type' => 'text',
                'unit' => null,
                'feature_group_id' => $groups['spalinowe'],
                'group' => 'SPALINOWE',
                'input_placeholder' => 'np. VM22, PE24, PWK28',
                'excel_column' => 'AY',
                'prestashop_name' => 'Model gaznika',
                'conditional_group' => 'spalinowe',
                'is_active' => true,
            ],
            [
                'code' => 'zbiornik_pojemnosc',
                'name' => 'Pojemnosc zbiornika',
                'value_type' => 'number',
                'unit' => 'L',
                'feature_group_id' => $groups['spalinowe'],
                'group' => 'SPALINOWE',
                'input_placeholder' => 'np. 3, 4.5, 6',
                'excel_column' => 'CH',
                'prestashop_name' => 'Pojemnosc zbiornika',
                'conditional_group' => 'spalinowe',
                'is_active' => true,
            ],
            [
                'code' => 'rozrusznik_nozny',
                'name' => 'Rozrusznik nozny',
                'value_type' => 'bool',
                'unit' => null,
                'feature_group_id' => $groups['spalinowe'],
                'group' => 'SPALINOWE',
                'input_placeholder' => null,
                'excel_column' => 'AB',
                'prestashop_name' => 'Rozrusznik nozny',
                'conditional_group' => 'spalinowe',
                'is_active' => true,
            ],
            [
                'code' => 'rozrusznik_elektryczny',
                'name' => 'Rozrusznik elektryczny',
                'value_type' => 'bool',
                'unit' => null,
                'feature_group_id' => $groups['spalinowe'],
                'group' => 'SPALINOWE',
                'input_placeholder' => null,
                'excel_column' => 'AC',
                'prestashop_name' => 'Rozrusznik elektryczny',
                'conditional_group' => 'spalinowe',
                'is_active' => true,
            ],
            [
                'code' => 'olej_silnikowy',
                'name' => 'Dedykowany olej silnikowy',
                'value_type' => 'text',
                'unit' => null,
                'feature_group_id' => $groups['spalinowe'],
                'group' => 'SPALINOWE',
                'input_placeholder' => 'np. 10W40, 15W50',
                'excel_column' => 'CS',
                'prestashop_name' => 'Olej silnikowy',
                'conditional_group' => 'spalinowe',
                'is_active' => true,
            ],
        ];
    }

    /**
     * DOKUMENTACJA - col CX-DI
     */
    private function getDokumentacjaFeatures(array $groups): array
    {
        return [
            [
                'code' => 'instrukcja_en',
                'name' => 'Instrukcja EN',
                'value_type' => 'text',
                'unit' => null,
                'feature_group_id' => $groups['dokumentacja'],
                'group' => 'DOKUMENTACJA',
                'input_placeholder' => 'URL do instrukcji EN',
                'excel_column' => 'CY',
                'prestashop_name' => 'Instrukcja EN',
                'is_active' => true,
            ],
            [
                'code' => 'instrukcja_pl',
                'name' => 'Instrukcja PL',
                'value_type' => 'text',
                'unit' => null,
                'feature_group_id' => $groups['dokumentacja'],
                'group' => 'DOKUMENTACJA',
                'input_placeholder' => 'URL do instrukcji PL',
                'excel_column' => 'CZ',
                'prestashop_name' => 'Instrukcja PL',
                'is_active' => true,
            ],
            [
                'code' => 'service_manual',
                'name' => 'Service Manual',
                'value_type' => 'text',
                'unit' => null,
                'feature_group_id' => $groups['dokumentacja'],
                'group' => 'DOKUMENTACJA',
                'input_placeholder' => 'URL do service manual',
                'excel_column' => 'DA',
                'prestashop_name' => 'Service Manual',
                'is_active' => true,
            ],
            [
                'code' => 'katalog_czesci_fabryka',
                'name' => 'Katalog czesci (fabryka)',
                'value_type' => 'text',
                'unit' => null,
                'feature_group_id' => $groups['dokumentacja'],
                'group' => 'DOKUMENTACJA',
                'input_placeholder' => 'URL do katalogu',
                'excel_column' => 'DC',
                'prestashop_name' => 'Katalog czesci fabryka',
                'is_active' => true,
            ],
            [
                'code' => 'katalog_czesci_mpp',
                'name' => 'Katalog czesci (MPP)',
                'value_type' => 'text',
                'unit' => null,
                'feature_group_id' => $groups['dokumentacja'],
                'group' => 'DOKUMENTACJA',
                'input_placeholder' => 'URL do katalogu MPP',
                'excel_column' => 'DD',
                'prestashop_name' => 'Katalog czesci MPP',
                'is_active' => true,
            ],
        ];
    }

    /**
     * INNE - col Z, AA, AM-AN, CI, CT, CV
     */
    private function getInneFeatures(array $groups): array
    {
        return [
            [
                'code' => 'stopka_boczna',
                'name' => 'Stopka boczna',
                'value_type' => 'bool',
                'unit' => null,
                'feature_group_id' => $groups['inne'],
                'group' => 'INNE',
                'input_placeholder' => null,
                'excel_column' => 'Z',
                'prestashop_name' => 'Stopka boczna',
                'is_active' => true,
            ],
            [
                'code' => 'stojak_w_zestawie',
                'name' => 'Stojak w zestawie',
                'value_type' => 'bool',
                'unit' => null,
                'feature_group_id' => $groups['inne'],
                'group' => 'INNE',
                'input_placeholder' => null,
                'excel_column' => 'AA',
                'prestashop_name' => 'Stojak w zestawie',
                'is_active' => true,
            ],
            [
                'code' => 'wiek_minimalny',
                'name' => 'Zalecany wiek minimalny',
                'value_type' => 'number',
                'unit' => 'lat',
                'feature_group_id' => $groups['inne'],
                'group' => 'INNE',
                'input_placeholder' => 'np. 3, 5, 8, 14',
                'excel_column' => 'CI',
                'prestashop_name' => 'Wiek minimalny',
                'is_active' => true,
            ],
            [
                'code' => 'max_waga_uzytkownika',
                'name' => 'Maksymalna waga uzytkownika',
                'value_type' => 'number',
                'unit' => 'kg',
                'feature_group_id' => $groups['inne'],
                'group' => 'INNE',
                'input_placeholder' => 'np. 40, 60, 100, 130',
                'excel_column' => 'CT',
                'prestashop_name' => 'Max waga uzytkownika',
                'is_active' => true,
            ],
            [
                'code' => 'okres_gwarancji',
                'name' => 'Okres gwarancji',
                'value_type' => 'select',
                'unit' => null,
                'feature_group_id' => $groups['inne'],
                'group' => 'INNE',
                'input_placeholder' => 'Wybierz okres',
                'excel_column' => 'CR',
                'prestashop_name' => 'Gwarancja',
                'is_active' => true,
            ],
            [
                'code' => 'mozliwosc_wieksze_kola',
                'name' => 'Mozliwosc montazu wiekszych kol',
                'value_type' => 'bool',
                'unit' => null,
                'feature_group_id' => $groups['inne'],
                'group' => 'INNE',
                'input_placeholder' => null,
                'excel_column' => 'AM',
                'prestashop_name' => 'Mozliwosc wiekszych kol',
                'is_active' => true,
            ],
        ];
    }
}
