{{-- Product Form Create Page - Blade Wrapper with Admin Layout --}}
@extends('layouts.admin')

@section('title', 'Dodaj nowy produkt - PPM')

@section('content')
    <div class="container-fluid">
        @livewire('products.management.product-form')
    </div>
@endsection