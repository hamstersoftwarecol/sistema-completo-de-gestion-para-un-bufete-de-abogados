<x-app-layout title="Usuarios">
    <x-slot name="header">
        <x-page-header title="Usuarios y roles" subtitle="Superadministrador ve todo el bufete; cada abogado ve sólo sus asuntos">
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><x-icon name="user-plus" class="h-4 w-4" /> Nuevo usuario</a>
        </x-page-header>
    </x-slot>

    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        @foreach ([
            [\App\Enums\Role::Superadmin, 'Acceso total: todos los casos, usuarios, datos maestros, configuración, copias de seguridad y reembolsos.'],
            [\App\Enums\Role::Senior, 'Ve sus casos (responsable o asistente). Puede eliminar sus casos, editar pagos y aprobar gastos de su equipo.'],
            [\App\Enums\Role::Junior, 'Ve sólo los casos asignados. Registra notas, documentos, audiencias, pagos y gastos (que requieren aprobación).'],
        ] as [$role, $desc])
            <div class="card p-4"><x-badge :color="$role->color()">{{ $role->label() }}</x-badge><p class="mt-2 text-xs text-gray-500">{{ $desc }}</p></div>
        @endforeach
    </div>

    <div class="card">
        <form method="GET" class="flex flex-col gap-3 border-b border-gray-100 p-4 dark:border-gray-700 sm:flex-row">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Nombre o correo…" class="form-control flex-1">
            <select name="role" class="form-control sm:w-56" onchange="this.form.submit()">
                <option value="">Todos los roles</option>
                @foreach (\App\Enums\Role::options() as $v => $l)<option value="{{ $v }}" @selected(request('role') === $v)>{{ $l }}</option>@endforeach
            </select>
        </form>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Usuario</th><th>Rol</th><th>Contacto</th><th class="text-center">Casos</th><th>Último acceso</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                @foreach ($users as $u)
                    <tr @class(['opacity-60' => ! $u->is_active])>
                        <td>
                            <div class="flex items-center gap-3">
                                <x-avatar :name="$u->name" />
                                <div><p class="font-semibold">{{ $u->name }} @if($u->id === auth()->id())<span class="text-xs text-gray-400">(usted)</span>@endif</p><p class="text-xs text-gray-500">{{ $u->email }}</p></div>
                            </div>
                        </td>
                        <td><x-badge :color="$u->role->color()">{{ $u->role->label() }}</x-badge>@if($u->specialty)<p class="mt-1 text-xs text-gray-500">{{ $u->specialty }}</p>@endif</td>
                        <td class="whitespace-nowrap text-xs">{{ $u->phone ?: '—' }}<br><span class="text-gray-500">{{ $u->professional_id }}</span></td>
                        <td class="whitespace-nowrap text-center text-sm"><span class="font-semibold">{{ $u->cases_as_lawyer_count }}</span> <span class="text-xs text-gray-500">+{{ $u->cases_as_assistant_count }} asist.</span></td>
                        <td class="whitespace-nowrap text-xs">{{ $u->last_login_at ? $u->last_login_at->diffForHumans() : 'Nunca' }}@if($u->last_login_ip)<br><span class="text-gray-400">{{ $u->last_login_ip }}</span>@endif</td>
                        <td><x-badge :color="$u->is_active ? 'green' : 'gray'">{{ $u->is_active ? 'Activo' : 'Inactivo' }}</x-badge></td>
                        <td class="whitespace-nowrap text-right">
                            @if ($u->id !== auth()->id())
                                @if ($u->is_active && ! session()->has('impersonator_id'))
                                    <form method="POST" action="{{ route('admin.users.impersonate', $u) }}" class="inline">@csrf
                                        <button class="btn btn-ghost btn-sm text-amber-600" title="Iniciar sesión como {{ $u->name }}"><x-icon name="switch" class="h-4 w-4" /></button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.users.toggle', $u) }}" class="inline">@csrf @method('PATCH')
                                    <button class="btn btn-ghost btn-sm" title="{{ $u->is_active ? 'Desactivar' : 'Activar' }}"><x-icon :name="$u->is_active ? 'lock' : 'key'" class="h-4 w-4" /></button>
                                </form>
                            @endif
                            <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-ghost btn-sm" title="Editar"><x-icon name="pencil" class="h-4 w-4" /></a>
                            @if ($u->id !== auth()->id())
                                <x-delete-button :action="route('admin.users.destroy', $u)" confirm="¿Eliminar a {{ $u->name }}? Si tiene casos asignados no se podrá eliminar." />
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())<div class="border-t border-gray-100 p-4 dark:border-gray-700">{{ $users->links() }}</div>@endif
    </div>
</x-app-layout>
