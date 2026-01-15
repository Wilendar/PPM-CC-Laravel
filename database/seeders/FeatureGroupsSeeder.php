<?php

namespace Database\Seeders;

use App\Models\FeatureGroup;
use Illuminate\Database\Seeder;

/**
 * Feature Groups Seeder
 *
 * ETAP_07e FAZA 1.4.1 - Seeder 11 grup cech z analizy Excel
 *
 * Groups based on Excel analysis:
 * - identyfikacja: Marka, Model, Typ, SKU
 * - silnik: Pojemnosc, Moc, Typ silnika
 * - naped: Skrzynia, Zebatki, Lancuch
 * - wymiary: Dlugosc, Szerokosc, Wysokosc, Waga
 * - zawieszenie: Amortyzatory, Rama, Wahacz
 * - hamulce: Zaciski, Tarcze, Uklad
 * - kola: Felgi, Opony, Obrecze
 * - elektryczne: Napiecie, Pojemnosc baterii, Zasieg
 * - spalinowe: Gaznik, Chlodzenie, Zbiornik
 * - dokumentacja: Instrukcje, Katalogi
 * - inne: Gwarancja, Wiek, Waga uzytkownika
 */
class FeatureGroupsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            [
                'code' => 'identyfikacja',
                'name' => 'Identification',
                'name_pl' => 'Identyfikacja',
                'icon' => 'car',
                'color' => 'blue',
                'sort_order' => 1,
                'vehicle_type_filter' => null,
                'description' => 'Podstawowe dane identyfikacyjne pojazdu',
                'is_active' => true,
                'is_collapsible' => false,
            ],
            [
                'code' => 'silnik',
                'name' => 'Engine',
                'name_pl' => 'Silnik',
                'icon' => 'engine',
                'color' => 'orange',
                'sort_order' => 2,
                'vehicle_type_filter' => null,
                'description' => 'Parametry silnika i jednostki napedowej',
                'is_active' => true,
                'is_collapsible' => true,
            ],
            [
                'code' => 'naped',
                'name' => 'Drivetrain',
                'name_pl' => 'Uklad napedowy',
                'icon' => 'cog',
                'color' => 'gray',
                'sort_order' => 3,
                'vehicle_type_filter' => null,
                'description' => 'Skrzynia biegow, lancuch, zebatki',
                'is_active' => true,
                'is_collapsible' => true,
            ],
            [
                'code' => 'wymiary',
                'name' => 'Dimensions',
                'name_pl' => 'Wymiary',
                'icon' => 'ruler',
                'color' => 'cyan',
                'sort_order' => 4,
                'vehicle_type_filter' => null,
                'description' => 'Wymiary gabarytowe i masa pojazdu',
                'is_active' => true,
                'is_collapsible' => true,
            ],
            [
                'code' => 'zawieszenie',
                'name' => 'Suspension',
                'name_pl' => 'Zawieszenie',
                'icon' => 'suspension',
                'color' => 'purple',
                'sort_order' => 5,
                'vehicle_type_filter' => null,
                'description' => 'Amortyzatory, rama, wahacze',
                'is_active' => true,
                'is_collapsible' => true,
            ],
            [
                'code' => 'hamulce',
                'name' => 'Brakes',
                'name_pl' => 'Hamulce',
                'icon' => 'brake',
                'color' => 'red',
                'sort_order' => 6,
                'vehicle_type_filter' => null,
                'description' => 'Uklad hamulcowy, tarcze, zaciski',
                'is_active' => true,
                'is_collapsible' => true,
            ],
            [
                'code' => 'kola',
                'name' => 'Wheels',
                'name_pl' => 'Kola i opony',
                'icon' => 'wheel',
                'color' => 'gray',
                'sort_order' => 7,
                'vehicle_type_filter' => null,
                'description' => 'Felgi, opony, rozmiary',
                'is_active' => true,
                'is_collapsible' => true,
            ],
            [
                'code' => 'elektryczne',
                'name' => 'Electric',
                'name_pl' => 'Pojazdy elektryczne',
                'icon' => 'electric',
                'color' => 'green',
                'sort_order' => 8,
                'vehicle_type_filter' => 'elektryczne',
                'description' => 'Bateria, napiecie, zasieg - tylko dla pojazdow elektrycznych',
                'is_active' => true,
                'is_collapsible' => true,
            ],
            [
                'code' => 'spalinowe',
                'name' => 'Combustion',
                'name_pl' => 'Pojazdy spalinowe',
                'icon' => 'fuel',
                'color' => 'yellow',
                'sort_order' => 9,
                'vehicle_type_filter' => 'spalinowe',
                'description' => 'Gaznik, chlodzenie, zbiornik - tylko dla pojazdow spalinowych',
                'is_active' => true,
                'is_collapsible' => true,
            ],
            [
                'code' => 'dokumentacja',
                'name' => 'Documentation',
                'name_pl' => 'Dokumentacja',
                'icon' => 'document',
                'color' => 'blue',
                'sort_order' => 10,
                'vehicle_type_filter' => null,
                'description' => 'Instrukcje, katalogi czesci, gwarancja',
                'is_active' => true,
                'is_collapsible' => true,
            ],
            [
                'code' => 'inne',
                'name' => 'Other',
                'name_pl' => 'Inne',
                'icon' => 'info',
                'color' => 'gray',
                'sort_order' => 11,
                'vehicle_type_filter' => null,
                'description' => 'Pozostale cechy pojazdu',
                'is_active' => true,
                'is_collapsible' => true,
            ],
        ];

        foreach ($groups as $group) {
            FeatureGroup::updateOrCreate(
                ['code' => $group['code']],
                $group
            );
        }

        $this->command->info('Created ' . count($groups) . ' feature groups.');
    }
}
