<x-app-layout title="Datos maestros">
    <x-slot name="header">
        <x-page-header title="Datos maestros" subtitle="Catálogos usados en los expedientes: tipos de caso, estados y juzgados" />
    </x-slot>

    <div x-data="{ tab: (window.location.hash || '#tipos').substring(1), item: null }" x-init="$watch('tab', t => history.replaceState(null, '', '#' + t))">
        <div class="card mb-6">
            <div class="flex gap-6 overflow-x-auto px-5">
                <button type="button" @click="tab = 'tipos'" class="tab" :class="tab === 'tipos' && 'tab-active'"><x-icon name="folder" class="h-4 w-4" /> Tipos de caso</button>
                <button type="button" @click="tab = 'estados'" class="tab" :class="tab === 'estados' && 'tab-active'"><x-icon name="flag" class="h-4 w-4" /> Estados de caso</button>
                <button type="button" @click="tab = 'juzgados'" class="tab" :class="tab === 'juzgados' && 'tab-active'"><x-icon name="building" class="h-4 w-4" /> Juzgados y tribunales</button>
            </div>
        </div>

        {{-- Tipos --}}
        <div x-show="tab === 'tipos'" class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <x-card title="Nuevo tipo de caso" icon="plus">
                <form method="POST" action="{{ route('admin.case-types.store') }}" class="space-y-3">
                    @csrf
                    <x-field label="Nombre" name="name" required><input name="name" required class="form-control"></x-field>
                    <x-field label="Descripción" name="description"><input name="description" class="form-control"></x-field>
                    <input type="hidden" name="is_active" value="1">
                    <button class="btn btn-primary w-full">Agregar</button>
                </form>
            </x-card>
            <x-card title="Tipos de caso" icon="folder" :padding="false" class="xl:col-span-2">
                <table class="table">
                    <tbody>
                    @foreach ($types as $t)
                        <tr x-data="{ edit: false }">
                            <td colspan="5" class="!p-0">
                                <div x-show="!edit" class="flex items-center gap-4 px-4 py-3">
                                    <span class="w-40 font-medium">{{ $t->name }}</span>
                                    <span class="flex-1 text-xs text-gray-500">{{ $t->description }}</span>
                                    <span class="w-16 text-center">{{ $t->cases_count }}</span>
                                    <x-badge :color="$t->is_active ? 'green' : 'gray'">{{ $t->is_active ? 'Activo' : 'Inactivo' }}</x-badge>
                                    <span class="whitespace-nowrap">
                                        <button type="button" class="btn btn-ghost btn-sm" @click="edit = true"><x-icon name="pencil" class="h-4 w-4" /></button>
                                        <x-delete-button :action="route('admin.case-types.destroy', $t)" confirm="¿Eliminar el tipo «{{ $t->name }}»?" />
                                    </span>
                                </div>
                                <form x-show="edit" x-cloak method="POST" action="{{ route('admin.case-types.update', $t) }}" class="flex flex-wrap items-center gap-2 bg-gray-50 px-4 py-3 dark:bg-gray-800/60">
                                    @csrf @method('PUT')
                                    <input name="name" value="{{ $t->name }}" class="form-control w-40">
                                    <input name="description" value="{{ $t->description }}" class="form-control flex-1">
                                    <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="is_active" value="1" class="form-check" @checked($t->is_active)> Activo</label>
                                    <button class="btn btn-primary btn-sm">Guardar</button>
                                    <button type="button" class="btn btn-secondary btn-sm" @click="edit = false">Cancelar</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </x-card>
        </div>

        {{-- Estados --}}
        <div x-show="tab === 'estados'" x-cloak class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <x-card title="Nuevo estado" icon="plus">
                <form method="POST" action="{{ route('admin.case-statuses.store') }}" class="space-y-3" x-data="{ color: 'blue' }">
                    @csrf
                    <x-field label="Nombre" name="name" required><input name="name" required class="form-control"></x-field>
                    <x-field label="Color" name="color">
                        <input type="hidden" name="color" :value="color">
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($colors as $c)
                                <button type="button" @click="color = '{{ $c }}'" class="h-6 w-6 rounded-full ring-offset-2 dark:ring-offset-gray-800" style="background: {{ \App\Support\Badge::hex($c) }}" :class="color === '{{ $c }}' && 'ring-2 ring-gray-900 dark:ring-white'" title="{{ $c }}"></button>
                            @endforeach
                        </div>
                    </x-field>
                    <x-field label="Orden" name="sort_order"><input type="number" name="sort_order" value="{{ $statuses->max('sort_order') + 1 }}" class="form-control"></x-field>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_closed" value="1" class="form-check"> Es un estado de cierre (caso terminado)</label>
                    <button class="btn btn-primary w-full">Agregar</button>
                </form>
            </x-card>
            <x-card title="Estados de caso" icon="flag" :padding="false" class="xl:col-span-2">
                <table class="table">
                    <tbody>
                    @foreach ($statuses as $s)
                        <tr x-data="{ edit: false, color: '{{ $s->color }}' }">
                            <td class="!p-0">
                                <div x-show="!edit" class="flex items-center gap-4 px-4 py-3">
                                    <span class="w-8 text-center text-xs text-gray-400">{{ $s->sort_order }}</span>
                                    <span class="flex-1"><x-badge :color="$s->color">{{ $s->name }}</x-badge> @if($s->is_closed)<span class="ml-2 text-xs text-gray-500">Cierre</span>@endif</span>
                                    <span class="text-xs text-gray-500">{{ $s->cases_count }} caso(s)</span>
                                    <span class="whitespace-nowrap">
                                        <button type="button" class="btn btn-ghost btn-sm" @click="edit = true"><x-icon name="pencil" class="h-4 w-4" /></button>
                                        <x-delete-button :action="route('admin.case-statuses.destroy', $s)" confirm="¿Eliminar el estado «{{ $s->name }}»?" />
                                    </span>
                                </div>
                                <form x-show="edit" x-cloak method="POST" action="{{ route('admin.case-statuses.update', $s) }}" class="space-y-2 bg-gray-50 px-4 py-3 dark:bg-gray-800/60">
                                    @csrf @method('PUT')
                                    <div class="flex flex-wrap items-center gap-2">
                                        <input type="number" name="sort_order" value="{{ $s->sort_order }}" class="form-control w-20">
                                        <input name="name" value="{{ $s->name }}" class="form-control flex-1">
                                        <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="is_closed" value="1" class="form-check" @checked($s->is_closed)> Cierre</label>
                                    </div>
                                    <input type="hidden" name="color" :value="color">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @foreach ($colors as $c)
                                            <button type="button" @click="color = '{{ $c }}'" class="h-5 w-5 rounded-full ring-offset-2 dark:ring-offset-gray-800" style="background: {{ \App\Support\Badge::hex($c) }}" :class="color === '{{ $c }}' && 'ring-2 ring-gray-900 dark:ring-white'"></button>
                                        @endforeach
                                        <span class="ml-auto"><button class="btn btn-primary btn-sm">Guardar</button> <button type="button" class="btn btn-secondary btn-sm" @click="edit = false">Cancelar</button></span>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </x-card>
        </div>

        {{-- Juzgados --}}
        <div x-show="tab === 'juzgados'" x-cloak class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <x-card title="Nuevo juzgado / tribunal" icon="plus">
                <form method="POST" action="{{ route('admin.courts.store') }}" class="space-y-3">
                    @csrf
                    <x-field label="Nombre" name="name" required><input name="name" required class="form-control" placeholder="Juzgado 3 Civil del Circuito"></x-field>
                    <x-field label="Ciudad" name="city"><input name="city" class="form-control"></x-field>
                    <x-field label="Dirección" name="address"><input name="address" class="form-control"></x-field>
                    <div class="grid grid-cols-2 gap-3">
                        <x-field label="Teléfono" name="phone"><input name="phone" class="form-control"></x-field>
                        <x-field label="Correo" name="email"><input type="email" name="email" class="form-control"></x-field>
                    </div>
                    <button class="btn btn-primary w-full">Agregar</button>
                </form>
            </x-card>
            <x-card title="Juzgados y tribunales" icon="building" :padding="false" class="xl:col-span-2">
                <table class="table">
                    <tbody>
                    @foreach ($courts as $ct)
                        <tr x-data="{ edit: false }">
                            <td class="!p-0">
                                <div x-show="!edit" class="flex items-center gap-4 px-4 py-3">
                                    <div class="flex-1"><p class="font-medium">{{ $ct->name }}</p><p class="text-xs text-gray-500">{{ $ct->city }} · {{ $ct->address }} · {{ $ct->phone }}</p></div>
                                    <span class="text-xs text-gray-500">{{ $ct->cases_count }} caso(s)</span>
                                    <span class="whitespace-nowrap">
                                        <button type="button" class="btn btn-ghost btn-sm" @click="edit = true"><x-icon name="pencil" class="h-4 w-4" /></button>
                                        <x-delete-button :action="route('admin.courts.destroy', $ct)" confirm="¿Eliminar «{{ $ct->name }}»?" />
                                    </span>
                                </div>
                                <form x-show="edit" x-cloak method="POST" action="{{ route('admin.courts.update', $ct) }}" class="grid grid-cols-1 gap-2 bg-gray-50 px-4 py-3 dark:bg-gray-800/60 md:grid-cols-6">
                                    @csrf @method('PUT')
                                    <input name="name" value="{{ $ct->name }}" class="form-control md:col-span-3">
                                    <input name="city" value="{{ $ct->city }}" placeholder="Ciudad" class="form-control md:col-span-3">
                                    <input name="address" value="{{ $ct->address }}" placeholder="Dirección" class="form-control md:col-span-2">
                                    <input name="phone" value="{{ $ct->phone }}" placeholder="Teléfono" class="form-control md:col-span-2">
                                    <input name="email" value="{{ $ct->email }}" placeholder="Correo" class="form-control md:col-span-2">
                                    <div class="flex justify-end gap-2 md:col-span-6"><button type="button" class="btn btn-secondary btn-sm" @click="edit = false">Cancelar</button><button class="btn btn-primary btn-sm">Guardar</button></div>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </x-card>
        </div>
    </div>
</x-app-layout>
