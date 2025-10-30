<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

/**
 * Variant Management Controller
 *
 * Thin controller for /admin/variants management page.
 * All business logic delegated to VariantManager service and Livewire components.
 *
 * COMPLIANCE:
 * - Laravel 12.x Controller patterns (Context7 verified)
 * - Thin controller philosophy (NO business logic)
 * - PPM Architecture: _DOCS/ARCHITEKTURA_PPM/09_WARIANTY_CECHY.md
 *
 * FEATURES:
 * - Variant listing with filters (product parent, attribute type)
 * - Bulk operations (price updates, stock updates, image assignment)
 * - Auto-generate variants from attributes
 * - CSV import/export
 *
 * ROUTES:
 * - GET /admin/variants -> index() -> Livewire management page
 *
 * MIDDLEWARE:
 * - auth: Required authentication
 * - role:manager+: Admin/Manager access only
 *
 * LIVEWIRE COMPONENT:
 * - livewire:admin.variants.variant-management (handles all UI logic)
 *
 * RELATED:
 * - Service: App\Services\Product\VariantManager (business logic)
 * - Models: ProductVariant, VariantAttribute, VariantPrice, VariantStock, VariantImage
 * - Plan: Plan_Projektu/ETAP_05a_Produkty.md (FAZA 4 - UI Components)
 *
 * @package App\Http\Controllers\Admin
 * @version 1.0
 * @since ETAP_05a FAZA 4 (2025-10-23)
 */
class VariantController extends Controller
{
    /**
     * Display variant management page
     *
     * Returns view with Livewire component for variant CRUD operations.
     * All business logic handled by VariantManager service via Livewire.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin.variants.index');
    }
}
