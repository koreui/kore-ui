@php
    $koreSlots = $this->getTableSlots();
    $densityClass = match($density ?? 'normal') {
        'compact'  => 'px-3 py-1 text-sm',
        'relaxed'  => 'px-4 py-4 text-base',
        default    => 'px-4 py-2.5 text-sm',
    };
    $headerDensityClass = match($density ?? 'normal') {
        'compact'  => 'px-3 py-1.5 text-xs',
        'relaxed'  => 'px-4 py-3 text-sm',
        default    => 'px-4 py-2 text-xs',
    };
@endphp
<div
    data-kore-datatable
    x-data="KoreDataTable({ density: '{{ $density }}', rowIds: {{ Js::from($rowIds ?? []) }}, slideDownOpen: {{ ($filtersExpanded ?? false) ? 'true' : 'false' }}, responsiveMode: '{{ $responsiveMode ?? 'scroll' }}', responsiveBreakpoint: {{ $responsiveBreakpoint ?? 768 }}, totalRows: {{ (int) ((($selectionEnabled ?? false) && ($rows ?? null) !== null && method_exists($rows, 'total')) ? $rows->total() : 0) }} })"
    class="rounded-kore-lg border border-kore-border bg-kore-surface overflow-hidden"
>
    {{-- Slot: before-toolbar --}}
    @if($slot = ($koreSlots['before-toolbar'] ?? null))
        @include($slot['view'], array_merge(['component' => $this], $slot['params']))
    @endif

    {{-- Filter Presets --}}
    @include('kore::datatable.filter-presets', [
        'presets'      => $presets ?? [],
        'activePreset' => $activePreset ?? null,
        'presetCounts' => $presetCounts ?? [],
    ])

    {{-- Toolbar: search + filters + per page + bulk actions + export --}}
    @include('kore::datatable.toolbar', [
        'koreSlots'           => $koreSlots,
        'searchDebounce'      => $searchDebounce,
        'perPageOptions'      => $perPageOptions,
        'perPage'             => $perPage,
        'translations'        => $translations,
        'filterDefs'          => $filterDefs ?? [],
        'filterCount'         => $filterCount ?? 0,
        'filterLayout'        => $filterLayout ?? 'popover',
        'filtersExpanded'     => $filtersExpanded ?? false,
        'bulkActions'         => $bulkActions ?? [],
        'columnSelectEnabled' => $columnSelectEnabled ?? false,
        'allColumns'          => $allColumns ?? [],
        'deselectedColumns'   => $deselectedColumns ?? [],
        'savedViewsEnabled'   => $savedViewsEnabled ?? false,
        'savedViews'          => $savedViews ?? [],
        'exportEnabled'       => $exportEnabled ?? false,
        'exportFormats'       => $exportFormats ?? [],
        'rowIds'              => $rowIds ?? [],
        'total'               => $total ?? 0,
    ])

    {{-- Slot: after-toolbar --}}
    @if($slot = ($koreSlots['after-toolbar'] ?? null))
        @include($slot['view'], array_merge(['component' => $this], $slot['params']))
    @endif

    {{-- Filter pills --}}
    @include('kore::datatable.filter-pills', [
        'activeFilters' => $activeFilters ?? [],
        'translations'  => $translations,
    ])

    {{-- Sort pills --}}
    @include('kore::datatable.sort-pills', [
        'activeSorts'  => $activeSorts ?? [],
        'translations' => $translations,
    ])

    {{-- Deferred loading skeleton --}}
    @if(($deferredLoading ?? false) && !($dataLoaded ?? true))
        <div wire:init="loadData">
            <table class="min-w-full divide-y divide-kore-border">
                <thead class="bg-kore-muted/50">
                    <tr>
                        @foreach($columns as $column)
                            <th class="text-left {{ $headerDensityClass }}" :class="headerDensityClasses">
                                <div class="h-3 w-20 bg-kore-muted rounded animate-pulse"></div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-kore-border">
                    @for($i = 0; $i < min($this->perPage, 10); $i++)
                        <tr>
                            @foreach($columns as $column)
                                <td class="{{ $densityClass }}" :class="densityClasses">
                                    <x-kore::skeleton class="h-4 w-full" />
                                </td>
                            @endforeach
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    @else
        {{-- Table --}}
        @php
            // Pre-calculate pinned column offsets
            $pinnedLeftOffset = 0;
            $pinnedLeftOffsets = [];
            $pinnedRightOffset = 0;
            $pinnedRightOffsets = [];
            $pinnedRightColumns = [];

            foreach ($columns as $idx => $col) {
                if ($col->isPinned() && $col->getPinnedSide() === 'left') {
                    $pinnedLeftOffsets[$idx] = $pinnedLeftOffset;
                    $pinnedLeftOffset += ($col->getWidth() ?? 150);
                }
                if ($col->isPinned() && $col->getPinnedSide() === 'right') {
                    $pinnedRightColumns[$idx] = $col;
                }
            }

            // Calculate right offsets in reverse
            foreach (array_reverse($pinnedRightColumns, true) as $idx => $col) {
                $pinnedRightOffsets[$idx] = $pinnedRightOffset;
                $pinnedRightOffset += ($col->getWidth() ?? 150);
            }

            // Track last pinned-left and first pinned-right for shadow
            $lastPinnedLeft = null;
            $firstPinnedRight = null;
            foreach ($columns as $idx => $col) {
                if ($col->isPinned() && $col->getPinnedSide() === 'left') {
                    $lastPinnedLeft = $idx;
                }
            }
            foreach ($columns as $idx => $col) {
                if ($col->isPinned() && $col->getPinnedSide() === 'right') {
                    $firstPinnedRight = $idx;
                    break;
                }
            }

            $hasPinnedColumns = !empty($pinnedLeftOffsets) || !empty($pinnedRightOffsets);

            // Con table-layout: fixed los anchos declarados solo se respetan si
            // la tabla tiene sitio para todos. Como el contenedor suele ser más
            // estrecho que su suma, el navegador los reparte proporcionalmente y
            // el width() vuelve a ser una sugerencia. Se le da a la tabla un
            // min-width igual a la suma para que sean exactos y el wrapper haga
            // scroll horizontal, que es justo lo que se pide al fijar anchos.
            $koreMinWidth = null;
            if (($tableLayout ?? 'auto') === 'fixed') {
                $koreMinWidth = ($selectionEnabled ?? false) ? 40 : 0;
                foreach ($columns as $col) {
                    $koreMinWidth += $col->getWidth() ?? $col->getMinWidth() ?? 150;
                }
            }

            // Per-column sticky style + edge shadow, computed ONCE. The header,
            // body and footer differ only in background color (muted vs surface),
            // so they prepend that themselves; everything else is shared here.
            // (A pinned column lives on a single side, so a cell is never both the
            // last-left and first-right — the shadow assignment can't collide.)
            $pinnedMeta = [];
            foreach ($columns as $idx => $col) {
                if (! $col->isPinned()) {
                    continue;
                }
                $side = $col->getPinnedSide();
                $style = '';
                if ($side === 'left' && isset($pinnedLeftOffsets[$idx])) {
                    $style = "position: sticky; left: {$pinnedLeftOffsets[$idx]}px; z-index: 1;";
                } elseif ($side === 'right' && isset($pinnedRightOffsets[$idx])) {
                    $style = "position: sticky; right: {$pinnedRightOffsets[$idx]}px; z-index: 1;";
                }
                $shadow = '';
                if ($idx === $lastPinnedLeft) {
                    $shadow = 'after:absolute after:top-0 after:right-0 after:bottom-0 after:w-px after:bg-kore-border after:shadow-[2px_0_4px_var(--kore-pin-shadow)]';
                } elseif ($idx === $firstPinnedRight) {
                    $shadow = 'before:absolute before:top-0 before:left-0 before:bottom-0 before:w-px before:bg-kore-border before:shadow-[-2px_0_4px_var(--kore-pin-shadow)]';
                }
                $pinnedMeta[$idx] = ['side' => $side, 'style' => $style, 'shadow' => $shadow];
            }
        @endphp

        {{-- z-index scale: body 0 · pinned cells 1 · sticky thead 20 · loading overlay 30 · teleported dropdowns 50 · drawer 60 --}}
        {{-- Ancla del overlay. Tiene que ser un padre SIN scroll: un `absolute inset-0` dentro
             del contenedor scrolleable se desplaza junto al contenido, y en cuanto el usuario
             hace scroll horizontal deja las columnas de la derecha sin tapar. --}}
        <div class="relative">
            {{-- Loading overlay. Las dos piezas de abajo son OBLIGATORIAS juntas:

                 1. `.flex` — wire:loading escribe el display en el style INLINE (inline-block por
                    defecto), que pisaría la clase `flex` y dejaría el spinner arriba a la izquierda.
                 2. `style="display: none"` — Livewire oculta estos overlays en el primer render con
                    un <style> que lista los selectores de atributo UNO A UNO
                    (`[wire\:loading]`, `[wire\:loading\.delay]`, `[wire\:loading\.flex]`, …).
                    No existe selector para la combinación delay + display, así que
                    `[wire:loading.delay.flex]` NO encaja con ninguno y nunca recibiría su
                    display:none inicial: el overlay nacería visible y se quedaría pegado para
                    siempre sobre la tabla, porque el JS solo lo apaga al TERMINAR una petición.

                 z-30 lo mantiene por encima del thead sticky (z-20). --}}
            <div wire:loading.delay.flex style="display: none" class="absolute inset-0 z-30 flex items-center justify-center bg-kore-surface/80 backdrop-blur-[1px]">
                {{-- Sin anunciar: la paginación de abajo ya tiene su `aria-live`
                     con el recuento, y con los dos un lector oía «Cargando» y a
                     continuación «Mostrando 1 de 1» en cada filtrado. --}}
                <x-kore::loading size="md" :announce="false" />
            </div>

        {{-- maxHeight → scroll vertical interno para que el header sticky funcione (overflow-x rompe el sticky relativo al viewport) --}}
        <div
            data-table-wrapper
            class="relative {{ ($maxHeight ?? null) ? 'overflow-auto' : 'overflow-x-auto' }}"
            @if($maxHeight ?? null) style="max-height: {{ $maxHeight }}px" @endif
        >

            {{-- Card mode (responsive). El @if del servidor decide si el HTML se
                 emite; el x-show decide si se ve. En la primera carga el
                 servidor todavía no sabe el ancho y manda las dos variantes;
                 desde el segundo render manda solo la que toca. --}}
            @if(($responsiveMode ?? 'scroll') === 'card' && $rows !== null && $this->shouldRenderMobile())
                <div x-show="isMobileView" x-cloak>
                    @include('kore::datatable.responsive.card', [
                        'columns' => $columns,
                        'rows' => $rows,
                        'selectionEnabled' => $selectionEnabled ?? false,
                        'primaryKey' => $primaryKey ?? 'id',
                        'rowIds' => $rowIds ?? [],
                    ])
                </div>
            @endif

            {{-- Collapse mode (responsive) --}}
            @if(($responsiveMode ?? 'scroll') === 'collapse' && $rows !== null && $this->shouldRenderMobile())
                <div x-show="isMobileView" x-cloak>
                    @include('kore::datatable.responsive.collapse', [
                        'columns' => $columns,
                        'collapsedColumns' => $collapsedColumns ?? [],
                        'rows' => $rows,
                        'selectionEnabled' => $selectionEnabled ?? false,
                        'primaryKey' => $primaryKey ?? 'id',
                        'rowIds' => $rowIds ?? [],
                    ])
                </div>
            @endif

            @if($this->shouldRenderTable())
            <table
                @if(($responsiveMode ?? 'scroll') !== 'scroll') x-show="!isMobileView" @endif
                @if(($tableLayout ?? 'auto') === 'fixed') style="table-layout: fixed; min-width: {{ $koreMinWidth }}px" @endif
                class="min-w-full divide-y divide-kore-border"
            >
                {{-- Header (sticky: stays visible on vertical scroll; opaque bg so rows don't bleed through) --}}
                <thead class="bg-kore-muted sticky top-0 z-20">
                    <tr>
                        @if($selectionEnabled ?? false)
                            <th class="w-10 text-center {{ $headerDensityClass }}" :class="headerDensityClasses">
                                <input
                                    type="checkbox"
                                    aria-label="Seleccionar todo"
                                    @checked($this->isPageFullySelected($rowIds ?? []))
                                    data-checked="{{ $this->isPageFullySelected($rowIds ?? []) ? '1' : '0' }}"
                                    data-indeterminate="{{ $this->isPagePartiallySelected($rowIds ?? []) ? '1' : '0' }}"
                                    x-init="$el.indeterminate = $el.dataset.indeterminate === '1'"
                                    wire:click="toggleSelectAll"
                                    class="rounded border-kore-input text-kore-primary focus:ring-kore-ring"
                                />
                            </th>
                        @endif

                        @foreach($columns as $colIdx => $column)
                            @php
                                $isPinned = $column->isPinned();
                                $pinnedSide = $column->getPinnedSide();
                                $pinnedStyle = $pinnedMeta[$colIdx]['style'] ?? '';
                                $pinnedClasses = $isPinned ? trim('bg-kore-muted ' . ($pinnedMeta[$colIdx]['shadow'] ?? '')) : '';

                                $widthStyle = '';
                                if ($column->getWidth()) {
                                    $widthStyle .= "width: {$column->getWidth()}px;";
                                }
                                if ($column->getMinWidth()) {
                                    $widthStyle .= "min-width: {$column->getMinWidth()}px;";
                                }
                                if ($column->getMaxWidth()) {
                                    $widthStyle .= "max-width: {$column->getMaxWidth()}px;";
                                }
                                $truncateClass = $column->getMaxWidth() ? 'truncate' : 'whitespace-nowrap';
                                $ariaSort = null;
                                if ($column->isSortable()) {
                                    $d = $this->getSortDirection($column->getSortField());
                                    $ariaSort = $d === 'asc' ? 'ascending' : ($d === 'desc' ? 'descending' : 'none');
                                }
                            @endphp
                            <th
                                scope="col"
                                @if($isPinned) data-pinned="{{ $pinnedSide }}" data-col-index="{{ $colIdx }}" @endif
                                @if($ariaSort) aria-sort="{{ $ariaSort }}" @endif
                                class="{{ $column->getAlign() === 'center' ? 'text-center' : ($column->getAlign() === 'right' ? 'text-right' : 'text-left') }} font-semibold text-kore-muted-fg uppercase tracking-wider {{ $truncateClass }} {{ $pinnedClasses }} {{ $headerDensityClass }}"
                                :class="headerDensityClasses"
                                style="{{ $pinnedStyle }}{{ $widthStyle }}"
                            >
                                <div class="inline-flex items-center gap-0.5 max-w-full">
                                    @if($column->isSortable())
                                        @php
                                            $sortDir = $this->getSortDirection($column->getSortField());
                                            $sortIcon = match($sortDir) {
                                                'asc'   => 'arrow-up',
                                                'desc'  => 'arrow-down',
                                                default => 'arrow-up-down',
                                            };
                                            $sortIconClass = $sortDir
                                                ? 'size-3.5 text-kore-fg'
                                                : 'size-3.5 text-kore-muted-fg/50 group-hover:text-kore-muted-fg transition-colors';
                                        @endphp
                                        <button
                                            type="button"
                                            wire:click="sortBy(@js($column->getSortField()))"
                                            aria-label="Ordenar por {{ $column->getLabel() }}"
                                            class="inline-flex items-center gap-1 group hover:text-kore-fg transition-colors min-w-0 uppercase"
                                        >
                                            <span class="truncate">{{ $column->getLabel() }}</span>
                                            <x-dynamic-component :component="'lucide-' . $sortIcon" :class="$sortIconClass" />
                                        </button>
                                    @else
                                        <span class="truncate">{{ $column->getLabel() }}</span>
                                    @endif

                                    {{-- Menú de columna: ordenar, fijar y ocultar sin salir de la cabecera --}}
                                    @if(($columnMenuEnabled ?? false) && $column->getType() !== 'action')
                                        @include('kore::datatable.column-menu', [
                                            'column'              => $column,
                                            'translations'        => $translations ?? [],
                                            'columnSelectEnabled' => $columnSelectEnabled ?? false,
                                        ])
                                    @endif
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>

                {{-- Body --}}
                <tbody class="divide-y divide-kore-border">
                    @if($rows !== null)
                        @forelse($rows as $row)
                            @php
                                $rowId = data_get($row, $primaryKey ?? 'id');
                                $rowSelected = ($selectionEnabled ?? false) && $this->isRowSelected($rowId);
                            @endphp
                            <tr
                                wire:key="row-{{ $rowId }}"
                                data-row-id="{{ $rowId }}"
                                @if($rowSelected) aria-selected="true" @endif
                                class="hover:bg-kore-muted/40 transition-colors {{ $rowSelected ? 'bg-kore-primary/5' : '' }}"
                                x-bind:class="{
                                    'ring-2 ring-inset ring-kore-primary/50 bg-kore-primary/5': keyboardMode && activeRowId === '{{ $rowId }}'
                                }"
                            >
                                @if($selectionEnabled ?? false)
                                    <td class="w-10 text-center {{ $densityClass }}" :class="densityClasses">
                                        <input
                                            type="checkbox"
                                            aria-label="Seleccionar fila"
                                            value="{{ $rowId }}"
                                            @checked($rowSelected)
                                            data-checked="{{ $rowSelected ? '1' : '0' }}"
                                            x-on:click="onRowCheckboxClick(@js($rowId), $event)"
                                            class="rounded border-kore-input text-kore-primary focus:ring-kore-ring"
                                        />
                                    </td>
                                @endif

                                @foreach($columns as $colIdx => $column)
                                    @php
                                        $isEditableCell = $column->isEditable() && $column->getType() !== 'boolean';
                                        $isPinned = $column->isPinned();
                                        $pinnedSide = $column->getPinnedSide();
                                        $pinnedStyle = $pinnedMeta[$colIdx]['style'] ?? '';
                                        $pinnedClasses = $isPinned ? trim('bg-kore-surface ' . ($pinnedMeta[$colIdx]['shadow'] ?? '')) : '';

                                        // Copyable/clickable only when NOT editable. Columns that render
                                        // their own interactive markup (e.g. ColorColumn's own copy button)
                                        // must NOT be wrapped in the generic copy/link <button> — nested
                                        // <button>s break the DOM and the inner Alpine scope.
                                        $selfRendering = in_array($column->getType(), ['color', 'component', 'action'], true);
                                        $showCopyable = !$isEditableCell && !$selfRendering && $column->isCopyable();
                                        $showClickable = !$isEditableCell && !$selfRendering && $column->isClickable();

                                        // maxWidth → truncate with ellipsis (full value exposed via title)
                                        $cellWidthStyle = $column->getMaxWidth() ? "max-width: {$column->getMaxWidth()}px;" : '';
                                        $cellTruncate = $column->getMaxWidth() ? 'truncate' : ($column->isWrap() ? '' : 'whitespace-nowrap');
                                        $cellTitle = ($column->getMaxWidth() && $column->getType() === 'text' && ! $column->isHtml()) ? (string) $column->getValue($row) : null;
                                    @endphp
                                    <td
                                        @if($isPinned) data-pinned="{{ $pinnedSide }}" data-col-index="{{ $colIdx }}" @endif
                                        @if($cellTitle !== null) title="{{ $cellTitle }}" @endif
                                        class="{{ $column->getAlign() === 'center' ? 'text-center' : ($column->getAlign() === 'right' ? 'text-right' : 'text-left') }} text-kore-fg {{ $cellTruncate }} {{ $isEditableCell ? 'cursor-pointer' : '' }} {{ $pinnedClasses }} relative {{ $densityClass }}"
                                        {{-- El índice cuenta la columna de checkboxes cuando existe, para
                                             que cuadre con el recorrido de moveRight(). --}}
                                        x-bind:class="isActiveCell(@js((string) $rowId), {{ $colIdx + (($selectionEnabled ?? false) ? 1 : 0) }})
                                            ? 'ring-2 ring-inset ring-kore-primary bg-kore-primary/10 ' + densityClasses
                                            : densityClasses"
                                        @if($pinnedStyle || $cellWidthStyle) style="{{ $pinnedStyle }}{{ $cellWidthStyle }}" @endif
                                    >
                                        @include('kore::datatable.cell-description', ['column' => $column, 'row' => $row, 'slot' => 'above'])
                                        @if($column->getType() === 'boolean' && $column->isEditable())
                                            {{-- Boolean editable: toggle with local state --}}
                                            <div
                                                x-data="{ value: {{ $column->getValue($row) ? 'true' : 'false' }} }"
                                                data-boolean-toggle
                                                @kore:datatable-edit-error.window="if ($event.detail.rowId == @js((string) $rowId) && $event.detail.field === @js($column->getField())) value = !value"
                                                wire:key="bool-{{ $rowId }}-{{ $column->getField() }}-{{ $column->getValue($row) ? '1' : '0' }}"
                                            >
                                                <button
                                                    type="button"
                                                    x-on:click="value = !value; $wire.updateCell(@js((string) $rowId), @js($column->getField()), value)"
                                                    class="inline-flex hover:opacity-75 transition-opacity cursor-pointer"
                                                >
                                                    @php
                                                        $boolProps = $column->getComponentProps();
                                                        $trueColor = $boolProps['trueColor'] ?? 'success';
                                                        $falseColor = $boolProps['falseColor'] ?? 'muted';
                                                        $size = $boolProps['size'] ?? 'md';
                                                        $sizeClass = match($size) { 'sm' => 'size-4', 'lg' => 'size-6', default => 'size-5' };
                                                    @endphp
                                                    <x-lucide-check-circle x-show="value" class="{{ $sizeClass }} text-kore-{{ $trueColor }}" />
                                                    <x-lucide-x-circle x-show="!value" class="{{ $sizeClass }} text-kore-{{ $falseColor }}" />
                                                </button>
                                            </div>
                                        @elseif($isEditableCell)
                                            {{-- Editable cell with local Alpine state --}}
                                            @php $rawValue = data_get($row, $column->getField()); @endphp
                                            <div x-data="{ editing: false }" class="relative">
                                                {{-- Display value --}}
                                                <div
                                                    data-editable-display
                                                    x-show="!editing"
                                                    x-on:click="editing = true; $nextTick(() => { if($refs.editInput) { $refs.editInput.focus(); $refs.editInput.select && $refs.editInput.select(); } })"
                                                    class="min-h-[1.5em] hover:bg-kore-muted/60 rounded px-1 -mx-1 transition-colors cursor-pointer group"
                                                >
                                                    @if($column->getType() !== 'text')
                                                        @include('kore::datatable.columns.' . $column->getType(), [
                                                            'column' => $column,
                                                            'row' => $row,
                                                            'value' => $column->getValue($row),
                                                            'primaryKey' => $primaryKey ?? 'id',
                                                        ])
                                                    @elseif($column->isHtml())
                                                        {!! $column->getValue($row) !!}
                                                    @else
                                                        {{ $column->getValue($row) }}
                                                    @endif
                                                    <x-lucide-pencil class="size-3 text-kore-muted-fg opacity-0 group-hover:opacity-100 transition-opacity inline ml-1" />
                                                </div>

                                                {{-- Edit input --}}
                                                <div x-show="editing" style="display:none">
                                                    @if($column->getEditableComponent() === 'textarea')
                                                        <textarea
                                                            x-ref="editInput"
                                                            x-on:keydown.enter.prevent="$wire.updateCell(@js((string) $rowId), @js($column->getField()), $el.value); editing = false"
                                                            x-on:keydown.escape.prevent="editing = false"
                                                            x-on:blur="if(editing) { $wire.updateCell(@js((string) $rowId), @js($column->getField()), $el.value); editing = false }"
                                                            class="w-full rounded-kore-md border border-kore-input bg-kore-bg text-kore-fg text-sm px-2 py-1 focus:outline-none focus:ring-2 focus:ring-kore-ring focus:border-kore-primary"
                                                            rows="2"
                                                        >{{ $rawValue }}</textarea>
                                                    @elseif($column->getEditableComponent() === 'select')
                                                        <select
                                                            x-ref="editInput"
                                                            x-on:change="$wire.updateCell(@js((string) $rowId), @js($column->getField()), $el.value); editing = false"
                                                            x-on:keydown.escape.prevent="editing = false"
                                                            x-on:blur="if(editing) { editing = false }"
                                                            class="w-full rounded-kore-md border border-kore-input bg-kore-bg text-kore-fg text-sm px-2 py-1 focus:outline-none focus:ring-2 focus:ring-kore-ring focus:border-kore-primary"
                                                        >
                                                            @foreach($column->getEditableOptions() as $optValue => $optLabel)
                                                                <option value="{{ $optValue }}" @selected($optValue == $rawValue)>{{ $optLabel }}</option>
                                                            @endforeach
                                                        </select>
                                                    @else
                                                        <input
                                                            type="{{ $column->getEditableInputType() }}"
                                                            x-ref="editInput"
                                                            value="{{ e($rawValue) }}"
                                                            @if($column->getEditableInputType() === 'number') step="any" @endif
                                                            x-on:keydown.enter.prevent="$wire.updateCell(@js((string) $rowId), @js($column->getField()), $el.value); editing = false"
                                                            x-on:keydown.escape.prevent="editing = false"
                                                            x-on:blur="if(editing) { $wire.updateCell(@js((string) $rowId), @js($column->getField()), $el.value); editing = false }"
                                                            class="w-full rounded-kore-md border border-kore-input bg-kore-bg text-kore-fg text-sm px-2 py-1 focus:outline-none focus:ring-2 focus:ring-kore-ring focus:border-kore-primary"
                                                        />
                                                    @endif
                                                </div>
                                            </div>
                                        @elseif($showCopyable)
                                            {{-- Copyable cell --}}
                                            @php $cellValue = $column->getValue($row); @endphp
                                            <button
                                                type="button"
                                                aria-label="{{ ($translations['copy'] ?? 'Copiar') . ' ' . $column->getLabel() }}"
                                                x-on:click="copyToClipboard(@js(is_string($cellValue) ? $cellValue : ''), @js($rowId . '-' . $column->getField()))"
                                                class="inline-flex items-center gap-1 group hover:text-kore-primary transition-colors"
                                            >
                                                @if($column->getType() !== 'text')
                                                    @include('kore::datatable.columns.' . $column->getType(), [
                                                        'column' => $column,
                                                        'row' => $row,
                                                        'value' => $cellValue,
                                                        'primaryKey' => $primaryKey ?? 'id',
                                                    ])
                                                @elseif($column->isHtml())
                                                    {!! $cellValue !!}
                                                @else
                                                    {{ $cellValue }}
                                                @endif
                                                @php $koreCopyKey = \Illuminate\Support\Js::from($rowId . '-' . $column->getField()); @endphp
                                                {{-- Js::from() y no @js(): dentro del atributo de un componente Blade
                                                     la directiva llega literal al hijo y se compila en SU vista, donde
                                                     $rowId no existe. {{ }} sí se resuelve en este scope, y no escapa
                                                     de más porque e() respeta Htmlable. --}}
                                                <x-lucide-copy class="size-3 text-kore-muted-fg opacity-0 group-hover:opacity-100 transition-opacity shrink-0" x-show="copyFeedback !== {{ $koreCopyKey }}" />
                                                <x-lucide-check class="size-3 text-kore-success shrink-0" x-show="copyFeedback === {{ $koreCopyKey }}" x-cloak />
                                            </button>
                                        @elseif($showClickable)
                                            {{-- Clickable cell --}}
                                            <a
                                                href="{{ $column->getClickableUrl($row) }}"
                                                @if($column->isClickableNewTab()) target="_blank" rel="noopener noreferrer" @endif
                                                class="text-kore-primary hover:text-kore-primary/80 underline underline-offset-2 transition-colors"
                                            >
                                                @if($column->getType() !== 'text')
                                                    @include('kore::datatable.columns.' . $column->getType(), [
                                                        'column' => $column,
                                                        'row' => $row,
                                                        'value' => $column->getValue($row),
                                                        'primaryKey' => $primaryKey ?? 'id',
                                                    ])
                                                @elseif($column->isHtml())
                                                    {!! $column->getValue($row) !!}
                                                @else
                                                    {{ $column->getValue($row) }}
                                                @endif
                                            </a>
                                        @elseif($column->getType() !== 'text')
                                            @include('kore::datatable.columns.' . $column->getType(), [
                                                'column' => $column,
                                                'row' => $row,
                                                'value' => $column->getValue($row),
                                                'primaryKey' => $primaryKey ?? 'id',
                                            ])
                                        @elseif($column->isHtml())
                                            {!! $column->getValue($row) !!}
                                        @else
                                            {{ $column->getValue($row) }}
                                        @endif
                                        @include('kore::datatable.cell-description', ['column' => $column, 'row' => $row, 'slot' => 'below'])
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($columns) + (($selectionEnabled ?? false) ? 1 : 0) }}">
                                    <x-kore::empty-state
                                        :title="$emptyText"
                                        :icon="$emptyIcon"
                                    />
                                </td>
                            </tr>
                        @endforelse
                    @endif
                </tbody>

                {{-- Footer (aggregations) --}}
                @if(! empty($aggregations ?? []))
                    <tfoot>
                        <tr class="bg-kore-muted/30 border-t border-kore-border">
                            @if($selectionEnabled ?? false)
                                <td class="w-10 {{ $densityClass }}" :class="densityClasses"></td>
                            @endif

                            @foreach($columns as $colIdx => $column)
                                @php
                                    $isPinned = $column->isPinned();
                                    $pinnedSide = $column->getPinnedSide();
                                    $pinnedStyle = $pinnedMeta[$colIdx]['style'] ?? '';
                                    $pinnedClasses = $isPinned ? trim('bg-kore-muted ' . ($pinnedMeta[$colIdx]['shadow'] ?? '')) : '';
                                @endphp
                                <td
                                    @if($isPinned) data-pinned="{{ $pinnedSide }}" data-col-index="{{ $colIdx }}" @endif
                                    class="{{ $column->getAlign() === 'center' ? 'text-center' : ($column->getAlign() === 'right' ? 'text-right' : 'text-left') }} font-semibold text-kore-fg relative {{ $pinnedClasses }} {{ $densityClass }}"
                                    :class="densityClasses"
                                    @if($pinnedStyle) style="{{ $pinnedStyle }}" @endif
                                >
                                    @if($column->hasAggregation() && isset($aggregations[$column->getField()]))
                                        @php $agg = $aggregations[$column->getField()]; @endphp
                                        @if($agg['label'])
                                            <span class="text-kore-muted-fg text-xs font-normal">{{ $agg['label'] }}:</span>
                                        @endif
                                        <span>{{ $agg['value'] }}</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    </tfoot>
                @endif
            </table>
            @endif
        </div>
        </div>{{-- /ancla del overlay --}}

        {{-- Pagination. Se incluye también cuando hay una sola página: el pie
             lleva el recuento de resultados y su aria-live, que no pueden
             desaparecer justo cuando un filtro reduce la tabla. Los controles de
             página sí se ocultan solos si no hacen falta. --}}
        @if($rows !== null && ($showingText !== null || (method_exists($rows, 'hasPages') && $rows->hasPages())))
            @include('kore::datatable.pagination', [
                'paginator'   => $rows,
                'showingText' => $showingText,
            ])
        @endif

        {{-- Slot: after-table --}}
        @if($slot = ($koreSlots['after-table'] ?? null))
            @include($slot['view'], array_merge(['component' => $this], $slot['params']))
        @endif
    @endif
</div>
