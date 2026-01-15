<?php

namespace Database\Seeders;

use App\Models\FeatureTemplate;
use App\Models\FeatureType;
use Illuminate\Database\Seeder;

/**
 * Vehicle Templates Seeder
 *
 * ETAP_07e FAZA 1.4.3 - Seeder 4 szablonow pojazdow
 *
 * Templates:
 * 1. Pit Bike Spalinowy (~45 features)
 * 2. Pit Bike Elektryczny (~35 features)
 * 3. Quad Spalinowy (~50 features)
 * 4. Buggy Elektryczny (~30 features)
 */
class VehicleTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure features exist
        $this->call(VehicleFeaturesSeeder::class);

        // Get feature types by code
        $features = FeatureType::pluck('id', 'code')->toArray();

        $templates = [
            $this->getPitBikeSpalinyTemplate($features),
            $this->getPitBikeElektrycznyTemplate($features),
            $this->getQuadSpalinyTemplate($features),
            $this->getBuggyElektrycznyTemplate($features),
        ];

        foreach ($templates as $template) {
            FeatureTemplate::updateOrCreate(
                ['name' => $template['name']],
                $template
            );
        }

        $this->command->info('Created ' . count($templates) . ' vehicle templates.');
    }

    /**
     * Pit Bike Spalinowy - ~45 cech
     */
    private function getPitBikeSpalinyTemplate(array $features): array
    {
        return [
            'name' => 'Pit Bike Spalinowy',
            'description' => 'Kompletny szablon dla pit bike-ow z silnikiem spalinowym',
            'is_predefined' => true,
            'is_active' => true,
            'features' => $this->buildFeatureList([
                // IDENTYFIKACJA
                ['code' => 'marka', 'required' => true],
                ['code' => 'model', 'required' => true],
                ['code' => 'typ_pojazdu', 'required' => true, 'default' => 'Pit Bike'],
                ['code' => 'grupa_pojazdu', 'required' => true, 'default' => 'Spalinowe'],

                // SILNIK
                ['code' => 'pojemnosc_silnika', 'required' => true],
                ['code' => 'moc_km', 'required' => false],
                ['code' => 'ilosc_oleju_silnik', 'required' => false],
                ['code' => 'oznaczenie_silnika', 'required' => false],
                ['code' => 'typ_silnika', 'required' => false],
                ['code' => 'liczba_zaworow', 'required' => false],
                ['code' => 'stopien_sprezania', 'required' => false],

                // NAPED
                ['code' => 'skrzynia_biegow', 'required' => true],
                ['code' => 'bieg_wsteczny', 'required' => false, 'default' => 'Nie'],
                ['code' => 'rodzaj_skrzyni', 'required' => false],
                ['code' => 'uklad_biegow', 'required' => false],
                ['code' => 'zebatka_przod', 'required' => false],
                ['code' => 'zebatka_tyl', 'required' => false],
                ['code' => 'lancuch_rozmiar', 'required' => false],

                // WYMIARY
                ['code' => 'dlugosc', 'required' => false],
                ['code' => 'szerokosc', 'required' => false],
                ['code' => 'wysokosc', 'required' => false],
                ['code' => 'wysokosc_siedzenia', 'required' => true],
                ['code' => 'przeswit', 'required' => false],
                ['code' => 'rozstaw_osi', 'required' => false],
                ['code' => 'waga', 'required' => true],

                // ZAWIESZENIE
                ['code' => 'amortyzator_przod_marka', 'required' => false],
                ['code' => 'amortyzator_przod_dlugosc', 'required' => false],
                ['code' => 'amortyzator_przod_regulacja_com', 'required' => false],
                ['code' => 'amortyzator_przod_regulacja_reb', 'required' => false],
                ['code' => 'amortyzator_tyl_marka', 'required' => false],
                ['code' => 'amortyzator_tyl_dlugosc', 'required' => false],
                ['code' => 'rama_material', 'required' => false],
                ['code' => 'wahacz_tyl_material', 'required' => false],
                ['code' => 'wahacz_dlugosc', 'required' => false],

                // HAMULCE
                ['code' => 'uklad_hamulcowy', 'required' => false],
                ['code' => 'zacisk_przod', 'required' => false],
                ['code' => 'zacisk_tyl', 'required' => false],
                ['code' => 'tarcza_przod', 'required' => false],
                ['code' => 'tarcza_tyl', 'required' => false],

                // KOLA
                ['code' => 'felga_przod', 'required' => true],
                ['code' => 'felga_tyl', 'required' => true],
                ['code' => 'opona_przod', 'required' => false],
                ['code' => 'opona_tyl', 'required' => false],
                ['code' => 'obrecze_material', 'required' => false],

                // SPALINOWE
                ['code' => 'chlodzenie_powietrzem', 'required' => false],
                ['code' => 'chlodzenie_ciecza', 'required' => false],
                ['code' => 'chlodzenie_olejem', 'required' => false],
                ['code' => 'gaznik_marka', 'required' => false],
                ['code' => 'gaznik_model', 'required' => false],
                ['code' => 'zbiornik_pojemnosc', 'required' => false],
                ['code' => 'rozrusznik_nozny', 'required' => false],
                ['code' => 'rozrusznik_elektryczny', 'required' => false],
                ['code' => 'olej_silnikowy', 'required' => false],

                // INNE
                ['code' => 'stopka_boczna', 'required' => false],
                ['code' => 'wiek_minimalny', 'required' => false],
                ['code' => 'max_waga_uzytkownika', 'required' => false],
                ['code' => 'okres_gwarancji', 'required' => false],
            ], $features),
        ];
    }

    /**
     * Pit Bike Elektryczny - ~35 cech
     */
    private function getPitBikeElektrycznyTemplate(array $features): array
    {
        return [
            'name' => 'Pit Bike Elektryczny',
            'description' => 'Kompletny szablon dla pit bike-ow z silnikiem elektrycznym',
            'is_predefined' => true,
            'is_active' => true,
            'features' => $this->buildFeatureList([
                // IDENTYFIKACJA
                ['code' => 'marka', 'required' => true],
                ['code' => 'model', 'required' => true],
                ['code' => 'typ_pojazdu', 'required' => true, 'default' => 'Pit Bike'],
                ['code' => 'grupa_pojazdu', 'required' => true, 'default' => 'Elektryczne'],

                // SILNIK
                ['code' => 'moc_w', 'required' => true],

                // WYMIARY
                ['code' => 'dlugosc', 'required' => false],
                ['code' => 'szerokosc', 'required' => false],
                ['code' => 'wysokosc', 'required' => false],
                ['code' => 'wysokosc_siedzenia', 'required' => true],
                ['code' => 'przeswit', 'required' => false],
                ['code' => 'rozstaw_osi', 'required' => false],
                ['code' => 'waga', 'required' => true],

                // ZAWIESZENIE
                ['code' => 'amortyzator_przod_marka', 'required' => false],
                ['code' => 'amortyzator_przod_dlugosc', 'required' => false],
                ['code' => 'amortyzator_tyl_marka', 'required' => false],
                ['code' => 'amortyzator_tyl_dlugosc', 'required' => false],
                ['code' => 'rama_material', 'required' => false],
                ['code' => 'wahacz_tyl_material', 'required' => false],

                // HAMULCE
                ['code' => 'uklad_hamulcowy', 'required' => false],
                ['code' => 'zacisk_przod', 'required' => false],
                ['code' => 'zacisk_tyl', 'required' => false],
                ['code' => 'tarcza_przod', 'required' => false],
                ['code' => 'tarcza_tyl', 'required' => false],

                // KOLA
                ['code' => 'felga_przod', 'required' => true],
                ['code' => 'felga_tyl', 'required' => true],
                ['code' => 'opona_przod', 'required' => false],
                ['code' => 'opona_tyl', 'required' => false],

                // ELEKTRYCZNE
                ['code' => 'tryby_predkosci', 'required' => false],
                ['code' => 'napiecie', 'required' => true],
                ['code' => 'pojemnosc_baterii', 'required' => true],
                ['code' => 'typ_baterii', 'required' => false],
                ['code' => 'zasieg', 'required' => false],

                // INNE
                ['code' => 'stopka_boczna', 'required' => false],
                ['code' => 'wiek_minimalny', 'required' => false],
                ['code' => 'max_waga_uzytkownika', 'required' => false],
                ['code' => 'okres_gwarancji', 'required' => false],
            ], $features),
        ];
    }

    /**
     * Quad Spalinowy - ~50 cech
     */
    private function getQuadSpalinyTemplate(array $features): array
    {
        return [
            'name' => 'Quad Spalinowy',
            'description' => 'Kompletny szablon dla quadow z silnikiem spalinowym',
            'is_predefined' => true,
            'is_active' => true,
            'features' => $this->buildFeatureList([
                // IDENTYFIKACJA
                ['code' => 'marka', 'required' => true],
                ['code' => 'model', 'required' => true],
                ['code' => 'typ_pojazdu', 'required' => true, 'default' => 'Quad'],
                ['code' => 'grupa_pojazdu', 'required' => true, 'default' => 'Spalinowe'],

                // SILNIK
                ['code' => 'pojemnosc_silnika', 'required' => true],
                ['code' => 'moc_km', 'required' => false],
                ['code' => 'ilosc_oleju_silnik', 'required' => false],
                ['code' => 'oznaczenie_silnika', 'required' => false],
                ['code' => 'typ_silnika', 'required' => false],
                ['code' => 'liczba_zaworow', 'required' => false],
                ['code' => 'stopien_sprezania', 'required' => false],

                // NAPED
                ['code' => 'skrzynia_biegow', 'required' => true],
                ['code' => 'bieg_wsteczny', 'required' => true],
                ['code' => 'rodzaj_skrzyni', 'required' => false],
                ['code' => 'uklad_biegow', 'required' => false],
                ['code' => 'zebatka_przod', 'required' => false],
                ['code' => 'zebatka_tyl', 'required' => false],
                ['code' => 'lancuch_rozmiar', 'required' => false],

                // WYMIARY
                ['code' => 'dlugosc', 'required' => true],
                ['code' => 'szerokosc', 'required' => true],
                ['code' => 'wysokosc', 'required' => false],
                ['code' => 'wysokosc_siedzenia', 'required' => true],
                ['code' => 'przeswit', 'required' => false],
                ['code' => 'rozstaw_osi', 'required' => false],
                ['code' => 'waga', 'required' => true],

                // ZAWIESZENIE
                ['code' => 'amortyzator_przod_marka', 'required' => false],
                ['code' => 'amortyzator_przod_dlugosc', 'required' => false],
                ['code' => 'amortyzator_przod_regulacja_com', 'required' => false],
                ['code' => 'amortyzator_przod_regulacja_reb', 'required' => false],
                ['code' => 'amortyzator_tyl_marka', 'required' => false],
                ['code' => 'amortyzator_tyl_dlugosc', 'required' => false],
                ['code' => 'rama_material', 'required' => false],
                ['code' => 'wahacz_tyl_material', 'required' => false],
                ['code' => 'wahacz_dlugosc', 'required' => false],

                // HAMULCE
                ['code' => 'uklad_hamulcowy', 'required' => true],
                ['code' => 'zacisk_przod', 'required' => false],
                ['code' => 'zacisk_tyl', 'required' => false],
                ['code' => 'tarcza_przod', 'required' => false],
                ['code' => 'tarcza_tyl', 'required' => false],

                // KOLA
                ['code' => 'felga_przod', 'required' => true],
                ['code' => 'felga_tyl', 'required' => true],
                ['code' => 'opona_przod', 'required' => false],
                ['code' => 'opona_tyl', 'required' => false],
                ['code' => 'obrecze_material', 'required' => false],

                // SPALINOWE
                ['code' => 'chlodzenie_powietrzem', 'required' => false],
                ['code' => 'chlodzenie_ciecza', 'required' => false],
                ['code' => 'chlodzenie_olejem', 'required' => false],
                ['code' => 'gaznik_marka', 'required' => false],
                ['code' => 'gaznik_model', 'required' => false],
                ['code' => 'zbiornik_pojemnosc', 'required' => true],
                ['code' => 'rozrusznik_nozny', 'required' => false],
                ['code' => 'rozrusznik_elektryczny', 'required' => false],
                ['code' => 'olej_silnikowy', 'required' => false],

                // INNE
                ['code' => 'stopka_boczna', 'required' => false],
                ['code' => 'stojak_w_zestawie', 'required' => false],
                ['code' => 'wiek_minimalny', 'required' => false],
                ['code' => 'max_waga_uzytkownika', 'required' => true],
                ['code' => 'okres_gwarancji', 'required' => false],
            ], $features),
        ];
    }

    /**
     * Buggy Elektryczny - ~30 cech
     */
    private function getBuggyElektrycznyTemplate(array $features): array
    {
        return [
            'name' => 'Buggy Elektryczny',
            'description' => 'Kompletny szablon dla buggy z silnikiem elektrycznym',
            'is_predefined' => true,
            'is_active' => true,
            'features' => $this->buildFeatureList([
                // IDENTYFIKACJA
                ['code' => 'marka', 'required' => true],
                ['code' => 'model', 'required' => true],
                ['code' => 'typ_pojazdu', 'required' => true, 'default' => 'Buggy'],
                ['code' => 'grupa_pojazdu', 'required' => true, 'default' => 'Elektryczne'],

                // SILNIK
                ['code' => 'moc_w', 'required' => true],

                // WYMIARY
                ['code' => 'dlugosc', 'required' => true],
                ['code' => 'szerokosc', 'required' => true],
                ['code' => 'wysokosc', 'required' => false],
                ['code' => 'wysokosc_siedzenia', 'required' => false],
                ['code' => 'przeswit', 'required' => false],
                ['code' => 'rozstaw_osi', 'required' => false],
                ['code' => 'waga', 'required' => true],

                // ZAWIESZENIE
                ['code' => 'amortyzator_przod_marka', 'required' => false],
                ['code' => 'amortyzator_tyl_marka', 'required' => false],
                ['code' => 'rama_material', 'required' => false],

                // HAMULCE
                ['code' => 'uklad_hamulcowy', 'required' => true],
                ['code' => 'tarcza_przod', 'required' => false],
                ['code' => 'tarcza_tyl', 'required' => false],

                // KOLA
                ['code' => 'felga_przod', 'required' => true],
                ['code' => 'felga_tyl', 'required' => true],
                ['code' => 'opona_przod', 'required' => false],
                ['code' => 'opona_tyl', 'required' => false],

                // ELEKTRYCZNE
                ['code' => 'tryby_predkosci', 'required' => false],
                ['code' => 'napiecie', 'required' => true],
                ['code' => 'pojemnosc_baterii', 'required' => true],
                ['code' => 'typ_baterii', 'required' => false],
                ['code' => 'zasieg', 'required' => false],

                // INNE
                ['code' => 'wiek_minimalny', 'required' => true],
                ['code' => 'max_waga_uzytkownika', 'required' => true],
                ['code' => 'okres_gwarancji', 'required' => false],
            ], $features),
        ];
    }

    /**
     * Build feature list with FeatureType data
     */
    private function buildFeatureList(array $featureDefs, array $featureIds): array
    {
        $result = [];

        foreach ($featureDefs as $def) {
            $code = $def['code'];

            if (!isset($featureIds[$code])) {
                $this->command->warn("Feature code not found: {$code}");
                continue;
            }

            // Get FeatureType for additional info
            $featureType = FeatureType::where('code', $code)->first();

            $result[] = [
                'feature_type_id' => $featureIds[$code],
                'code' => $code,
                'name' => $featureType?->name ?? $code,
                'type' => $featureType?->value_type ?? 'text',
                'unit' => $featureType?->unit,
                'required' => $def['required'] ?? false,
                'default' => $def['default'] ?? null,
            ];
        }

        return $result;
    }
}
