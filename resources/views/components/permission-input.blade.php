@props([
    'permission' => null,
    'action' => null,
])

@php
    $canEdit = true;
    if ($permission) {
        $canEdit = auth()->user()?->hasPermissionTo($permission) ?? false;
    } elseif ($action && isset($userPermissions)) {
        $canEdit = $userPermissions[$action] ?? false;
    }
@endphp

<input {{ $attributes->merge([
    'readonly' => !$canEdit ?: false,
]) }} @class([
    'bg-gray-700/50 cursor-not-allowed' => !$canEdit,
    $attributes->get('class', ''),
]) />
