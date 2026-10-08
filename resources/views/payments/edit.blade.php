<x-app-layout title="Editar pago">
    <x-slot name="header">
        <x-page-header :title="'Editar pago '.$payment->receipt_number" :back="route('payments.show', $payment)">
            @can('delete', $payment)<x-delete-button :action="route('payments.destroy', $payment)" label="Eliminar" size="" confirm="¿Eliminar el pago {{ $payment->receipt_number }}? Esta acción no se puede deshacer." />@endcan
        </x-page-header>
    </x-slot>
    <form method="POST" action="{{ route('payments.update', $payment) }}">
        @csrf
        @method('PUT')
        @include('payments._form')
        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('payments.show', $payment) }}" class="btn btn-secondary">Cancelar</a>
            <button class="btn btn-primary"><x-icon name="check" class="h-4 w-4" /> Guardar</button>
        </div>
    </form>
</x-app-layout>
