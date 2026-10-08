<x-app-layout title="Editar gasto">
    <x-slot name="header"><x-page-header title="Editar gasto" :back="route('expenses.show', $expense)" /></x-slot>
    <form method="POST" action="{{ route('expenses.update', $expense) }}" enctype="multipart/form-data" class="max-w-4xl">
        @csrf
        @method('PUT')
        @include('expenses._form')
        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('expenses.show', $expense) }}" class="btn btn-secondary">Cancelar</a>
            <button class="btn btn-primary"><x-icon name="check" class="h-4 w-4" /> Guardar</button>
        </div>
    </form>
</x-app-layout>
