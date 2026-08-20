# Tooltip

Tooltip ligero con CSS puro para posicionamiento y Alpine.js para show/hide.

## Uso básico

```blade
<x-kore::tooltip text="Información adicional">
    <x-kore::button label="Hover me" />
</x-kore::tooltip>
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `text` | `string\|null` | `null` | Texto del tooltip |
| `position` | `string` | `config(top)` | Posición: `top`, `right`, `bottom`, `left` |
| `delay` | `int` | `config(200)` | Delay en ms antes de mostrar |

## Posiciones

```blade
<x-kore::tooltip text="Arriba" position="top">...</x-kore::tooltip>
<x-kore::tooltip text="Abajo" position="bottom">...</x-kore::tooltip>
<x-kore::tooltip text="Izquierda" position="left">...</x-kore::tooltip>
<x-kore::tooltip text="Derecha" position="right">...</x-kore::tooltip>
```

## Accesibilidad

- **El control que lo dispara lleva `aria-describedby`** apuntando a un `<span class="sr-only">` con el texto, dentro del propio componente. Sin eso el tooltip no existía para un lector de pantalla: el panel está teleportado a `<body>`, lejos del control, y nadie apuntaba a él.
- **El panel flotante es decorativo** (`aria-hidden`): el texto ya lo lee el `sr-only`, y con los dos se leería dos veces.
- Se muestra con hover y con foco; se oculta con `mouseleave`, `blur` y **`Escape`** (WCAG 1.4.13), sin mover el foco.
- El `Escape` solo se marca como atendido si había algo abierto: con el tooltip cerrado, la tecla sigue su camino hasta quien la esperara —un modal, por ejemplo—.

> **Pon algo enfocable en el slot.** El `aria-describedby` va sobre el primer control que encuentre dentro (`button`, `a[href]`, un campo…). Envolviendo texto suelto, el tooltip solo se abre con el ratón y nadie más se entera de que existe.

## Notas de implementación

El texto accesible vive junto al control y no en el panel, y no es una preferencia de estilo: **darle un `id` al nodo teleportado rompía el DataTable**. El panel acaba en `<body>` mientras el `<template>` que lo declara sigue en su celda, así que al re-renderizar la tabla el morph emparejaba por id el nodo del HTML nuevo con el que ya colgaba de `<body>` y lo arrancaba de su ámbito de Alpine —`ReferenceError: show is not defined`, con veinticinco tooltips en una página—. Asignar el id desde JavaScript no lo arreglaba: pedir `$refs.tooltip` durante el montaje dejaba paneles sin ámbito por su cuenta.

Por eso el JavaScript de este componente **no toca `$refs.tooltip` fuera de `open()`**.
