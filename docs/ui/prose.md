# Prose

El texto enriquecido, ya publicado.

```blade
<x-kore::prose :markdown="$articulo->cuerpo" />
<x-kore::prose :html="$articulo->cuerpo" />
```

## Por qué existe

Publicar lo que escribe [`<x-kore::editor>`](../form/editor.md) tenía dos trampas,
y las dos había que saberlas de memoria:

1. **`{!! $cuerpo !!}` sin sanear es un XSS almacenado.** El texto enriquecido
   solo se ve enriquecido si se pinta sin escapar, así que la única defensa era
   acordarse de llamar al sanitizador en el sitio correcto.
2. **Los estilos no estaban.** Tailwind arrasa con los del navegador, así que un
   `<h2>` pesaba lo mismo que un párrafo y una lista perdía sus viñetas. Las
   reglas existían, pero bajo una clase interna del propio editor.

`<x-kore::prose>` cierra las dos: **sanea antes de pintar** —es la última línea,
y después ya no hay ninguna— y aplica la misma clase que el interior del editor,
así que lo publicado se ve exactamente como se veía al escribirlo.

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `html` | `string\|null` | `null` | HTML guardado. Se sanea con `HtmlSanitizer` antes de pintarlo |
| `markdown` | `string\|null` | `null` | Markdown guardado. Lo convierte `Markdown::aHtml()` |
| `size` | `string` | config | `sm`, `md`, `lg` |

Por slot, el contenido se pinta **tal cual**: ahí puede haber componentes y no
solo el texto del editor, y sanear se llevaría por delante lo que no es suyo.
Quien lo use por ahí, sanea él.

```blade
<x-kore::prose>
    {!! KoreUi\Editor\HtmlSanitizer::limpiar($cuerpo) !!}
    <x-kore::alert>Y algo más, que no viene del editor</x-kore::alert>
</x-kore::prose>
```

## Una sola clase

El CSS del texto enriquecido responde a `.kore-prose`, y es la misma clase que
usa el área de escritura del editor. Son el mismo contenido en dos momentos:
duplicar dieciséis reglas sería pedir que se separen sin que nadie se entere. Hay
un test de navegador que compara los estilos calculados de los dos.
