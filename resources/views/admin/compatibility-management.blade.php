@extends('layouts.admin')

@section('content')
    <livewire:admin.compatibility.compatibility-management />
@endsection

@push('styles')
    @vite(['resources/css/admin/components.css'])
@endpush
