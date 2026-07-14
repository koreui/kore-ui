# Chart Legend

La leyenda. No es un adorno: son botones, y al pulsarlos se oculta la serie.

## Uso básico

```blade
<x-kore::chart :data="$ventas" x="mes">
    <x-kore::chart.line y="ingresos" label="Ingresos" />
    <x-kore::chart.line y="gastos"   label="Gastos" />
    <x-kore::chart.legend />
</x-kore::chart>
```

## Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `position` | `string` | `top` | `top` o `bottom` |

## Accesibilidad

Cada entrada es un `<button>` con `aria-pressed`. **La identidad de la serie nunca depende solo del color**: el nombre va escrito al lado.

## Limitaciones

**Ocultar una serie no reescala los ejes.** La serie desaparece, pero el eje se queda como estaba.

Bajo Livewire, el estado de "serie oculta" lo mantiene el navegador y el servidor no lo conoce — así que el componente lo vuelve a aplicar después de cada morph. Si no lo hiciera, la serie oculta reaparecería en cuanto se actualizara cualquier cosa de la página.
