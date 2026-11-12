# Movimiento

La librería anima los cambios de color y de foco, la apertura de paneles (menú, select, calendario), acordeones y pestañas, el interruptor, y la entrada de modales y toasts. Todo es opcional.

## Apagarlo

Hay tres niveles. Los tres hacen lo mismo —poner a cero los tokens de duración—, así que se pueden combinar.

### Toda la aplicación

```php
// config/kore-ui.php
'ui' => [
    'motion' => false,
],
```

No hay que tocar el layout: `@koreScripts` lo aplica a `<html>`.

### Una zona

```blade
<section data-kore-motion="off">
    <x-kore::select … />
    <x-kore::datepicker … />
</section>
```

Vale en cualquier elemento. Los paneles que se abren desde dentro —la lista del select, el calendario— también se quedan sin animación aunque vivan teleportados a `<body>`.

### Un componente

```blade
<x-kore::dropdown :animated="false">…</x-kore::dropdown>
<x-kore::button label="Guardar" :animated="false" />
<x-kore::toggle wire:model="activo" :animated="false" />
```

Lo aceptan los componentes interactivos: `button`, `input` y el resto de campos, `select`, `datepicker`, `time-picker`, `color-picker`, `checkbox`, `radio`, `toggle`, `dropdown`, `tooltip`, `accordion`, `tab`, `stepper`, `card`, `alert`, `carousel`, `tree`, `sidebar`, `stats`…

En `progress`, `animated` sigue siendo la animación de las rayas, como en la 2.x.

## Lo que no se apaga

El spinner de carga. Es la única señal de que algo está pasando; quieto, es un círculo que no dice nada.

## `prefers-reduced-motion`

Si el usuario pide menos movimiento en su sistema, la librería lo respeta sin configurar nada: las duraciones pasan a cero y el spinner se ralentiza.

## Tokens

| Token | Valor | Para qué |
|---|---|---|
| `--kore-duration-fast` | 120 ms | Lo que responde al puntero o al foco |
| `--kore-duration-base` | 180 ms | Lo que aparece o cambia de estado: paneles, interruptor |
| `--kore-duration-slow` | 280 ms | Lo que desplaza contenido: modal, drawer, acordeón |
| `--kore-ease-out` | curva | Entradas y cambios de estado |
| `--kore-ease-in` | curva | Salidas |
| `--kore-ease-in-out` | curva | Movimientos de ida y vuelta |
| `--kore-press-scale` | 0.975 | Cuánto se encoge un botón al pulsarlo |
| `--kore-pop-scale` | 0.96 | Desde qué escala entra un panel |
| `--kore-sidebar-duration` | 200 ms | Colapso del sidebar |
| `--kore-spring-duration` | 500 ms | Resorte del toast |
| `--kore-chart-tween` | 400 ms | Gráficos en vivo |

```css
:root {
    --kore-duration-base: 240ms;
    --kore-press-scale: 1;   /* sin efecto de pulsación */
}
```

## Animar un elemento propio

Las utilidades de entrada funcionan con cualquier `x-show`, sin escribir `x-transition`:

```blade
<div x-show="abierto" class="kore-pop">…</div>         {{-- escala y opacidad --}}
<div x-show="abierto" class="kore-fade">…</div>        {{-- solo opacidad --}}
<div x-show="abierto" class="kore-slide-down">…</div>  {{-- baja unos píxeles --}}
<div x-show="abierto" class="kore-drawer">…</div>      {{-- entra desde un lado --}}
```

Usan `@starting-style` y `transition-behavior: allow-discrete`. En un navegador que no los soporte, el elemento aparece y desaparece sin fundido.
