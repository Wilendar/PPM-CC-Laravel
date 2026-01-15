{{-- Product Import Panel - Full Layout Wrapper --}}
{{-- ETAP_06: Import System --}}
@extends('layouts.admin', ['breadcrumb' => 'Import produktow'])

@section('title', 'Import produktow - PPM')

@section('content')
    <div class="container-fluid py-4">
        @livewire('products.import.product-import-panel')
    </div>
@endsection
