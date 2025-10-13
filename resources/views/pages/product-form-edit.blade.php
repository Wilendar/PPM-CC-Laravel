{{-- Product Form Edit Page - Blade Wrapper with Admin Layout --}}
@extends('layouts.admin', ['breadcrumb' => 'Edytuj produkt'])

@section('title', 'Edytuj produkt: ' . ($productModel->name ?? 'Produkt') . ' - PPM')

@section('content')
    <div class="container-fluid">
        @livewire('products.management.product-form', ['product' => $productModel])
    </div>
@endsection