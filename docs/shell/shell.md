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

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `skipLink` | `bool` | `true` | Pinta el enlace de «saltar al contenido» como primer elemento del shell |
| `skipLabel` | `string\|null` | `Saltar al contenido` | Su texto. Sale de `kore-ui.ui.translations.skip_to_content` |
| `mainId` | `string` | `kore-contenido` | `id` del `<main>`, y destino del enlace de salto |

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

## Saltar al contenido

El shell pinta como **primer elemento del documento** un enlace que salta al
`<main>`. Solo se ve al enfocarlo con el tabulador; el resto del tiempo es un
`sr-only`.

Está porque sin él, quien navega con teclado tenía que recorrer todo el menú
—seis pulsaciones con un sidebar de tres niveles— antes de llegar al contenido,
y en **cada** página.

El `<main>` recibe `id="kore-contenido"` y `tabindex="-1"`. El `tabindex` no es
decorativo: sin él, el salto mueve el foco del navegador pero algunos lectores
de pantalla siguen leyendo desde donde estaban.

```blade
{{-- Sin enlace de salto: la página ya lo pone por su cuenta --}}
<x-kore::shell :skip-link="false">…</x-kore::shell>

{{-- Con otro texto y otro id de destino --}}
<x-kore::shell skip-label="Ir al contenido" main-id="contenido">…</x-kore::shell>
```

`mainId` cambia las dos puntas a la vez —el `href` del enlace y el `id` del
`<main>`—, así que siguen apuntándose. Tócalo solo si ya tienes un `id`
comprometido en tu CSS o en tus enlaces; si además pones `:skip-link="false"`,
el `<main>` conserva `id` y `tabindex` para que tu propio enlace funcione.

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
