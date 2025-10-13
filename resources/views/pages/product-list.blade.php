{{-- Product List Page - Full Layout Wrapper --}}
@extends('layouts.admin', ['breadcrumb' => 'Lista produktów'])

@section('title', 'Lista produktów - PPM')

@section('content')
    <div class="container-fluid py-4">
        @livewire('products.listing.product-list')
    </div>
@endsection