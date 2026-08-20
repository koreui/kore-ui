# Button Group

Agrupa varios `<x-kore::button>` en una sola pieza: redondea solo las esquinas exteriores y solapa los bordes contiguos.

```blade
<x-kore::button-group aria-label="Vista">
    <x-kore::button variant="outline" color="secondary" label="Lista" />
    <x-kore::button variant="outline" color="secondary" label="Tarjetas" />
    <x-kore::button variant="outline" color="secondary" label="Tabla" />
</x-kore::button-group>
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `ariaLabel` | `string\|null` | `config(Acciones)` | Nombre del grupo |

## Accesibilidad

Es un `role="group"` con nombre. Sin él eran tres botones sueltos para un lector, sin nada que dijera que van juntos ni dónde acaba el conjunto.

## Notas de implementación

Las esquinas se deciden con `:first-child` y `:last-child`, es decir, **en CSS**. Por eso un re-render de Livewire que añada o quite botones no puede descolocarlas: el navegador las recalcula solo. Está medido en `demo/e2e/specs/51-presentacion-semantica.spec.js`.
