# Shell

El layout de la aplicación. Coloca los sidebars, reserva el espacio del contenido y lo mantiene sincronizado cuando el sidebar se colapsa.

## Uso básico

```blade
<x-kore::shell>
    <x-slot:sidebar>
        <x-kore::sidebar>…</x-kore::sidebar>
    </x-slot:sidebar>

    <x-slot:navbar>
        <x-kore::navbar />
    </x-slot:navbar>

    {{ $slot }}
</x-kore::shell>
```

## Slots

| Slot | Descripción |
|------|-------------|
| `sidebar` | El sidebar principal (izquierda) |
| `aside` | Un segundo sidebar (derecha), por ejemplo un panel de herramientas |
| `navbar` | La barra superior. Va dentro de la columna de contenido, no sobre el sidebar |
| `default` | El contenido de la página. Se renderiza dentro de un `<main>` |

## Cómo sabe cuánto espacio reservar

El shell **no inspecciona el HTML** ni recibe props duplicadas: los sidebars se le anuncian solos al renderizarse.

Funciona porque Blade evalúa el contenido de los slots **antes** que la plantilla del componente que los contiene. Cuando le toca el turno al shell, cada `<x-kore::sidebar>` de sus slots ya se ha renderizado y ha registrado su anchura, su breakpoint y su estado.

Así, el `<main>` sale con el desplazamiento correcto **desde el servidor**, no calculado en el navegador.

## Dos anchuras, no una

El shell distingue entre:

- **Lo ancho que se ve el sidebar**
- **El espacio que reserva el contenido**

Normalmente coinciden, pero en **modo rail** no: el sidebar se expande *por encima* del contenido al pasar el ratón, y el contenido no debe moverse. Con una sola medida eso sería imposible.

## Contenido a pantalla completa

El `<main>` establece un contexto de apilamiento propio (`isolation: isolate`), para que nada de dentro de la página pueda taparle la barra superior o el sidebar.

El efecto secundario es que un elemento `fixed` con un `z-index` alto **dentro** del contenido (un `speed-dial`, por ejemplo) quedará por debajo del chrome del shell. Si necesitas que flote sobre todo, sácalo del `<main>`.

## Sin sidebar

El shell funciona igual sin ninguno: simplemente no reserva espacio.

```blade
<x-kore::shell>
    <x-slot:navbar><x-kore::navbar :toggle="false" /></x-slot:navbar>
    {{ $slot }}
</x-kore::shell>
```
