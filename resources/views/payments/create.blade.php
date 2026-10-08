<x-app-layout title="Registrar pago">
    <x-slot name="header"><x-page-header title="Registrar pago" :back="url()->previous()" /></x-slot>
    <form method="POST" action="{{ route('payments.store') }}">
        @csrf
        @include('payments._form')
        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('payments.index') }}" class="btn btn-secondary">Cancelar</a>
            <button class="btn btn-primary"><x-icon name="check" class="h-4 w-4" /> Guardar y ver recibo</button>
        </div>
    </form>
</x-app-layout>
