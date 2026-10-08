@props(['title' => null, 'icon' => null, 'padding' => true])
<div {{ $attributes->merge(['class' => 'card']) }}>
    @if ($title || isset($actions))
        <div class="card-header">
            <h3 class="card-title flex items-center gap-2">
                @if ($icon)<x-icon :name="$icon" class="h-5 w-5 text-primary-500" />@endif
                {{ $title }}
            </h3>
            @isset($actions)<div class="flex items-center gap-2">{{ $actions }}</div>@endisset
        </div>
    @endif
    <div @class(['card-body' => $padding])>
        {{ $slot }}
    </div>
</div>
