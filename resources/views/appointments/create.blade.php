<x-app-layout title="Reservar cita">
    <x-slot name="header"><x-page-header title="Reservar cita" :back="url()->previous()" /></x-slot>
    <form method="POST" action="{{ route('appointments.store') }}">
        @csrf
        @include('appointments._form')
        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ url()->previous() }}" class="btn btn-secondary">Cancelar</a>
            <button class="btn btn-primary"><x-icon name="check" class="h-4 w-4" /> Reservar</button>
        </div>
    </form>
</x-app-layout>
