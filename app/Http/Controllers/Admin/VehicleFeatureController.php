<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

/**
 * VehicleFeatureController
 *
 * Thin controller for Vehicle Features Management page.
 *
 * RESPONSIBILITIES:
 * - Render view with Livewire component
 * - NO business logic (delegated to Livewire + FeatureManager service)
 *
 * COMPLIANCE:
 * - Laravel 12.x Controller patterns (Context7 verified)
 * - Thin controller pattern (~50 lines)
 * - NO hardcoded values
 * - Middleware handled in routes
 *
 * RELATED:
 * - Livewire: app/Http/Livewire/Admin/Features/VehicleFeatureManagement.php
 * - Service: app/Services/Product/FeatureManager.php
 * - Models: FeatureType, FeatureValue, ProductFeature
 * - Plan: Plan_Projektu/ETAP_05a_Produkty.md - FAZA 4 (UI Components)
 * - Architecture: _DOCS/ARCHITEKTURA_PPM/09_WARIANTY_CECHY.md (section 9.2)
 *
 * @package App\Http\Controllers\Admin
 * @version 1.0
 * @since ETAP_05a FAZA 4 (2025-10-23)
 */
class VehicleFeatureController extends Controller
{
    /**
     * Display Vehicle Features Management page
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin.features.index');
    }
}
