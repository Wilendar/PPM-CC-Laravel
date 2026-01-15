@extends('layouts.admin')

@section('title', 'Edytor Wizualny Opisu')

@section('content')
    <livewire:products.visual-description.visual-description-editor
        :product="$product"
        :shop="$shop" />
@endsection
