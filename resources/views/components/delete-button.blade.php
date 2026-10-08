@props(['action', 'confirm' => '¿Seguro que desea eliminar este registro?', 'label' => null, 'size' => 'btn-sm'])
<form method="POST" action="{{ $action }}" data-confirm="{{ $confirm }}" class="inline">
    @csrf
    @method('DELETE')
    <button type="submit" {{ $attributes->merge(['class' => "btn btn-ghost {$size} text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10"]) }} title="Eliminar">
        <x-icon name="trash" class="h-4 w-4" />@if($label)<span>{{ $label }}</span>@endif
    </button>
</form>
