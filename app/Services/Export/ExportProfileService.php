<?php

namespace App\Services\Export;

use App\Models\ExportProfile;
use App\Models\PriceGroup;
use App\Models\Warehouse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

/**
 * ExportProfileService - CRUD, field definitions, filters, format options for export profiles.
 */
class ExportProfileService
{
    public function create(array $data, int $userId): ExportProfile
    {
        $data['created_by'] = $userId;
        $data['updated_by'] = $userId;

        return ExportProfile::create($data);
    }

    public function update(ExportProfile $profile, array $data, int $userId): ExportProfile
    {
        $data['updated_by'] = $userId;
        $profile->update($data);

        return $profile->fresh();
    }

    public function delete(ExportProfile $profile): bool
    {
        if ($profile->file_path && Storage::exists($profile->file_path)) {
            Storage::delete($profile->file_path);
        }

        return (bool) $profile->delete();
    }

    public function duplicate(ExportProfile $profile, int $userId): ExportProfile
    {
        $newProfile = $profile->replicate([
            'token', 'slug', 'file_path', 'file_size',
            'product_count', 'generation_duration',
            'last_generated_at', 'next_generation_at',
        ]);

        $newProfile->name = $profile->name . ' (kopia)';
        $newProfile->token = Str::random(64);
        $newProfile->file_path = null;
        $newProfile->file_size = null;
        $newProfile->product_count = null;
        $newProfile->generation_duration = null;
        $newProfile->last_generated_at = null;
        $newProfile->next_generation_at = null;
        $newProfile->created_by = $userId;
        $newProfile->updated_by = $userId;
        $newProfile->save();

        return $newProfile;
    }

    public function regenerateToken(ExportProfile $profile): ExportProfile
    {
        $profile->update(['token' => Str::random(64)]);

        return $profile->fresh();
    }

    /**
     * Pogrupowana lista dostepnych pol (pricing/stock dynamicznie z DB).
     *
     * @return array<string, array{label: string, fields: array}>
     */
    public function getAvailableFields(): array
    {
        return [
            'basic' => [
                'label' => 'Podstawowe',
                'fields' => [
                    'sku'               => ['label' => 'SKU', 'type' => 'string'],
                    'name'              => ['label' => 'Nazwa', 'type' => 'string'],
                    'slug'              => ['label' => 'Slug', 'type' => 'string'],
                    'short_description' => ['label' => 'Krotki opis', 'type' => 'text'],
                    'long_description'  => ['label' => 'Dlugi opis', 'type' => 'text'],
                    'ean'               => ['label' => 'EAN', 'type' => 'string'],
                    'weight'            => ['label' => 'Waga', 'type' => 'decimal'],
                    'height'            => ['label' => 'Wysokosc', 'type' => 'decimal'],
                    'width'             => ['label' => 'Szerokosc', 'type' => 'decimal'],
                    'length'            => ['label' => 'Dlugosc', 'type' => 'decimal'],
                    'tax_rate'          => ['label' => 'Stawka VAT', 'type' => 'decimal'],
                    'manufacturer'      => ['label' => 'Producent', 'type' => 'string'],
                    'supplier_code'     => ['label' => 'Kod dostawcy', 'type' => 'string'],
                ],
            ],
            'status' => [
                'label' => 'Status',
                'fields' => [
                    'is_active'         => ['label' => 'Aktywny', 'type' => 'boolean'],
                    'is_featured'       => ['label' => 'Wyrozniony', 'type' => 'boolean'],
                    'is_variant_master' => ['label' => 'Posiada warianty', 'type' => 'boolean'],
                    'created_at'        => ['label' => 'Data utworzenia', 'type' => 'datetime'],
                    'updated_at'        => ['label' => 'Data aktualizacji', 'type' => 'datetime'],
                ],
            ],
            'seo' => [
                'label' => 'SEO',
                'fields' => [
                    'meta_title'       => ['label' => 'Meta tytul', 'type' => 'string'],
                    'meta_description' => ['label' => 'Meta opis', 'type' => 'text'],
                ],
            ],
            'pricing'    => $this->buildPricingFields(),
            'stock'      => $this->buildStockFields(),
            'categories' => [
                'label' => 'Kategorie',
                'fields' => [
                    'category_path'    => ['label' => 'Sciezka kategorii', 'type' => 'string'],
                    'category_primary' => ['label' => 'Kategoria glowna', 'type' => 'string'],
                ],
            ],
            'media' => [
                'label' => 'Media',
                'fields' => [
                    'image_url_main' => ['label' => 'Zdjecie glowne URL', 'type' => 'string'],
                    'image_urls_all' => ['label' => 'Wszystkie zdjecia URL', 'type' => 'text'],
                ],
            ],
        ];
    }

    /** @return array<string, array{label: string, type: string, options?: array}> */
    public function getAvailableFilters(): array
    {
        return [
            'is_active' => [
                'label' => 'Status aktywnosci', 'type' => 'select',
                'options' => ['' => 'Wszystkie', 'true' => 'Tylko aktywne', 'false' => 'Tylko nieaktywne'],
            ],
            'category_ids' => ['label' => 'Kategorie', 'type' => 'multiselect'],
            'manufacturer' => ['label' => 'Producent', 'type' => 'text'],
            'has_stock' => [
                'label' => 'Stan magazynowy', 'type' => 'select',
                'options' => ['' => 'Wszystkie', 'true' => 'Tylko z dostepnym stanem', 'false' => 'Tylko bez stanu'],
            ],
            'shop_ids' => ['label' => 'Sklepy PrestaShop', 'type' => 'multiselect'],
        ];
    }

    /** @return array<string, array{label: string, description: string}> */
    public function getFormatOptions(): array
    {
        return [
            'csv'            => ['label' => 'CSV', 'description' => 'Plik CSV z separatorem ; (kompatybilny z Excel)'],
            'xlsx'           => ['label' => 'XLSX', 'description' => 'Arkusz Excel z formatowaniem'],
            'json'           => ['label' => 'JSON', 'description' => 'Format JSON dla integracji API'],
            'xml_google'     => ['label' => 'XML Google Merchant', 'description' => 'Feed produktowy Google Shopping'],
            'xml_ceneo'      => ['label' => 'XML Ceneo', 'description' => 'Feed produktowy Ceneo.pl'],
            'xml_prestashop' => ['label' => 'XML PrestaShop', 'description' => 'Format importu PrestaShop'],
        ];
    }

    // === Private helpers ===

    private function buildPricingFields(): array
    {
        $fields = [];
        foreach (PriceGroup::active()->ordered()->get(['id', 'name', 'code']) as $group) {
            $fields['price_net_' . $group->code] = ['label' => 'Cena netto - ' . $group->name, 'type' => 'decimal'];
            $fields['price_gross_' . $group->code] = ['label' => 'Cena brutto - ' . $group->name, 'type' => 'decimal'];
        }

        return ['label' => 'Ceny', 'fields' => $fields];
    }

    private function buildStockFields(): array
    {
        $fields = [];
        foreach (Warehouse::active()->ordered()->get(['id', 'name', 'code']) as $wh) {
            $fields['stock_' . $wh->code] = ['label' => 'Stan - ' . $wh->name, 'type' => 'integer'];
            $fields['reserved_' . $wh->code] = ['label' => 'Rezerwacja - ' . $wh->name, 'type' => 'integer'];
        }

        return ['label' => 'Stany magazynowe', 'fields' => $fields];
    }
}
