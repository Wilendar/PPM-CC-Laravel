@extends('layouts.admin')

@section('title', 'Edytuj uzytkownika - Admin PPM')

@section('content')
    <livewire:admin.users.user-form :user="$user" />
@endsection
