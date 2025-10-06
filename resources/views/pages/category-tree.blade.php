{{-- Category Tree Page - Full Layout Wrapper --}}
@extends('layouts.admin')

@section('title', 'Lista kategorii - PPM')

@section('content')
    <div class="container-fluid py-4">
        @livewire('products.categories.category-tree')
    </div>
@endsection