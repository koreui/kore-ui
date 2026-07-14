@props([
    'variant' => null,
    'justify' => 'between',
    'role' => 'toolbar',
])

@php
    $variant = $variant ?? config('kore-ui.ui.toolbar.variant', 'default');

    $justifyClass = match($justify) {
        'start' => 'justify-start',
        'end' => 'justify-end',
        'center' => 'justify-center',
        default => 'justify-between',
    };
@endphp

<div
    {{ $attributes
        ->except(['variant', 'justify', 'role'])
        ->class([
            'flex items-center gap-2',
            $justifyClass,
            'border border-kore-border rounded-kore-md bg-kore-surface px-3 py-2' => $variant === 'bordered',
            'px-1 py-1' => $variant === 'default',
        ])
    }}
    {{-- role="toolbar" promete a los lectores de pantalla un widget que se recorre con
         las flechas. Lo que no lo sea (la navbar del shell, por ejemplo) puede pasar
         :role="false" para no emitirlo.

         Tiene que ser `false` y no `null`: @props resuelve con ??, así que un null
         explícito cae en el default exactamente igual que un atributo ausente. --}}
    @if($role) role="{{ $role }}" @endif
>
    @if(isset($start))
        <div class="flex items-center gap-2 shrink-0">
            {{ $start }}
        </div>
    @endif

    @if($slot->isNotEmpty())
        <div class="flex items-center gap-2">
            {{ $slot }}
        </div>
    @endif

    @if(isset($end))
        <div class="flex items-center gap-2 shrink-0">
            {{ $end }}
        </div>
    @endif
</div>
