@php
    $linkClasses = trim('btn-enterprise-secondary w-full py-3 ' . ($classes ?? ''));
@endphp

<a href="{{ route('admin.products.index') }}"
   class="{{ $linkClasses }}">
    <i class="fas fa-times mr-2"></i>
    Anuluj i wróć
</a>
