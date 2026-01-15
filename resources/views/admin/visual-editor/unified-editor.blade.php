@extends('layouts.admin')

@section('title', 'Unified Visual Editor')

@section('content')
    <livewire:products.visual-description.unified-visual-editor
        :product="$product"
        :shop="$shop"
    />
@endsection
