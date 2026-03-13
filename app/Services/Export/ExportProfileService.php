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
                    'status_name'       => ['label' => 'Status produktu', 'type' => 'string'],
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
            'compatibility' => [
                'label' => 'Dopasowania pojazdow',
                'fields' => [
                    'compatible_vehicles'       => ['label' => 'Kompatybilne pojazdy (lista)', 'type' => 'text'],
                    'compatible_vehicles_count' => ['label' => 'Liczba kompatybilnych pojazdow', 'type' => 'integer'],
                    'compatibility_types'       => ['label' => 'Typy kompatybilnosci', 'type' => 'string'],
                    'compatibility_full'        => ['label' => 'Pelne dane kompatybilnosci', 'type' => 'text'],
                ],
            ],
            'locations' => $this->buildLocationFields(),
            'partners' => [
                'label' => 'Partnerzy biznesowi',
                'fields' => [
                    'manufacturer_name' => ['label' => 'Nazwa producenta (relacja)', 'type' => 'string'],
                    'supplier_name'     => ['label' => 'Dostawca', 'type' => 'string'],
                    'importer_name'     => ['label' => 'Importer', 'type' => 'string'],
                    'product_type_name' => ['label' => 'Typ produktu', 'type' => 'string'],
                ],
            ],
            'features' => [
                'label' => 'Cechy produktu',
                'fields' => [
                    'features_list'  => ['label' => 'Cechy produktu (lista)', 'type' => 'text'],
                    'feature_groups' => ['label' => 'Grupy cech', 'type' => 'string'],
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
            'category_ids'     => ['label' => 'Kategorie', 'type' => 'multiselect'],
            'manufacturer_ids' => ['label' => 'Producent', 'type' => 'multiselect'],
            'supplier_ids'     => ['label' => 'Dostawca', 'type' => 'multiselect'],
            'product_type_id'  => ['label' => 'Typ produktu', 'type' => 'select'],
            'stock_status' => [
                'label' => 'Stan magazynowy', 'type' => 'select',
                'options' => ['' => 'Wszystkie', 'in_stock' => 'Na stanie', 'low_stock' => 'Niski stan', 'out_of_stock' => 'Brak na stanie'],
            ],
            'warehouse_ids'    => ['label' => 'Magazyny', 'type' => 'multiselect'],
            'price_min'        => ['label' => 'Cena minimalna', 'type' => 'number'],
            'price_max'        => ['label' => 'Cena maksymalna', 'type' => 'number'],
            'price_group_id'   => ['label' => 'Grupa cenowa', 'type' => 'select'],
            'erp_connection_ids' => ['label' => 'Integracje ERP', 'type' => 'multiselect'],
            'shop_ids'         => ['label' => 'Sklepy PrestaShop', 'type' => 'multiselect'],
            'date_from'        => ['label' => 'Data od', 'type' => 'date'],
            'date_to'          => ['label' => 'Data do', 'type' => 'date'],
            'date_type' => [
                'label' => 'Typ daty', 'type' => 'select',
                'options' => ['created_at' => 'Data utworzenia', 'updated_at' => 'Data aktualizacji'],
            ],
            'media_filter' => [
                'label' => 'Media', 'type' => 'select',
                'options' => ['' => 'Wszystkie', 'with_images' => 'Ze zdjeciami', 'without_images' => 'Bez zdjec'],
            ],
            'has_compatibility' => [
                'label' => 'Dopasowania', 'type' => 'select',
                'options' => ['' => 'Wszystkie', 'with' => 'Z dopasowaniami', 'without' => 'Bez dopasowan'],
            ],
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

    private function buildLocationFields(): array
    {
        $fields = [];
        foreach (Warehouse::active()->ordered()->get(['id', 'name', 'code']) as $wh) {
            $fields['warehouse_location_' . $wh->code] = ['label' => 'Lokalizacja - ' . $wh->name, 'type' => 'string'];
        }

        return ['label' => 'Lokalizacje magazynowe', 'fields' => $fields];
    }
}
