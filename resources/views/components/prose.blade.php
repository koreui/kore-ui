{{-- El texto enriquecido, ya publicado.

     Existe porque publicar lo que escribe `<x-kore::editor>` tenía dos trampas y
     las dos había que saberlas de memoria: pintarlo con `{!! !!}` sin sanear es
     un XSS almacenado, y los estilos de sus títulos, listas y bloques de código
     solo existían bajo una clase interna del propio editor. --}}
@props([
    // El HTML guardado. Se sanea antes de pintarlo: es la última línea, y aquí
    // ya no hay ninguna después.
    'html' => null,
    // El markdown guardado. No hace falta sanearlo: el HTML lo fabrica el
    // parser con las etiquetas que decide él.
    'markdown' => null,
    'size' => null,
])

@php
    $size = $size ?? config('kore-ui.ui.prose.size', 'md');

    $sizeClasses = match($size) {
        'sm' => 'text-sm',
        'lg' => 'text-lg',
        default => 'text-base',
    };

    $contenido = match(true) {
        filled($markdown) => \KoreUi\Editor\Markdown::aHtml($markdown),
        filled($html) => \KoreUi\Editor\HtmlSanitizer::limpiar($html),
        default => null,
    };
@endphp

<div {{ $attributes->class(['kore-prose text-kore-fg', $sizeClasses]) }}>
    @if($contenido !== null)
        {!! $contenido !!}
    @else
        {{-- Por slot se pinta tal cual: ahí puede haber componentes, no solo el
             texto del editor, y sanearlo se llevaría por delante lo que no es
             suyo. Quien lo use por aquí sanea él. --}}
        {{ $slot }}
    @endif
</div>
