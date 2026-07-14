{{-- El payload SOLO se emite si hay tooltip: es una segunda copia del dato en el DOM y a
     2.000 puntos pesa más que el propio <path>. Va en un <script type="application/json">, no
     en un atributo: el JSON en un atributo escapa cada comilla a &quot; — seis bytes por comilla. --}}
<script type="application/json" data-kore-chart-payload="{{ $chartId }}">@json($plot->payload())</script>

@if($plot->frame->tooltip['crosshair'] ?? true)
    <div class="kore-chart-crosshair" x-ref="crosshair" x-show="hover !== null" x-cloak
         style="--kx: 0" aria-hidden="true"></div>
@endif

{{-- ⚠️ wire:ignore NO es opcional. Medido: el morph de Livewire BORRA cualquier style inline
     que escriba el cliente, y floating-ui posiciona con style inline. Sin esto, el tooltip
     salta a la esquina en cuanto Livewire actualiza cualquier cosa de la página. --}}
<div wire:ignore
     class="kore-chart-tooltip"
     x-ref="tooltip"
     x-show="hover !== null"
     x-cloak
     role="tooltip">
    <div class="kore-chart-tooltip-title" x-text="hover?.label"></div>
    <template x-for="row in (hover?.rows ?? [])" :key="row.id">
        <div class="kore-chart-tooltip-row">
            <span class="kore-chart-tooltip-dot" :style="`--kore-series: var(--kore-chart-${row.slot})`"></span>
            <span class="kore-chart-tooltip-name" x-text="row.name"></span>
            <span class="kore-chart-tooltip-value" x-text="row.value"></span>
        </div>
    </template>
</div>
