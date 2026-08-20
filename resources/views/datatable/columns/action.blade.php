@php
    $props = $column->getComponentProps();
    $actions = $column->getVisibleActions($row);
    $isDropdown = ($props['displayMode'] ?? 'dropdown') === 'dropdown';

    /**
     * Resuelve un color para botones inline → ['class' => ..., 'style' => ...].
     *
     * Semánticos kore  → clases Tailwind  (sin inline style)
     * Tailwind v4 scale  (ej: red-500)    → var(--color-red-500)
     * Valores CSS crudos (#hex, rgb, …)   → valor directo
     * Token custom       (ej: icon-red)   → var(--icon-red)
     */
    $resolveColor = function (string $color): array {
        $semantic = [
            'primary'     => 'text-kore-primary-text hover:bg-kore-primary/10',
            'destructive' => 'text-kore-destructive-text hover:bg-kore-destructive/10',
            'success'     => 'text-kore-success-text hover:bg-kore-success/10',
            'warning'     => 'text-kore-warning-text hover:bg-kore-warning/10',
            'info'        => 'text-kore-info-text hover:bg-kore-info/10',
            'muted'       => 'text-kore-muted-fg hover:bg-kore-muted/50',
        ];

        if (isset($semantic[$color])) {
            return ['class' => $semantic[$color], 'style' => ''];
        }

        // Tailwind v4: red-500, blue-600, emerald-400, etc.
        if (preg_match('/^[a-z]+-\d+$/', $color)) {
            return ['class' => 'hover:bg-current/10', 'style' => "color: var(--color-{$color})"];
        }

        // Valor CSS directo: #hex, rgb(), rgba(), oklch(), hsl(), hsla()
        if (preg_match('/^(#|rgb|rgba|oklch|hsl|hsla)/', $color)) {
            return ['class' => 'hover:bg-current/10', 'style' => "color: {$color}"];
        }

        // Token CSS personalizado: icon-red → var(--icon-red)
        return ['class' => 'hover:bg-current/10', 'style' => "color: var(--{$color})"];
    };

    /**
     * Resuelve un color para dropdown.item → string de inline style.
     * Retorna '' para 'primary' (mantiene el text-kore-fg neutro del dropdown).
     */
    $resolveDropdownStyle = function (string $color) use ($resolveColor): string {
        if ($color === 'primary') {
            return '';
        }

        $semanticVars = [
            'destructive' => 'var(--kore-destructive)',
            'success'     => 'var(--kore-success)',
            'warning'     => 'var(--kore-warning)',
            'info'        => 'var(--kore-info)',
            'muted'       => 'var(--kore-muted-fg)',
        ];

        if (isset($semanticVars[$color])) {
            return "color: {$semanticVars[$color]}";
        }

        return $resolveColor($color)['style'];
    };
@endphp

@if(count($actions) > 0)
    @if($isDropdown)
        <x-kore::dropdown position="bottom-end" width="180px">
            <x-slot:trigger>
                <button type="button" aria-label="{{ $column->getLabel() }}" class="p-1 rounded-kore-md hover:bg-kore-muted transition-colors">
                    <x-dynamic-component :component="'lucide-' . ($props['triggerIcon'] ?? 'more-vertical')" class="size-4 text-kore-muted-fg" />
                </button>
            </x-slot:trigger>

            @foreach($actions as $action)
                @if($action->hasSeparator())
                    <x-kore::dropdown.separator />
                @endif

                @php
                    $dropdownStyle = $resolveDropdownStyle($action->getColor());
                @endphp

                @if($action->hasUrl())
                    <x-kore::dropdown.item
                        :icon="$action->getIcon()"
                        :label="$action->getLabel()"
                        :href="$action->getUrl($row)"
                        :style="$dropdownStyle ?: null"
                        :target="$action->opensInNewTab() ? '_blank' : null"
                        :rel="$action->opensInNewTab() ? 'noopener noreferrer' : null"
                    />
                @elseif($action->hasDispatch())
                    <x-kore::dropdown.item
                        :icon="$action->getIcon()"
                        :label="$action->getLabel()"
                        :style="$dropdownStyle ?: null"
                        x-on:click="window.dispatchEvent(new CustomEvent('{{ $action->getDispatchEvent() }}', { detail: {{ Js::from($action->getDispatchParams($row)) }}, bubbles: true }))"
                    />
                @elseif($action->getWireMethod())
                    @if($action->hasConfirm())
                        <x-kore::dropdown.item
                            :icon="$action->getIcon()"
                            :label="$action->getLabel()"
                            :style="$dropdownStyle ?: null"
                            x-on:click="window.dispatchEvent(new CustomEvent('kore:open', { detail: {{ Js::from($action->buildKoreConfirmPayload($row, $this->getId(), $primaryKey ?? 'id')) }}, bubbles: true }))"
                        />
                    @else
                        <x-kore::dropdown.item
                            :icon="$action->getIcon()"
                            :label="$action->getLabel()"
                            :style="$dropdownStyle ?: null"
                            wire:click="{{ $action->getWireMethod() }}({{ Js::from(data_get($row, $primaryKey ?? 'id')) }})"
                        />
                    @endif
                @endif
            @endforeach
        </x-kore::dropdown>
    @else
        <div class="inline-flex items-center gap-1">
            @foreach($actions as $action)
                @php
                    $c = $resolveColor($action->getColor());
                    $btnColorClass = $c['class'];
                    $btnColorStyle = $c['style'];
                @endphp

                @if($action->hasUrl())
                    <a
                        href="{{ $action->getUrl($row) }}"
                        @if($action->opensInNewTab()) target="_blank" rel="noopener noreferrer" @endif
                        class="p-1.5 rounded-kore-md transition-colors {{ $btnColorClass }}"
                        @if($btnColorStyle) style="{{ $btnColorStyle }}" @endif
                        aria-label="{{ $action->getLabel() }}"
                        title="{{ $action->getLabel() }}"
                    >
                        @if($action->getIcon())
                            <x-dynamic-component :component="'lucide-' . $action->getIcon()" class="size-4" />
                        @else
                            <span class="text-sm">{{ $action->getLabel() }}</span>
                        @endif
                    </a>
                @elseif($action->hasDispatch())
                    <button
                        type="button"
                        x-on:click="window.dispatchEvent(new CustomEvent('{{ $action->getDispatchEvent() }}', { detail: {{ Js::from($action->getDispatchParams($row)) }}, bubbles: true }))"
                        class="p-1.5 rounded-kore-md transition-colors {{ $btnColorClass }}"
                        @if($btnColorStyle) style="{{ $btnColorStyle }}" @endif
                        aria-label="{{ $action->getLabel() }}"
                        title="{{ $action->getLabel() }}"
                    >
                        @if($action->getIcon())
                            <x-dynamic-component :component="'lucide-' . $action->getIcon()" class="size-4" />
                        @else
                            <span class="text-sm">{{ $action->getLabel() }}</span>
                        @endif
                    </button>
                @elseif($action->getWireMethod())
                    <button
                        type="button"
                        @if($action->hasConfirm())
                            x-on:click="window.dispatchEvent(new CustomEvent('kore:open', { detail: {{ Js::from($action->buildKoreConfirmPayload($row, $this->getId(), $primaryKey ?? 'id')) }}, bubbles: true }))"
                        @else
                            {{-- `@js()` aquí sí, porque esto es un <button> normal y la
                                 directiva se compila en ESTA vista. En la rama del
                                 desplegable de arriba el mismo atributo va sobre un
                                 <x-kore::dropdown.item>: allí la directiva llega literal
                                 al hijo y acaba en el DOM sin compilar, así que se usa
                                 Js::from(). No unificar copiando esta línea. --}}
                            wire:click="{{ $action->getWireMethod() }}(@js(data_get($row, $primaryKey ?? 'id')))"
                        @endif
                        class="p-1.5 rounded-kore-md transition-colors {{ $btnColorClass }}"
                        @if($btnColorStyle) style="{{ $btnColorStyle }}" @endif
                        aria-label="{{ $action->getLabel() }}"
                        title="{{ $action->getLabel() }}"
                    >
                        @if($action->getIcon())
                            <x-dynamic-component :component="'lucide-' . $action->getIcon()" class="size-4" />
                        @else
                            <span class="text-sm">{{ $action->getLabel() }}</span>
                        @endif
                    </button>
                @endif
            @endforeach
        </div>
    @endif
@endif
