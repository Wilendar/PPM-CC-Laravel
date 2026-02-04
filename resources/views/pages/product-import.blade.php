{{-- Product Import Panel - Full Layout Wrapper --}}
{{-- ETAP_06: Import System --}}
@extends('layouts.admin', ['breadcrumb' => 'Import produktow'])

@section('title', 'Import produktow - PPM')

@section('content')
    <div class="container-fluid py-4">
        @livewire('products.import.product-import-panel')

        {{-- Modale poza ProductImportPanel (FIX: zapobiega "Snapshot missing" po re-renderze panelu) --}}
        @livewire('products.import.modals.product-import-modal', key('product-import-modal'))
        @livewire('products.import.modals.import-prices-modal', key('import-prices-modal'))
        @livewire('products.import.modals.presta-shop-category-picker-modal', key('presta-shop-category-picker-modal'))
        @livewire('products.import.modals.c-s-v-import-modal', key('csv-import-modal'))
        @livewire('products.import.modals.variant-modal', key('variant-modal'))
        @livewire('products.import.modals.feature-template-modal', key('feature-template-modal'))
        @livewire('products.import.modals.compatibility-modal', key('compatibility-modal'))
        @livewire('products.import.modals.image-upload-modal', key('image-upload-modal'))
        @livewire('products.import.modals.description-modal', key('description-modal'))
    </div>
@endsection
