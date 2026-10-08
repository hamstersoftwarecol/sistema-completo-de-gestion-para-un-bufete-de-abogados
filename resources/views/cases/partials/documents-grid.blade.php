{{-- Cuadrícula de documentos con vista previa (PDF / imagen / video / audio) --}}
@php $showCase = $showCase ?? false; @endphp
<div x-data="{ preview: null }">
    @if ($documents->isEmpty())
        <x-empty icon="document" title="Sin documentos" message="Cargue demandas, poderes, pruebas, fotos, audios o videos del caso." />
    @else
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($documents as $doc)
                @php
                    $icon = match ($doc->kind) { 'pdf' => 'document', 'imagen' => 'photo', 'video' => 'video', 'audio' => 'audio', default => 'paperclip' };
                    $color = match ($doc->kind) { 'pdf' => 'text-red-500 bg-red-50 dark:bg-red-500/10', 'imagen' => 'text-sky-500 bg-sky-50 dark:bg-sky-500/10', 'video' => 'text-violet-500 bg-violet-50 dark:bg-violet-500/10', 'audio' => 'text-amber-500 bg-amber-50 dark:bg-amber-500/10', default => 'text-gray-500 bg-gray-100 dark:bg-gray-700' };
                @endphp
                <div class="group flex items-start gap-3 rounded-lg border border-gray-200 p-3 hover:border-primary-300 dark:border-gray-700 dark:hover:border-primary-700">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $color }}"><x-icon :name="$icon" class="h-5 w-5" /></div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold" title="{{ $doc->title }}">{{ $doc->title }}</p>
                        <p class="truncate text-xs text-gray-500">{{ $doc->category_label }} · {{ format_bytes($doc->size) }} · {{ $doc->created_at->format('d/m/Y') }}</p>
                        @if ($showCase)<p class="truncate text-xs text-gray-400">Caso {{ $doc->legalCase?->case_number }}</p>@endif
                        <div class="mt-2 flex gap-1">
                            @if ($doc->isPreviewable())
                                <button type="button" class="btn btn-secondary btn-sm" @click="preview = { url: '{{ route('documents.show', $doc) }}', kind: '{{ $doc->kind }}', title: @js($doc->title) }"><x-icon name="eye" class="h-3.5 w-3.5" /> Ver</button>
                            @endif
                            <a href="{{ route('documents.download', $doc) }}" class="btn btn-secondary btn-sm"><x-icon name="download" class="h-3.5 w-3.5" /></a>
                            @can('delete', $doc)
                                <x-delete-button :action="route('documents.destroy', $doc)" confirm="¿Eliminar el documento «{{ $doc->title }}»?" />
                            @endcan
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Visor --}}
    <template x-teleport="body">
        <div x-show="preview" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/80 p-4" @keydown.escape.window="preview = null" @click.self="preview = null">
            <div class="flex h-full max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl dark:bg-gray-800">
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <p class="truncate font-semibold" x-text="preview?.title"></p>
                    <div class="flex gap-2">
                        <a :href="preview?.url" target="_blank" class="btn btn-secondary btn-sm">Abrir en pestaña</a>
                        <button class="btn btn-ghost btn-sm" @click="preview = null"><x-icon name="x" class="h-5 w-5" /></button>
                    </div>
                </div>
                <div class="flex flex-1 items-center justify-center overflow-auto bg-gray-100 dark:bg-gray-900">
                    <template x-if="preview?.kind === 'pdf'"><iframe :src="preview.url" class="h-full w-full"></iframe></template>
                    <template x-if="preview?.kind === 'imagen'"><img :src="preview.url" class="max-h-full max-w-full object-contain"></template>
                    <template x-if="preview?.kind === 'video'"><video :src="preview.url" controls class="max-h-full max-w-full"></video></template>
                    <template x-if="preview?.kind === 'audio'"><audio :src="preview.url" controls class="w-2/3"></audio></template>
                </div>
            </div>
        </div>
    </template>
</div>
