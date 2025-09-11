<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductAttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * FAZA C: Media & Relations - Production Product Attributes
     * 
     * Seeder tworzy kluczowe atrybuty dla PPM automotive business:
     * - Model (multiselect) - compatibility z pojazdami
     * - Oryginał (text) - OEM part numbers
     * - Zamiennik (text) - aftermarket equivalents  
     * - Kolor (select) - dla odzieży i części
     * - Rozmiar (text) - dla odzieży
     * - Materiał (select) - dla części technicznych
     * 
     * Optimized dla automotive industry requirements
     */
    public function run(): void
    {
        // Timestamps dla consistency
        $now = now();
        
        $attributes = [
            // 1. MODEL COMPATIBILITY (multiselect) - Most Critical dla Automotive
            [
                'name' => 'Model',
                'code' => 'model',
                'attribute_type' => 'multiselect',
                'is_required' => false,
                'is_filterable' => true,
                'is_variant_specific' => false,
                'sort_order' => 100,
                'display_group' => 'compatibility',
                'validation_rules' => json_encode([
                    'max_selections' => 20,
                    'min_selections' => 0
                ]),
                'options' => json_encode([
                    // Yamaha Models
                    ['value' => 'yamaha_yz125', 'label' => 'Yamaha YZ125'],
                    ['value' => 'yamaha_yz250', 'label' => 'Yamaha YZ250'],
                    ['value' => 'yamaha_yz250f', 'label' => 'Yamaha YZ250F'],
                    ['value' => 'yamaha_yz450f', 'label' => 'Yamaha YZ450F'],
                    ['value' => 'yamaha_wr250', 'label' => 'Yamaha WR250'],
                    ['value' => 'yamaha_wr450f', 'label' => 'Yamaha WR450F'],
                    ['value' => 'yamaha_xt660z', 'label' => 'Yamaha XT660Z Tenere'],
                    
                    // Honda Models
                    ['value' => 'honda_crf250r', 'label' => 'Honda CRF250R'],
                    ['value' => 'honda_crf450r', 'label' => 'Honda CRF450R'],
                    ['value' => 'honda_crf250l', 'label' => 'Honda CRF250L'],
                    ['value' => 'honda_crf450l', 'label' => 'Honda CRF450L'],
                    ['value' => 'honda_xr650l', 'label' => 'Honda XR650L'],
                    
                    // KTM Models
                    ['value' => 'ktm_250sx', 'label' => 'KTM 250 SX'],
                    ['value' => 'ktm_450sx', 'label' => 'KTM 450 SX'],
                    ['value' => 'ktm_250exc', 'label' => 'KTM 250 EXC'],
                    ['value' => 'ktm_450exc', 'label' => 'KTM 450 EXC'],
                    ['value' => 'ktm_690enduro', 'label' => 'KTM 690 Enduro'],
                    
                    // Kawasaki Models
                    ['value' => 'kawasaki_kx250', 'label' => 'Kawasaki KX250'],
                    ['value' => 'kawasaki_kx450', 'label' => 'Kawasaki KX450'],
                    ['value' => 'kawasaki_klx250', 'label' => 'Kawasaki KLX250'],
                    
                    // Suzuki Models  
                    ['value' => 'suzuki_rmz250', 'label' => 'Suzuki RMZ250'],
                    ['value' => 'suzuki_rmz450', 'label' => 'Suzuki RMZ450'],
                    ['value' => 'suzuki_drz400', 'label' => 'Suzuki DRZ400'],
                    
                    // Generic/Universal
                    ['value' => 'universal', 'label' => 'Uniwersalny'],
                    ['value' => 'most_models', 'label' => 'Większość modeli']
                ]),
                'default_value' => null,
                'help_text' => 'Wybierz modele pojazdów kompatybilne z tym produktem',
                'unit' => null,
                'format_pattern' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now
            ],
            
            // 2. ORIGINAL OEM PART NUMBER (text)
            [
                'name' => 'Oryginał',
                'code' => 'original',
                'attribute_type' => 'text',
                'is_required' => false,
                'is_filterable' => true,
                'is_variant_specific' => true,
                'sort_order' => 200,
                'display_group' => 'compatibility',
                'validation_rules' => json_encode([
                    'max_length' => 100,
                    'pattern' => '/^[A-Z0-9\-_\/]+$/i'
                ]),
                'default_value' => null,
                'help_text' => 'Oryginalny numer części OEM (np. 1DX-25371-00-00)',
                'unit' => null,
                'format_pattern' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now
            ],
            
            // 3. REPLACEMENT/AFTERMARKET PART NUMBER (text)
            [
                'name' => 'Zamiennik',
                'code' => 'replacement',
                'attribute_type' => 'text',
                'is_required' => false,
                'is_filterable' => true,
                'is_variant_specific' => true,
                'sort_order' => 300,
                'display_group' => 'compatibility',
                'validation_rules' => json_encode([
                    'max_length' => 200,
                    'multiple_values' => true,
                    'separator' => ';'
                ]),
                'help_text' => 'Numery części zamiennych oddzielone średnikiem (;)',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now
            ],
            
            // 4. COLOR (select) - dla części i odzieży
            [
                'name' => 'Kolor',
                'code' => 'color',
                'attribute_type' => 'select',
                'is_required' => false,
                'is_filterable' => true,
                'is_variant_specific' => true,
                'sort_order' => 400,
                'display_group' => 'appearance',
                'options' => json_encode([
                    ['value' => 'black', 'label' => 'Czarny'],
                    ['value' => 'white', 'label' => 'Biały'],
                    ['value' => 'red', 'label' => 'Czerwony'],
                    ['value' => 'blue', 'label' => 'Niebieski'],
                    ['value' => 'yellow', 'label' => 'Żółty'],
                    ['value' => 'orange', 'label' => 'Pomarańczowy'],
                    ['value' => 'green', 'label' => 'Zielony'],
                    ['value' => 'silver', 'label' => 'Srebrny'],
                    ['value' => 'gray', 'label' => 'Szary'],
                    ['value' => 'gold', 'label' => 'Złoty'],
                    ['value' => 'carbon', 'label' => 'Carbon'],
                    ['value' => 'transparent', 'label' => 'Przezroczysty'],
                    ['value' => 'multicolor', 'label' => 'Wielokolorowy']
                ]),
                'help_text' => 'Kolor produktu',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now
            ],
            
            // 5. SIZE (text) - głównie dla odzieży
            [
                'name' => 'Rozmiar',
                'code' => 'size',
                'attribute_type' => 'select',
                'is_required' => false,
                'is_filterable' => true,
                'is_variant_specific' => true,
                'sort_order' => 500,
                'display_group' => 'physical',
                'options' => json_encode([
                    // Clothing sizes
                    ['value' => 'xs', 'label' => 'XS'],
                    ['value' => 's', 'label' => 'S'],
                    ['value' => 'm', 'label' => 'M'],
                    ['value' => 'l', 'label' => 'L'],
                    ['value' => 'xl', 'label' => 'XL'],
                    ['value' => '2xl', 'label' => 'XXL'],
                    ['value' => '3xl', 'label' => 'XXXL'],
                    
                    // Helmet sizes
                    ['value' => '54', 'label' => '54'],
                    ['value' => '56', 'label' => '56'],
                    ['value' => '58', 'label' => '58'],
                    ['value' => '60', 'label' => '60'],
                    ['value' => '62', 'label' => '62'],
                    
                    // Universal/Other
                    ['value' => 'universal', 'label' => 'Uniwersalny'],
                    ['value' => 'adjustable', 'label' => 'Regulowany']
                ]),
                'help_text' => 'Rozmiar produktu (odzież, kaski, etc.)',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now
            ],
            
            // 6. MATERIAL (select) - dla części technicznych
            [
                'name' => 'Materiał',
                'code' => 'material',
                'attribute_type' => 'select',
                'is_required' => false,
                'is_filterable' => true,
                'is_variant_specific' => true,
                'sort_order' => 600,
                'display_group' => 'technical',
                'options' => json_encode([
                    ['value' => 'steel', 'label' => 'Stal'],
                    ['value' => 'stainless_steel', 'label' => 'Stal nierdzewna'],
                    ['value' => 'aluminum', 'label' => 'Aluminium'],
                    ['value' => 'carbon_fiber', 'label' => 'Włókno węglowe'],
                    ['value' => 'plastic', 'label' => 'Plastik'],
                    ['value' => 'abs_plastic', 'label' => 'Plastik ABS'],
                    ['value' => 'polyethylene', 'label' => 'Polietylen'],
                    ['value' => 'rubber', 'label' => 'Guma'],
                    ['value' => 'silicone', 'label' => 'Silikon'],
                    ['value' => 'leather', 'label' => 'Skóra'],
                    ['value' => 'synthetic_leather', 'label' => 'Skóra syntetyczna'],
                    ['value' => 'fabric', 'label' => 'Tkanina'],
                    ['value' => 'mesh', 'label' => 'Siatka'],
                    ['value' => 'ceramic', 'label' => 'Ceramika'],
                    ['value' => 'composite', 'label' => 'Kompozyt']
                ]),
                'help_text' => 'Materiał z którego wykonany jest produkt',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now
            ],
            
            // 7. BRAND/MANUFACTURER (text)
            [
                'name' => 'Marka',
                'code' => 'brand',
                'attribute_type' => 'text',
                'is_required' => false,
                'is_filterable' => true,
                'is_variant_specific' => false,
                'sort_order' => 700,
                'display_group' => 'general',
                'validation_rules' => json_encode([
                    'max_length' => 100
                ]),
                'help_text' => 'Marka/producent produktu',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now
            ],
            
            // 8. YEAR (multiselect) - dla zgodności z rokiem produkcji
            [
                'name' => 'Rok',
                'code' => 'year',
                'attribute_type' => 'multiselect',
                'is_required' => false,
                'is_filterable' => true,
                'is_variant_specific' => false,
                'sort_order' => 800,
                'display_group' => 'compatibility',
                'validation_rules' => json_encode([
                    'max_selections' => 20
                ]),
                'options' => json_encode($this->generateYearOptions()),
                'help_text' => 'Roczniki pojazdów kompatybilne z produktem',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now
            ],
            
            // 9. WARRANTY (number) - miesiące gwarancji
            [
                'name' => 'Gwarancja',
                'code' => 'warranty',
                'attribute_type' => 'number',
                'is_required' => false,
                'is_filterable' => false,
                'is_variant_specific' => false,
                'sort_order' => 900,
                'display_group' => 'general',
                'validation_rules' => json_encode([
                    'min' => 0,
                    'max' => 120
                ]),
                'unit' => 'miesięcy',
                'help_text' => 'Okres gwarancji w miesiącach',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now
            ],
            
            // 10. CONDITION (select) - nowy, używany, odnowiony
            [
                'name' => 'Stan',
                'code' => 'condition',
                'attribute_type' => 'select',
                'is_required' => false,
                'is_filterable' => true,
                'is_variant_specific' => false,
                'sort_order' => 1000,
                'display_group' => 'general',
                'options' => json_encode([
                    ['value' => 'new', 'label' => 'Nowy'],
                    ['value' => 'used', 'label' => 'Używany'],
                    ['value' => 'refurbished', 'label' => 'Odnowiony'],
                    ['value' => 'damaged', 'label' => 'Uszkodzony']
                ]),
                'default_value' => 'new',
                'help_text' => 'Stan produktu',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now
            ]
        ];
        
        // Insert attributes using DB facade dla performance
        DB::table('product_attributes')->insert($attributes);
    }
    
    /**
     * Generate year options dla compatibility
     */
    private function generateYearOptions(): array
    {
        $years = [];
        $currentYear = date('Y');
        
        // Generate years from 1990 to current year + 2
        for ($year = 1990; $year <= ($currentYear + 2); $year++) {
            $years[] = ['value' => (string)$year, 'label' => (string)$year];
        }
        
        return $years;
    }
};