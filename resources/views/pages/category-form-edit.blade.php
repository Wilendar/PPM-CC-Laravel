{{-- Category Form Edit Page - Full Layout Wrapper --}}
@extends('layouts.admin')

@section('title', 'Edytuj kategorię: ' . ($categoryModel->name ?? 'Kategoria') . ' - PPM')

@section('content')
    <div class="container-fluid py-4">
        @livewire('products.categories.category-form', ['category' => $categoryModel])
    </div>
@endsection