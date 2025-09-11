<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductAttributeSeederFixed extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * FAZA C: Media & Relations - Production Product Attributes (FIXED)
     */
    public function run(): void
    {
        // Skip if attributes already exist
        if (DB::table('product_attributes')->count() > 0) {
            return;
        }
        
        // Base attribute structure
        $baseAttribute = [
            'default_value' => null,
            'unit' => null,
            'format_pattern' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ];
        
        $attributes = [
            // 1. MODEL COMPATIBILITY (multiselect)
            array_merge($baseAttribute, [
                'name' => 'Model',
                'code' => 'model',
                'attribute_type' => 'multiselect',
                'is_required' => false,
                'is_filterable' => true,
                'is_variant_specific' => false,
                'sort_order' => 100,
                'display_group' => 'compatibility',
                'validation_rules' => json_encode(['max_selections' => 20]),
                'options' => json_encode($this->getModelOptions()),
                'help_text' => 'Wybierz modele pojazdów kompatybilne z tym produktem'
            ]),
            
            // 2. ORIGINAL OEM PART NUMBER (text)
            array_merge($baseAttribute, [
                'name' => 'Oryginał',
                'code' => 'original',
                'attribute_type' => 'text',
                'is_required' => false,
                'is_filterable' => true,
                'is_variant_specific' => true,
                'sort_order' => 200,
                'display_group' => 'compatibility',
                'validation_rules' => json_encode(['max_length' => 100]),
                'options' => null,
                'help_text' => 'Oryginalny numer części OEM'
            ]),
            
            // 3. REPLACEMENT PART NUMBER (text)
            array_merge($baseAttribute, [
                'name' => 'Zamiennik',
                'code' => 'replacement',
                'attribute_type' => 'text',
                'is_required' => false,
                'is_filterable' => true,
                'is_variant_specific' => true,
                'sort_order' => 300,
                'display_group' => 'compatibility',
                'validation_rules' => json_encode(['max_length' => 200]),
                'options' => null,
                'help_text' => 'Numery części zamiennych'
            ]),
            
            // 4. COLOR (select)
            array_merge($baseAttribute, [
                'name' => 'Kolor',
                'code' => 'color',
                'attribute_type' => 'select',
                'is_required' => false,
                'is_filterable' => true,
                'is_variant_specific' => true,
                'sort_order' => 400,
                'display_group' => 'appearance',
                'validation_rules' => null,
                'options' => json_encode($this->getColorOptions()),
                'help_text' => 'Kolor produktu'
            ]),
            
            // 5. SIZE (select)
            array_merge($baseAttribute, [
                'name' => 'Rozmiar',
                'code' => 'size',
                'attribute_type' => 'select',
                'is_required' => false,
                'is_filterable' => true,
                'is_variant_specific' => true,
                'sort_order' => 500,
                'display_group' => 'physical',
                'validation_rules' => null,
                'options' => json_encode($this->getSizeOptions()),
                'help_text' => 'Rozmiar produktu'
            ]),
            
            // 6. MATERIAL (select)
            array_merge($baseAttribute, [
                'name' => 'Materiał',
                'code' => 'material',
                'attribute_type' => 'select',
                'is_required' => false,
                'is_filterable' => true,
                'is_variant_specific' => true,
                'sort_order' => 600,
                'display_group' => 'technical',
                'validation_rules' => null,
                'options' => json_encode($this->getMaterialOptions()),
                'help_text' => 'Materiał produktu'
            ]),
            
            // 7. BRAND (text)
            array_merge($baseAttribute, [
                'name' => 'Marka',
                'code' => 'brand',
                'attribute_type' => 'text',
                'is_required' => false,
                'is_filterable' => true,
                'is_variant_specific' => false,
                'sort_order' => 700,
                'display_group' => 'general',
                'validation_rules' => json_encode(['max_length' => 100]),
                'options' => null,
                'help_text' => 'Marka/producent produktu'
            ]),
            
            // 8. WARRANTY (number)
            array_merge($baseAttribute, [
                'name' => 'Gwarancja',
                'code' => 'warranty',
                'attribute_type' => 'number',
                'is_required' => false,
                'is_filterable' => false,
                'is_variant_specific' => false,
                'sort_order' => 800,
                'display_group' => 'general',
                'validation_rules' => json_encode(['min' => 0, 'max' => 120]),
                'options' => null,
                'help_text' => 'Okres gwarancji w miesiącach',
                'unit' => 'miesięcy'
            ]),
            
            // 9. CONDITION (select)
            array_merge($baseAttribute, [
                'name' => 'Stan',
                'code' => 'condition',
                'attribute_type' => 'select',
                'is_required' => false,
                'is_filterable' => true,
                'is_variant_specific' => false,
                'sort_order' => 900,
                'display_group' => 'general',
                'validation_rules' => null,
                'options' => json_encode($this->getConditionOptions()),
                'help_text' => 'Stan produktu',
                'default_value' => 'new'
            ])
        ];
        
        // Insert all attributes
        foreach ($attributes as $attribute) {
            DB::table('product_attributes')->insert($attribute);
        }
    }
    
    private function getModelOptions(): array
    {
        return [
            // Yamaha
            ['value' => 'yamaha_yz125', 'label' => 'Yamaha YZ125'],
            ['value' => 'yamaha_yz250', 'label' => 'Yamaha YZ250'],
            ['value' => 'yamaha_yz250f', 'label' => 'Yamaha YZ250F'],
            ['value' => 'yamaha_yz450f', 'label' => 'Yamaha YZ450F'],
            ['value' => 'yamaha_wr250', 'label' => 'Yamaha WR250'],
            ['value' => 'yamaha_wr450f', 'label' => 'Yamaha WR450F'],
            
            // Honda
            ['value' => 'honda_crf250r', 'label' => 'Honda CRF250R'],
            ['value' => 'honda_crf450r', 'label' => 'Honda CRF450R'],
            ['value' => 'honda_crf250l', 'label' => 'Honda CRF250L'],
            
            // KTM
            ['value' => 'ktm_250sx', 'label' => 'KTM 250 SX'],
            ['value' => 'ktm_450sx', 'label' => 'KTM 450 SX'],
            ['value' => 'ktm_250exc', 'label' => 'KTM 250 EXC'],
            
            // Kawasaki
            ['value' => 'kawasaki_kx250', 'label' => 'Kawasaki KX250'],
            ['value' => 'kawasaki_kx450', 'label' => 'Kawasaki KX450'],
            
            // Universal
            ['value' => 'universal', 'label' => 'Uniwersalny']
        ];
    }
    
    private function getColorOptions(): array
    {
        return [
            ['value' => 'black', 'label' => 'Czarny'],
            ['value' => 'white', 'label' => 'Biały'],
            ['value' => 'red', 'label' => 'Czerwony'],
            ['value' => 'blue', 'label' => 'Niebieski'],
            ['value' => 'yellow', 'label' => 'Żółty'],
            ['value' => 'orange', 'label' => 'Pomarańczowy'],
            ['value' => 'green', 'label' => 'Zielony'],
            ['value' => 'silver', 'label' => 'Srebrny']
        ];
    }
    
    private function getSizeOptions(): array
    {
        return [
            ['value' => 'xs', 'label' => 'XS'],
            ['value' => 's', 'label' => 'S'],
            ['value' => 'm', 'label' => 'M'],
            ['value' => 'l', 'label' => 'L'],
            ['value' => 'xl', 'label' => 'XL'],
            ['value' => 'universal', 'label' => 'Uniwersalny']
        ];
    }
    
    private function getMaterialOptions(): array
    {
        return [
            ['value' => 'steel', 'label' => 'Stal'],
            ['value' => 'aluminum', 'label' => 'Aluminium'],
            ['value' => 'carbon_fiber', 'label' => 'Włókno węglowe'],
            ['value' => 'plastic', 'label' => 'Plastik'],
            ['value' => 'rubber', 'label' => 'Guma'],
            ['value' => 'leather', 'label' => 'Skóra']
        ];
    }
    
    private function getConditionOptions(): array
    {
        return [
            ['value' => 'new', 'label' => 'Nowy'],
            ['value' => 'used', 'label' => 'Używany'],
            ['value' => 'refurbished', 'label' => 'Odnowiony']
        ];
    }
};