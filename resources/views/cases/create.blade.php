<x-app-layout title="Nuevo caso">
    <x-slot name="header">
        <x-page-header title="Nuevo caso" subtitle="Abra un expediente: cliente, juzgado, juez, tipo de caso y equipo" :back="route('cases.index')" />
    </x-slot>

    <form method="POST" action="{{ route('cases.store') }}" enctype="multipart/form-data">
        @csrf
        @include('cases._form')
        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('cases.index') }}" class="btn btn-secondary">Cancelar</a>
            <button class="btn btn-primary"><x-icon name="check" class="h-4 w-4" /> Crear caso</button>
        </div>
    </form>
</x-app-layout>
