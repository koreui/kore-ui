{{-- Contenido interno de un sidebar.item: icono + etiqueta + badge.
     Es un parcial porque lo comparten las dos formas del item (el <a> de un enlace y el
     <button> de un disclosure con sub-items), y duplicarlo garantizaría que acabaran
     divergiendo.

     Accesibilidad: al colapsar, la etiqueta y el badge pill salen del DOM visible
     (display:none), así que TAMBIÉN salen del árbol de accesibilidad. Los duplicados
     `sr-only` de abajo son los que mantienen el enlace con nombre y con su aviso en los
     dos estados; los elementos visibles van por tanto marcados como aria-hidden. --}}

@if($icon)
    <span class="relative shrink-0">
        <x-dynamic-component
            :component="'lucide-' . $icon"
            @class([
                'size-5',
                $isActive ? 'text-kore-primary' : 'text-kore-muted-fg',
            ])
        />

        @if($isDot)
            {{-- El punto se ve en los dos estados: es un aviso de "hay algo", y con el
                 sidebar ancho no hay ninguna píldora que lo sustituya. --}}
            <span class="kore-sidebar-dot {{ $dotClass }}" aria-hidden="true"></span>
        @elseif($hasBadge)
            {{-- Con el sidebar en iconos, el número se muda a la esquina del icono: es el
                 único hueco que queda. Vacío si ni acortado cabe, y entonces el CSS lo
                 degrada a un punto. --}}
            <span class="kore-sidebar-count {{ $countClass }}" aria-hidden="true">{{ $compactBadge }}</span>
        @endif
    </span>
@endif

<span class="kore-sidebar-label flex-1 text-start" aria-hidden="true">{{ $label }}</span>

{{-- El nombre del enlace. Es el único que sobrevive al colapso, donde la etiqueta visible
     ya no está en el árbol de accesibilidad. --}}
<span class="sr-only">{{ $label }}</span>

@if($hasBadge)
    {{-- El valor ÍNTEGRO, sin acortar: quien lo escucha merece el número real, no "99+". --}}
    <span class="sr-only">{{ $badge }}</span>

    @unless($isDot)
        <span
            class="kore-sidebar-label shrink-0 rounded-full px-1.5 py-0.5 text-xs font-medium {{ $pillClass }}"
            aria-hidden="true"
        >{{ $badge }}</span>
    @endunless

    {{-- Sin icono no hay esquina donde anclar nada, así que el aviso va al final. --}}
    @unless($icon)
        <span class="kore-sidebar-mini size-2.5 shrink-0 rounded-full {{ $dotClass }}" aria-hidden="true"></span>
    @endunless
@endif
