@php
    $props = $column->getComponentProps();
    $actions = $column->getVisibleActions($row);
    $isDropdown = ($props['displayMode'] ?? 'dropdown') === 'dropdown';
@endphp

@if(count($actions) > 0)
    @if($isDropdown)
        <x-kore::dropdown position="bottom-end" width="180px">
            <x-slot:trigger>
                <button type="button" class="p-1 rounded-kore-md hover:bg-kore-muted transition-colors">
                    <x-dynamic-component :component="'lucide-' . ($props['triggerIcon'] ?? 'more-vertical')" class="size-4 text-kore-muted-fg" />
                </button>
            </x-slot:trigger>

            @foreach($actions as $action)
                @if($action->hasSeparator())
                    <x-kore::dropdown.separator />
                @endif

                @if($action->hasUrl())
                    <x-kore::dropdown.item
                        :icon="$action->getIcon()"
                        :label="$action->getLabel()"
                        :href="$action->getUrl($row)"
                        @if($action->opensInNewTab()) target="_blank" rel="noopener noreferrer" @endif
                    />
                @elseif($action->hasDispatch())
                    <x-kore::dropdown.item
                        :icon="$action->getIcon()"
                        :label="$action->getLabel()"
                        x-on:click="window.dispatchEvent(new CustomEvent('{{ $action->getDispatchEvent() }}', { detail: {{ Js::from($action->getDispatchParams($row)) }}, bubbles: true }))"
                    />
                @elseif($action->getWireMethod())
                    @if($action->hasConfirm())
                        <x-kore::dropdown.item
                            :icon="$action->getIcon()"
                            :label="$action->getLabel()"
                            x-on:click="window.dispatchEvent(new CustomEvent('kore:open', { detail: {{ Js::from($action->buildKoreConfirmPayload($row, $this->getId(), $primaryKey ?? 'id')) }}, bubbles: true }))"
                        />
                    @else
                        <x-kore::dropdown.item
                            :icon="$action->getIcon()"
                            :label="$action->getLabel()"
                            wire:click="{{ $action->getWireMethod() }}('{{ data_get($row, $primaryKey ?? 'id') }}')"
                        />
                    @endif
                @endif
            @endforeach
        </x-kore::dropdown>
    @else
        <div class="inline-flex items-center gap-1">
            @foreach($actions as $action)
                @php
                    $btnColorClass = match($action->getColor()) {
                        'destructive' => 'text-kore-destructive hover:bg-kore-destructive/10',
                        'success' => 'text-kore-success hover:bg-kore-success/10',
                        'warning' => 'text-kore-warning hover:bg-kore-warning/10',
                        'info' => 'text-kore-info hover:bg-kore-info/10',
                        default => 'text-kore-primary hover:bg-kore-primary/10',
                    };
                @endphp

                @if($action->hasUrl())
                    <a
                        href="{{ $action->getUrl($row) }}"
                        @if($action->opensInNewTab()) target="_blank" rel="noopener noreferrer" @endif
                        class="p-1.5 rounded-kore-md transition-colors {{ $btnColorClass }}"
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
                            wire:click="{{ $action->getWireMethod() }}('{{ data_get($row, $primaryKey ?? 'id') }}')"
                        @endif
                        class="p-1.5 rounded-kore-md transition-colors {{ $btnColorClass }}"
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
