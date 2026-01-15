<?php

namespace Database\Seeders;

use App\Models\FeatureType;
use App\Models\FeatureValue;
use Illuminate\Database\Seeder;

/**
 * Feature Values Seeder
 *
 * ETAP_07e FAZA 3.1 - Predefined values for select-type features
 *
 * Adds values to feature_values table for FeatureTypes with value_type='select'
 */
class FeatureValuesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $valueMappings = $this->getValueMappings();

        $totalCreated = 0;

        foreach ($valueMappings as $featureCode => $values) {
            $featureType = FeatureType::where('code', $featureCode)->first();

            if (!$featureType) {
                $this->command->warn("Feature type '{$featureCode}' not found, skipping.");
                continue;
            }

            if ($featureType->value_type !== 'select') {
                $this->command->warn("Feature type '{$featureCode}' is not 'select' type, skipping.");
                continue;
            }

            $position = 0;
            foreach ($values as $value => $displayValue) {
                $position++;

                FeatureValue::updateOrCreate(
                    [
                        'feature_type_id' => $featureType->id,
                        'value' => $value,
                    ],
                    [
                        'display_value' => $displayValue ?? $value,
                        'position' => $position,
                        'is_active' => true,
                    ]
                );

                $totalCreated++;
            }

            $this->command->info("Added " . count($values) . " values for '{$featureType->name}'");
        }

        $this->command->info("Total feature values created/updated: {$totalCreated}");
    }

    /**
     * Get all value mappings for select-type features
     *
     * Format: 'feature_code' => ['value' => 'display_value']
     */
    private function getValueMappings(): array
    {
        return [
            // IDENTYFIKACJA
            'marka' => [
                'YCF' => 'YCF',
                'KAYO' => 'KAYO',
                'MRF' => 'MRF',
                'GPX' => 'GPX',
                'PITBULL' => 'PITBULL',
                'BENZER' => 'BENZER',
                'STOMP' => 'STOMP',
                'ORION' => 'ORION',
                'BSE' => 'BSE',
                'APOLLO' => 'APOLLO',
                'MALCOR' => 'MALCOR',
                'CRF' => 'CRF',
                'KTM' => 'KTM',
                'HUSQVARNA' => 'HUSQVARNA',
                'IMR' => 'IMR',
                'INCA' => 'INCA',
                'KAYOMOTO' => 'KAYOMOTO',
                'INNA' => 'Inna marka',
            ],

            'typ_pojazdu' => [
                'MINI_CROSS' => 'Mini Cross',
                'PIT_BIKE' => 'Pit Bike',
                'MOTOCYKL_CROSSOWY' => 'Motocykl Crossowy',
                'ENDURO' => 'Enduro',
                'SUPERMOTO' => 'Supermoto',
                'QUAD' => 'Quad',
                'QUAD_SPORTOWY' => 'Quad Sportowy',
                'QUAD_DZIECIECY' => 'Quad Dzieciecy',
                'ELEKTRYCZNY_PITBIKE' => 'Elektryczny Pit Bike',
                'ELEKTRYCZNY_QUAD' => 'Elektryczny Quad',
                'MOTOR_ELEKTRYCZNY_DZIECIECY' => 'Motor Elektryczny Dzieciecy',
                'BUGGY' => 'Buggy',
                'UTV' => 'UTV',
                'INNE' => 'Inne',
            ],

            'grupa_pojazdu' => [
                'SPALINOWE' => 'Pojazdy spalinowe',
                'ELEKTRYCZNE' => 'Pojazdy elektryczne',
                'HYBRYDOWE' => 'Pojazdy hybrydowe',
            ],

            // SILNIK
            'typ_silnika' => [
                '2T' => '2-suw (2T)',
                '4T' => '4-suw (4T)',
                'ELEKTRYCZNY' => 'Elektryczny',
                '2T_CHLODZONY_CIECZA' => '2-suw chlodzony ciecza',
                '4T_CHLODZONY_CIECZA' => '4-suw chlodzony ciecza',
                '4T_CHLODZONY_POWIETRZEM' => '4-suw chlodzony powietrzem',
            ],

            // NAPED
            'rodzaj_skrzyni' => [
                'MANUALNA' => 'Manualna',
                'AUTOMATYCZNA' => 'Automatyczna',
                'CVT' => 'CVT (bezstopniowa)',
                'POLAUTOMATYCZNA' => 'Polautomatyczna',
            ],

            // ZAWIESZENIE
            'rama_material' => [
                'STALOWA' => 'Rama stalowa',
                'ALUMINIOWA' => 'Rama aluminiowa',
                'CHROMOLOWA' => 'Rama chromolowa',
            ],

            'wahacz_tyl_material' => [
                'STALOWY' => 'Wahacz stalowy',
                'ALUMINIOWY' => 'Wahacz aluminiowy',
            ],

            // HAMULCE
            'uklad_hamulcowy' => [
                'TARCZOWY_HYDRAULICZNY' => 'Tarczowy hydrauliczny',
                'TARCZOWY_MECHANICZNY' => 'Tarczowy mechaniczny (na linke)',
                'BEBNOWY' => 'Bebnowy',
                'TARCZOWY_PRZOD_BEBNOWY_TYL' => 'Tarczowy przod / Bebnowy tyl',
            ],

            // KOLA
            'obrecze_material' => [
                'STALOWE' => 'Obrecze stalowe',
                'ALUMINIOWE' => 'Obrecze aluminiowe',
            ],

            // ELEKTRYCZNE
            'typ_baterii' => [
                'LEAD_ACID' => 'Kwasowo-olowiowy (SLA)',
                'LITHIUM_ION' => 'Litowo-jonowy (Li-Ion)',
                'LIFEPO4' => 'LiFePO4 (zelazofosforanowy)',
                'NIMH' => 'Niklowo-wodorkowy (NiMH)',
            ],

            // INNE
            'okres_gwarancji' => [
                '6M' => '6 miesiecy',
                '12M' => '12 miesiecy',
                '24M' => '24 miesiace',
                '36M' => '36 miesiecy',
                'LIFETIME' => 'Dozywotnia',
            ],
        ];
    }
}
