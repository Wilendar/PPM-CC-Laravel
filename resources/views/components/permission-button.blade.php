@props([
    'permission' => null,
    'action' => null,
    'disabledTooltip' => null,
])

@php
    $hasPermission = true;
    if ($permission) {
        $hasPermission = auth()->user()?->hasPermissionTo($permission) ?? false;
    } elseif ($action && isset($userPermissions)) {
        $hasPermission = $userPermissions[$action] ?? false;
    }
    $tooltip = $disabledTooltip ?? ($permission ? "Brak uprawnien: {$permission}" : 'Brak uprawnien');
@endphp

<button {{ $attributes->merge([
    'disabled' => !$hasPermission ?: false,
    'title' => !$hasPermission ? $tooltip : null,
]) }} @class([
    'opacity-50 cursor-not-allowed' => !$hasPermission,
    $attributes->get('class', ''),
])>
    {{ $slot }}
</button>
