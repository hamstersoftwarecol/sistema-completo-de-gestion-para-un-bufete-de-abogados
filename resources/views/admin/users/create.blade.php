<x-app-layout title="Nuevo usuario">
    <x-slot name="header"><x-page-header title="Nuevo usuario" :back="route('admin.users.index')" /></x-slot>
    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf
        @include('admin.users._form')
        <div class="mt-6 flex justify-end gap-2"><a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancelar</a><button class="btn btn-primary">Crear usuario</button></div>
    </form>
</x-app-layout>
