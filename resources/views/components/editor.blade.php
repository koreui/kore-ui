@props([
    'label' => null,
    'hint' => null,
    'name' => null,
    'error' => null,
    'placeholder' => null,
    'toolbar' => null,
    'minHeight' => null,
    'maxHeight' => null,
    'maxlength' => null,
    'counter' => false,
    'markdown' => false,
    // Subida de imágenes. Sin `uploadProperty` no hay botón: el editor no sabe
    // dónde guardar nada, y esa decisión es de la aplicación.
    'uploadProperty' => null,
    'uploadMethod' => null,
    'uploadMimes' => null,
    'uploadMaxSize' => null,
    'debounce' => null,
    'disabled' => false,
    'readonly' => false,
    'required' => false,
    'showError' => true,
])

@php
    $config = config('kore-ui.form.editor', []);

    $placeholder = $placeholder ?? ($config['placeholder'] ?? null);
    $minHeight = $minHeight ?? ($config['min_height'] ?? '12rem');
    $maxHeight = $maxHeight ?? ($config['max_height'] ?? null);
    $debounce = $debounce ?? ($config['debounce'] ?? 400);

    // Qué botones y en qué orden. Las barras separan grupos.
    $subeImagenes = filled($uploadProperty) && filled($uploadMethod);

    $uploadMimes = $uploadMimes ?? ($config['upload']['mimes'] ?? ['image/png', 'image/jpeg', 'image/gif', 'image/webp']);
    $uploadMaxSize = $uploadMaxSize ?? ($config['upload']['max_size'] ?? 2048);

    // Markdown no tiene subrayado: no hay sintaxis que guardar. Dejar el botón
    // sería prometer un formato que se pierde en cuanto el texto va y vuelve del
    // servidor, así que se cae solo.
    $quitarSubrayado = $markdown;

    $toolbar = $toolbar ?? ($config['toolbar'] ?? [
        'bold', 'italic', 'underline', 'strike', '|',
        'h2', 'h3', '|',
        'ul', 'ol', 'quote', 'pre', '|',
        'left', 'center', 'right', '|',
        'link', 'unlink', 'image', '|',
        'clear', 'undo', 'redo', 'fullscreen',
    ]);

    if (! $subeImagenes) {
        $toolbar = array_values(array_filter($toolbar, fn ($clave) => $clave !== 'image'));
    }

    if ($quitarSubrayado) {
        // Markdown tampoco tiene alineación: los tres botones prometerían un
        // formato que se pierde en cuanto el texto va y vuelve del servidor.
        $sinSintaxis = ['underline', 'left', 'center', 'right'];
        $toolbar = array_values(array_filter($toolbar, fn ($clave) => ! in_array($clave, $sinSintaxis, true)));
    }

    $name = $name ?? $attributes->whereStartsWith('wire:model')->first();

    $hasError = false;
    $errorMessage = null;

    if ($showError) {
        if ($error) {
            $hasError = true;
            $errorMessage = $error;
        } elseif ($name && isset($errors) && $errors->has($name)) {
            $hasError = true;
            $errorMessage = $errors->first($name);
        }
    }

    $fieldId = $attributes->get('id', \KoreUi\Core\Support\IdContext::para($name));
    $describedBy = $hasError ? $fieldId . '-error' : ($hint ? $fieldId . '-hint' : null);

    $edicionBloqueada = $disabled || $readonly;

    $tr = fn (string $clave, string $porDefecto) => config('kore-ui.form.translations.editor_' . $clave, $porDefecto);

    // Cada botón: icono, qué anuncia y qué hace. `formato` es la clave del
    // estado que lo enciende, cuando la tiene.
    $botones = [
        'bold'      => ['icon' => 'bold', 'label' => $tr('bold', 'Negrita'), 'accion' => "ejecutar('bold')", 'formato' => 'bold', 'atajo' => 'Ctrl+B'],
        'italic'    => ['icon' => 'italic', 'label' => $tr('italic', 'Cursiva'), 'accion' => "ejecutar('italic')", 'formato' => 'italic', 'atajo' => 'Ctrl+I'],
        'underline' => ['icon' => 'underline', 'label' => $tr('underline', 'Subrayado'), 'accion' => "ejecutar('underline')", 'formato' => 'underline', 'atajo' => 'Ctrl+U'],
        'strike'    => ['icon' => 'strikethrough', 'label' => $tr('strike', 'Tachado'), 'accion' => "ejecutar('strikeThrough')", 'formato' => 'strike'],
        'h2'        => ['icon' => 'heading-2', 'label' => $tr('h2', 'Título'), 'accion' => "bloque('H2')", 'formato' => 'h2'],
        'h3'        => ['icon' => 'heading-3', 'label' => $tr('h3', 'Subtítulo'), 'accion' => "bloque('H3')", 'formato' => 'h3'],
        'ul'        => ['icon' => 'list', 'label' => $tr('ul', 'Lista con viñetas'), 'accion' => "ejecutar('insertUnorderedList')", 'formato' => 'ul'],
        'ol'        => ['icon' => 'list-ordered', 'label' => $tr('ol', 'Lista numerada'), 'accion' => "ejecutar('insertOrderedList')", 'formato' => 'ol'],
        'quote'     => ['icon' => 'text-quote', 'label' => $tr('quote', 'Cita'), 'accion' => "bloque('BLOCKQUOTE')", 'formato' => 'quote'],
        'link'      => ['icon' => 'link', 'label' => $tr('link', 'Enlace'), 'accion' => 'abrirEnlace()', 'formato' => 'link', 'atajo' => 'Ctrl+K'],
        'unlink'    => ['icon' => 'unlink', 'label' => $tr('unlink', 'Quitar enlace'), 'accion' => 'quitarEnlace()'],
        'image'     => ['icon' => 'image', 'label' => $tr('image', 'Imagen'), 'accion' => 'elegirImagen()'],
        'pre'       => ['icon' => 'code', 'label' => $tr('pre', 'Bloque de código'), 'accion' => "bloque('PRE')", 'formato' => 'pre'],
        'left'      => ['icon' => 'align-left', 'label' => $tr('left', 'Alinear a la izquierda'), 'accion' => "alinear('left')", 'formato' => 'left'],
        'center'    => ['icon' => 'align-center', 'label' => $tr('center', 'Centrar'), 'accion' => "alinear('center')", 'formato' => 'center'],
        'right'     => ['icon' => 'align-right', 'label' => $tr('right', 'Alinear a la derecha'), 'accion' => "alinear('right')", 'formato' => 'right'],
        'fullscreen'=> ['icon' => 'maximize', 'label' => $tr('fullscreen', 'Pantalla completa'), 'accion' => 'alternarPantallaCompleta()', 'formato' => null],
        'clear'     => ['icon' => 'remove-formatting', 'label' => $tr('clear', 'Quitar formato'), 'accion' => "ejecutar('removeFormat')"],
        'undo'      => ['icon' => 'undo', 'label' => $tr('undo', 'Deshacer'), 'accion' => "ejecutar('undo')"],
        'redo'      => ['icon' => 'redo', 'label' => $tr('redo', 'Rehacer'), 'accion' => "ejecutar('redo')"],
    ];

    $marcoClases = collect([
        'rounded-kore-md border bg-kore-bg transition-colors',
        'focus-within:ring-2',
        $hasError
            ? 'border-kore-destructive focus-within:ring-kore-destructive/30 focus-within:border-kore-destructive'
            : 'border-kore-input focus-within:ring-kore-ring focus-within:border-kore-primary',
        $disabled ? 'opacity-50 cursor-not-allowed' : '',
    ])->filter()->implode(' ');

    $botonClases = 'inline-flex shrink-0 items-center justify-center size-8 rounded-kore-sm text-kore-muted-fg transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-kore-ring disabled:cursor-not-allowed';

    $jsConfig = json_encode(array_filter([
        'markdown' => $markdown ?: null,
        'upload' => $subeImagenes ? [
            'property' => $uploadProperty,
            'method' => $uploadMethod,
            'mimes' => $uploadMimes,
            'maxSize' => (int) $uploadMaxSize,
            'mensajes' => [
                'tipo' => $tr('image_type', 'Ese tipo de archivo no se admite.'),
                'tamano' => str_replace(':max', (string) $uploadMaxSize, $tr('image_size', 'La imagen supera :max KB.')),
                'error' => $tr('image_error', 'No se pudo subir la imagen.'),
            ],
        ] : null,
        'debounce' => (int) $debounce,
        'maxlength' => $maxlength !== null ? (int) $maxlength : null,
        'readonly' => $readonly ?: null,
        'disabled' => $disabled ?: null,
    ], fn ($v) => $v !== null));

    $atributosRaiz = $attributes->whereDoesntStartWith('wire:model')->except(['id']);
@endphp

{{-- El editor trae su JavaScript aparte: pesa un sexto de todo el de la
     librería y la mayoría de las páginas no lo usan.

     Va en `@assets` y no en `@once` por un motivo concreto: un `<script>` que
     llega dentro de una respuesta de Livewire —un editor dentro de un modal que
     se abre— NO se ejecuta, porque el morphing lo inserta como marcado y el
     navegador no corre los scripts insertados así. `@assets` es de Livewire
     justamente para eso: lo carga una sola vez y se encarga de ejecutarlo aunque
     el componente aparezca a mitad de partida. Fuera de Livewire se comporta
     como un `@once`.

     Y si aun así llegara tarde, el propio bundle rescata los editores que Alpine
     ya hubiera recorrido y dejado sin componente. --}}
@assets
    <script src="{{ \KoreUi\KoreUiServiceProvider::editorScriptUrl() }}" defer></script>
@endassets

<x-kore::field
    :label="$label"
    :hint="$hint"
    :has-error="$hasError"
    :error-message="$errorMessage"
    :field-id="$fieldId"
    :required="$required"
>
    <div
        x-data="KoreEditor({{ $jsConfig }})"
        x-bind:class="pantallaCompleta && 'fixed inset-0 z-50 m-0 rounded-none border-0 flex flex-col'"
        x-on:keydown.escape="pantallaCompleta && alternarPantallaCompleta()"
        {{ $atributosRaiz->merge(['class' => $marcoClases]) }}
        @if($readonly) aria-readonly="true" @endif
    >
        {{-- El valor de verdad. El editable no puede llevar el `wire:model`
             porque un `contenteditable` no es un control de formulario: no tiene
             `value` que Livewire pueda leer. --}}
        <input type="hidden" x-ref="hiddenInput" {{ $attributes->whereStartsWith('wire:model') }}
            @if($name) name="{{ $name }}" @endif />

        @unless($edicionBloqueada)
            {{-- La barra es UNA sola parada del tabulador: se entra con Tab y se
                 recorre con las flechas. Con doce botones, lo contrario significa
                 doce pulsaciones para llegar al texto. --}}
            <div
                x-ref="toolbar"
                role="toolbar"
                aria-label="{{ $tr('toolbar', 'Herramientas de formato') }}"
                aria-controls="{{ $fieldId }}"
                x-on:keydown.arrow-right.prevent="mover(1)"
                x-on:keydown.arrow-left.prevent="mover(-1)"
                {{-- En un móvil, veinte botones a 32px ocupaban TRES filas: un
                     tercio del componente era barra. En pantalla estrecha va en
                     una sola fila que se desplaza a lo ancho —el patrón de
                     cualquier aplicación móvil—, y a partir de `sm` vuelve a
                     envolverse, que en un escritorio es más cómodo que arrastrar. --}}
                class="flex flex-nowrap overflow-x-auto sm:flex-wrap sm:overflow-x-visible items-center gap-0.5 border-b border-kore-border px-1.5 py-1"
            >
                @foreach($toolbar as $clave)
                    @if($clave === '|')
                        <span class="mx-1 h-5 w-px bg-kore-border" aria-hidden="true"></span>
                    @elseif(isset($botones[$clave]))
                        @php $boton = $botones[$clave]; @endphp
                        <button
                            type="button"
                            tabindex="-1"
                            {{-- El `mousedown` es lo que roba el foco, y con él se
                                 va la selección: pulsar «negrita» con un texto
                                 marcado lo aplicaba sobre una selección que ya no
                                 existía. Se corta antes de que ocurra. --}}
                            x-on:mousedown.prevent
                            x-on:click="{{ $boton['accion'] }}"
                            {{-- UN solo atributo `class`. Con dos, el navegador se
                                 queda con el primero y tira el segundo: los botones
                                 sin estado —deshacer, imagen, pantalla completa—
                                 perdían el `size-8` y quedaban en 16×16, por debajo
                                 del objetivo táctil que la propia librería exige.
                                 El `x-bind:class` de Alpine no pisa al estático:
                                 añade y quita sobre él. --}}
                            class="{{ $botonClases }}{{ isset($boton['formato']) ? '' : ' hover:bg-kore-muted hover:text-kore-fg' }}"
                            @isset($boton['formato'])
                                x-bind:aria-pressed="formatos.{{ $boton['formato'] }} ? 'true' : 'false'"
                                x-bind:class="formatos.{{ $boton['formato'] }} ? 'bg-kore-primary/10 text-kore-primary-text' : 'hover:bg-kore-muted hover:text-kore-fg'"
                            @endisset
                            aria-label="{{ $boton['label'] }}@isset($boton['atajo']) ({{ $boton['atajo'] }})@endisset"
                            title="{{ $boton['label'] }}@isset($boton['atajo']) · {{ $boton['atajo'] }}@endisset"
                        >
                            @if($clave === 'fullscreen')
                                <span x-show="!pantallaCompleta"><x-lucide-maximize class="size-4" /></span>
                                <span x-show="pantallaCompleta" x-cloak><x-lucide-minimize class="size-4" /></span>
                            @else
                                <x-dynamic-component :component="'lucide-' . $boton['icon']" class="size-4" />
                            @endif
                        </button>
                    @endif
                @endforeach
            </div>
        @endunless

        <div class="relative">
            {{-- `wire:ignore`: sin él, cualquier repintado del servidor reemplaza
                 el árbol que el usuario está editando y se lleva por delante el
                 cursor, la selección y el historial de deshacer. --}}
            <div
                x-ref="editable"
                wire:ignore
                id="{{ $fieldId }}"
                role="textbox"
                aria-multiline="true"
                @if($required) aria-required="true" @endif
                @if($hasError) aria-invalid="true" @endif
                @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
                @if($readonly) aria-readonly="true" @endif
                contenteditable="{{ $edicionBloqueada ? 'false' : 'true' }}"
                spellcheck="true"
                x-on:focus="asegurarBloque()"
                x-on:input="programarSincronia(); recontar()"
                x-on:blur="cerrar()"
                x-on:keydown="alTeclear($event)"
                x-on:keyup="leerFormatos()"
                x-on:mouseup="leerFormatos()"
                x-on:paste="alPegar($event)"
                x-on:drop="alSoltar($event)"
                x-bind:class="pantallaCompleta && 'flex-1'"
                class="kore-prose px-3 py-2 text-sm text-kore-fg outline-none overflow-y-auto"
                x-bind:style="pantallaCompleta ? 'min-height:0;max-height:none' : ''"
                style="min-height: {{ $minHeight }};@if($maxHeight) max-height: {{ $maxHeight }};@endif"
            ></div>

            @if($placeholder)
                {{-- Un `contenteditable` no tiene `placeholder`; se pinta encima y
                     se esconde en cuanto hay algo. `pointer-events-none` para que
                     pinchar en él lleve el cursor al texto, no al hueco. --}}
                <div
                    x-show="vacio"
                    x-cloak
                    aria-hidden="true"
                    class="pointer-events-none absolute inset-x-3 top-2 text-sm text-kore-muted-fg"
                >{{ $placeholder }}</div>
            @endif
        </div>

        @if($counter || $maxlength)
            <div class="flex justify-end border-t border-kore-border px-3 py-1">
                <span
                    class="text-xs tabular-nums text-kore-muted-fg"
                    x-bind:class="{{ $maxlength ? 'caracteres > ' . (int) $maxlength . ' ? \'text-kore-destructive\' : \'\'' : "''" }}"
                    aria-live="polite"
                >
                    <span x-text="caracteres">0</span>@if($maxlength) / {{ (int) $maxlength }}@endif
                </span>
            </div>
        @endif

        @if($subeImagenes)
            {{-- Escondido, pero DENTRO del documento: un input creado al vuelo y
                 no insertado no dispara `change` en algunos navegadores, y el
                 archivo se pierde sin error ninguno. --}}
            <input
                type="file"
                x-ref="archivo"
                class="sr-only"
                tabindex="-1"
                aria-hidden="true"
                accept="{{ implode(',', $uploadMimes) }}"
                x-on:change="alElegirArchivo($event)"
            />

            <div x-show="progresoImagen !== null" x-cloak class="flex items-center gap-2 border-t border-kore-border px-3 py-1.5">
                <span class="text-xs text-kore-muted-fg shrink-0">{{ $tr('image_uploading', 'Subiendo imagen…') }}</span>
                <div class="h-1 flex-1 overflow-hidden rounded-full bg-kore-muted" role="progressbar"
                     x-bind:aria-valuenow="progresoImagen" aria-valuemin="0" aria-valuemax="100"
                     x-bind:aria-label="@js($tr('image_uploading', 'Subiendo imagen…'))">
                    <div class="h-full bg-kore-primary transition-[width] duration-150" x-bind:style="`width: ${progresoImagen}%`"></div>
                </div>
                <span class="text-xs tabular-nums text-kore-muted-fg shrink-0" x-text="`${progresoImagen}%`"></span>
            </div>

            <p x-show="errorImagen" x-cloak role="alert"
               class="border-t border-kore-border px-3 py-1.5 text-xs text-kore-destructive" x-text="errorImagen"></p>
        @endif

        {{-- El diálogo del enlace. Vive dentro del componente y no en un overlay
             porque tiene que devolver el foco y la selección exactos al cerrarse. --}}
        <div
            x-show="dialogoEnlace"
            x-cloak
            x-on:keydown.escape.stop="dialogoEnlace = false; $refs.editable.focus()"
            {{-- Envuelve en pantalla estrecha: con la etiqueta, el campo y los
                 dos botones en una sola fila, el campo se quedaba en 130px de los
                 390 de un móvil. --}}
            class="flex flex-wrap items-center gap-2 border-t border-kore-border bg-kore-muted/30 px-3 py-2"
        >
            <label for="{{ $fieldId }}-url" class="text-xs text-kore-muted-fg shrink-0">{{ $tr('url', 'Dirección') }}</label>
            <input
                type="url"
                id="{{ $fieldId }}-url"
                x-ref="campoEnlace"
                x-model="urlEnlace"
                x-on:keydown.enter.prevent="aplicarEnlace()"
                placeholder="{{ $tr('url_placeholder', 'https://') }}"
                class="flex-1 basis-40 min-w-0 rounded-kore-sm border border-kore-input bg-kore-bg px-2 py-1 text-sm text-kore-fg outline-none focus:ring-2 focus:ring-kore-ring"
            />
            <button type="button" x-on:click="aplicarEnlace()" class="rounded-kore-sm bg-kore-primary px-3 py-1.5 text-xs font-medium text-kore-primary-fg min-h-6">
                {{ $tr('apply', 'Aplicar') }}
            </button>
            <button type="button" x-on:click="dialogoEnlace = false; $refs.editable.focus()" class="rounded-kore-sm px-3 py-1.5 text-xs text-kore-muted-fg hover:text-kore-fg min-h-6">
                {{ config('kore-ui.ui.translations.close', 'Cerrar') }}
            </button>
        </div>
    </div>
</x-kore::field>
