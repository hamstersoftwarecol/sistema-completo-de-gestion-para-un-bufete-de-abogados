<x-app-layout title="Editar usuario">
    <x-slot name="header"><x-page-header :title="'Editar: '.$user->name" :back="route('admin.users.index')" /></x-slot>
    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf @method('PUT')
        @include('admin.users._form')
        <div class="mt-6 flex justify-end gap-2"><a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancelar</a><button class="btn btn-primary">Guardar cambios</button></div>
    </form>
</x-app-layout>
