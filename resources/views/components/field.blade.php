{{-- Campo de formulario con etiqueta, ayuda y error de validación --}}
@props(['label' => null, 'name' => null, 'hint' => null, 'required' => false])
@php $errorKey = $name ? str_replace(['[', ']'], ['.', ''], $name) : null; @endphp
<div {{ $attributes }}>
    @if ($label)
        <label @if($name) for="{{ $name }}" @endif class="form-label">{{ $label }}@if($required)<span class="text-red-500"> *</span>@endif</label>
    @endif
    {{ $slot }}
    @if ($hint)<p class="form-hint">{{ $hint }}</p>@endif
    @if ($errorKey)
        @error($errorKey)<p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    @endif
</div>
