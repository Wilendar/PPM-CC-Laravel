{{-- Category Form Create Page - Full Layout Wrapper --}}
@extends('layouts.admin')

@section('title', 'Nowa kategoria - PPM')

@section('content')
    <div class="container-fluid py-4">
        @livewire('products.categories.category-form')
    </div>
@endsection