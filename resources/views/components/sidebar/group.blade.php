@props([
    'label' => null,
    'icon' => null,
    'collapsible' => false,
    'collapsed' => false,
    'separator' => 'line',
    'header' => null,
])

@php
    // Un grupo agrupa; no navega. Si contiene la ruta activa y es colapsable, se abre
    // solo — mismo mecanismo que sidebar.item: el marcador ya está en el HTML del slot,
    // porque Blade renderiza los slots antes que la plantilla que los contiene.
    $slotHtml = (string) $slot;
    $hasActiveChild = str_contains($slotHtml, 'data-kore-active="true"');

    $isOpen = ! $collapsible || ! $collapsed || $hasActiveChild;

    $uid = 'kore-group-'.substr(md5(($label ?? '').spl_object_hash($slot)), 0, 8);
@endphp

<li
    role="none"
    @if($collapsible) data-kore-has-children data-kore-open="{{ $isOpen ? 'true' : 'false' }}" @endif
    {{ $attributes->except('class')->class([
        'mt-4 first:mt-0',
        'border-t border-kore-border pt-4' => $separator === 'line',
        $attributes->get('class'),
    ]) }}
>
    {{-- El título va envuelto en .kore-sidebar-group-header, que anima su ALTURA al
         colapsar. No basta con esconder el texto: la fila entera tiene que encogerse, o
         desaparecería de golpe y todo lo que hay debajo pegaría un salto hacia arriba.
         Los items del grupo siguen visibles como iconos; lo que se va es el rótulo. --}}
    @if($header || $label)
        <div class="kore-sidebar-group-header">
            @if($collapsible)
                <button
                    type="button"
                    x-on:click="toggleItem($event)"
                    aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                    aria-controls="{{ $uid }}"
                    class="kore-sidebar-link w-full rounded-kore-md py-1.5 text-xs font-semibold uppercase tracking-wider text-kore-muted-fg transition-colors hover:bg-kore-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-kore-ring/50"
                >
                    @if($icon)
                        <x-dynamic-component :component="'lucide-' . $icon" class="size-4 shrink-0" />
                    @endif

                    <span class="kore-sidebar-label flex-1 text-start">{{ $header ?? $label }}</span>

                    <x-dynamic-component
                        component="lucide-chevron-right"
                        class="kore-sidebar-chevron kore-sidebar-label size-3.5 shrink-0"
                    />
                </button>
            @else
                <div class="kore-sidebar-link py-1.5">
                    @if($icon)
                        <x-dynamic-component :component="'lucide-' . $icon" class="size-4 shrink-0 text-kore-muted-fg" />
                    @endif

                    <span class="kore-sidebar-label text-xs font-semibold uppercase tracking-wider text-kore-muted-fg">
                        {{ $header ?? $label }}
                    </span>
                </div>
            @endif
        </div>
    @endif

    @if($collapsible)
        <div class="kore-sidebar-submenu">
            <div>
                <ul role="list" id="{{ $uid }}" class="mt-1 space-y-1">
                    {{ $slot }}
                </ul>
            </div>
        </div>
    @else
        <ul role="list" class="mt-1 space-y-1">
            {{ $slot }}
        </ul>
    @endif
</li>
