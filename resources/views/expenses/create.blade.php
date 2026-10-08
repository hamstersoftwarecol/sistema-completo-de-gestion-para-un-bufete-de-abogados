<x-app-layout title="Registrar gasto">
    <x-slot name="header"><x-page-header title="Registrar gasto" subtitle="Se enviará al abogado responsable / administrador para su aprobación" :back="url()->previous()" /></x-slot>
    <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data" class="max-w-4xl">
        @csrf
        @include('expenses._form')
        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Cancelar</a>
            <button class="btn btn-primary"><x-icon name="send" class="h-4 w-4" /> Enviar para aprobación</button>
        </div>
    </form>
</x-app-layout>
