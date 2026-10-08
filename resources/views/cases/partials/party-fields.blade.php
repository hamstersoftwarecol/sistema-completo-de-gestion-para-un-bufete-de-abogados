{{-- Campos de una parte del proceso. $model = 'editing' enlaza con Alpine (edición), null usa old() (alta). --}}
@php
    $bind = fn (string $field) => $model ? "x-model=\"{$model}.{$field}\"" : 'value="'.e(old($field)).'"';
@endphp
<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <x-field label="Nombre o razón social" name="name" required>
        <input name="name" required class="form-control" {!! $bind('name') !!}>
    </x-field>
    <x-field label="Calidad en el proceso" name="role" required>
        <select name="role" required class="form-control" @if($model) x-model="{{ $model }}.role" @endif>
            @foreach (config('bufete.party_roles') as $k => $l)
                <option value="{{ $k }}" @selected(! $model && old('role', 'demandado') === $k)>{{ $l }}</option>
            @endforeach
        </select>
    </x-field>
    <x-field label="Documento" name="document_number"><input name="document_number" class="form-control" {!! $bind('document_number') !!}></x-field>
    <x-field label="Teléfono" name="phone"><input name="phone" class="form-control" {!! $bind('phone') !!}></x-field>
    <x-field label="Correo" name="email"><input type="email" name="email" class="form-control" {!! $bind('email') !!}></x-field>
    <x-field label="Apoderado" name="lawyer_name"><input name="lawyer_name" class="form-control" {!! $bind('lawyer_name') !!}></x-field>
    <x-field label="Dirección" name="address" class="md:col-span-2"><input name="address" class="form-control" {!! $bind('address') !!}></x-field>
    <x-field label="Observaciones" name="notes" class="md:col-span-2">
        <textarea name="notes" rows="2" class="form-control" @if($model) x-model="{{ $model }}.notes" @endif>{{ $model ? '' : old('notes') }}</textarea>
    </x-field>
</div>
