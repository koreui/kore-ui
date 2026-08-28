# Editor

Texto enriquecido sin dependencias: negrita, títulos, listas, citas y enlaces,
sobre `contenteditable` y con la salida pasada por una lista blanca.

```blade
<x-kore::editor label="Descripción" wire:model="descripcion" />
```

## Cómo se publica

```blade
<x-kore::prose :markdown="$articulo->cuerpo" />
```

Sanea antes de pintar y aplica los estilos del texto enriquecido, que si no hay
que conocer de memoria. Ver [prose](../ui/prose.md).

## Lo primero: lo que se guarda hay que sanearlo en el servidor

El editor limpia lo que se escribe y lo que se pega, pero **eso pasa en el
navegador, y el navegador no es una frontera de seguridad**. El valor viaja por
`wire:model`, y cualquiera con las herramientas de desarrollo abiertas puede
mandar por ese hilo lo que quiera. Como el texto enriquecido solo se ve
enriquecido si se pinta con `{!! !!}`, guardar sin filtrar es un XSS almacenado.

Por eso el paquete trae la contrapartida en PHP:

```php
use KoreUi\Editor\HtmlSanitizer;

class Articulo extends Component
{
    public string $cuerpo = '';

    public function updatedCuerpo(string $valor): void
    {
        $this->cuerpo = HtmlSanitizer::limpiar($valor);
    }
}
```

O al guardar:

```php
$articulo->cuerpo = HtmlSanitizer::limpiar($this->cuerpo);
```

`HtmlSanitizer` admite las mismas etiquetas que el editor y se le puede dar una
lista más corta: `HtmlSanitizer::limpiar($html, ['p', 'strong', 'em'])`. Para
HTML de cualquier otro origen —no el que produce este editor— la respuesta
sigue siendo HTMLPurifier.

**O quítate el problema de encima**: con [`markdown`](#markdown-la-forma-de-no-tener-que-acordarse)
lo que se guarda es texto plano y no hay marcado del usuario que sanear.

### Sin acordarte de nada

Llamar al sanitizador a mano funciona hasta el día en que alguien guarda desde
otro sitio —un comando, un import, una API— y se le olvida. En el modelo, el
agujero deja de depender de la memoria de nadie:

```php
use KoreUi\Editor\Casts\SanitizedHtml;

protected function casts(): array
{
    return ['cuerpo' => SanitizedHtml::class];
}
```

Limpia al **escribir**, no al leer: sanear en cada lectura sería pagarlo en cada
fila de cada consulta. Para arreglar lo que ya está guardado, una migración.

### Validar lo que llega

```php
use KoreUi\Editor\Rules\{SafeHtml, MaxTextLength};

$request->validate([
    'cuerpo' => ['required', new SafeHtml, new MaxTextLength(500)],
]);
```

`SafeHtml` **rechaza** el marcado que no pasaría la lista blanca. Sirve para una
API, donde un HTML raro es una señal; en un formulario normal suele ser mejor
limpiar y seguir con el cast, porque el usuario vería un error que él no ha
provocado —el editor de verdad nunca produce eso—.

`MaxTextLength` existe porque **`max:500` no vale**: mide la cadena entera, y en
un campo de texto enriquecido esa cadena lleva etiquetas. `<p><strong>Hola</strong></p>`
son 4 caracteres para quien escribe y 30 para `max`, así que el contador del
editor decía «4/10» y el formulario contestaba que se había pasado. Y hace falta
en el servidor porque el `maxlength` del editor vive en el navegador: es una
comodidad para quien escribe, no una frontera.

## Imágenes

El botón de imagen aparece solo si se le dice **dónde dejar el archivo**. Esa
decisión —qué disco, qué carpeta, con qué URL se sirve, si la ruta va firmada—
es de la aplicación, así que el editor sube y pregunta:

```blade
<x-kore::editor
    wire:model="cuerpo"
    upload-property="imagenEditor"
    upload-method="guardarImagen"
    :upload-max-size="1024"
/>
```

```php
use Livewire\WithFileUploads;

class Articulo extends Component
{
    use WithFileUploads;

    public $imagenEditor = null;

    /** Guarda lo subido y devuelve la URL con la que el editor lo pintará. */
    public function guardarImagen(): ?string
    {
        if (! $this->imagenEditor) {
            return null;
        }

        $ruta = $this->imagenEditor->store('editor', 'public');
        $this->imagenEditor = null;

        return Storage::disk('public')->url($ruta);
    }
}
```

El viaje son dos pasos: `$wire.upload()` deja el archivo en la propiedad como
temporal, y el método decide qué hacer con él y devuelve la URL. Devolver `null`
se trata como error y el editor lo dice.

Se puede subir de tres maneras, y las tres acaban en el mismo sitio:

- el botón de la barra,
- **pegando** — una captura de pantalla llega al portapapeles como archivo, no
  como HTML, y sin tratarla se perdía sin decir nada,
- **arrastrando y soltando** sobre el texto.

| Prop | Default | Qué hace |
|---|---|---|
| `uploadProperty` | — | La propiedad Livewire que recibe el archivo. Sin ella no hay botón |
| `uploadMethod` | — | El método que guarda y devuelve la URL |
| `uploadMimes` | `image/png, jpeg, gif, webp` | Tipos admitidos |
| `uploadMaxSize` | `2048` | KB. Se comprueba en el navegador antes de gastar el viaje |

El **texto alternativo** sale del nombre del archivo, sin extensión y con los
guiones convertidos en espacios. No es una descripción, pero es infinitamente
mejor que la cadena vacía, y el autor puede cambiarlo.

**`data:` no se admite como origen**, aunque para un `src` parezca lo natural: un
`data:image/svg+xml` es un documento SVG completo y un SVG puede llevar
`<script>` dentro, que se ejecuta si alguien abre la imagen en su propia pestaña.
Además, una imagen en base64 dentro del texto multiplica por cuatro lo que ocupa
y viaja entera en cada guardado. Toda imagen sale con `loading="lazy"`: dentro de
un texto largo casi ninguna está en pantalla al cargar.

## Markdown: la forma de no tener que acordarse

Guardando HTML, lo que hay en la base de datos son etiquetas que alguien pintará
con `{!! !!}`, y **todo depende de que el saneado se haya ejecutado por el
camino**. Basta con que un día se guarde desde otro sitio para que el agujero
quede abierto.

```blade
<x-kore::editor markdown wire:model="cuerpo" />
```

Con `markdown`, lo que viaja y se guarda es **texto plano**. El HTML no existe
hasta que el servidor lo fabrica:

```blade
{!! KoreUi\Editor\Markdown::aHtml($articulo->cuerpo) !!}
```

Y lo fabrica con las etiquetas que decide él, no con las que venían: un
`<script>` escrito por el usuario sale como `&lt;script&gt;`, texto. El XSS deja
de ser algo que haya que acordarse de prevenir. Lo que produce `Markdown::aHtml()`
pasa entero por la lista blanca de `HtmlSanitizer` —hay un test que lo comprueba—,
así que no hace falta encadenar los dos.

El editor sigue siendo el mismo por dentro: se escribe viendo el formato, no la
sintaxis. Lo que cambia es la puerta.

### Qué sintaxis

| Botón | Markdown |
|---|---|
| Título / Subtítulo | `## `, `### ` |
| Negrita | `**texto**` |
| Cursiva | `*texto*` |
| Tachado | `~~texto~~` |
| Código | `` `texto` `` |
| Lista | `- item` / `1. item` |
| Cita | `> texto` |
| Enlace | `[texto](url)` |
| Imagen | `![alt](url)` |
| Bloque de código | ``` ``` ``` |

**El subrayado y la alineación no están**: markdown no tiene sintaxis para
ellos, así que sus botones se caen solos de la barra. Dejarlo sería prometer un formato que se pierde en cuanto
el texto va y vuelve del servidor.

### Los dos parsers

La gramática está escrita dos veces: en `resources/js/form/editor-markdown.js`,
que pinta lo que se ve mientras se edita, y en `KoreUi\Editor\Markdown`, que
fabrica lo que se publica. Si divergen, lo escrito se ve de una manera y se
publica de otra, y eso **no lo detecta ningún test de los dos lados por
separado**: cada uno pasa con su propia idea de la verdad.

Por eso hay un tercer test que los enfrenta —`npm run markdown:check`, en CI—:
pasa los mismos casos por ambos y compara el HTML carácter a carácter. Al tocar
uno hay que tocar el otro.

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string\|null` | `null` | Etiqueta del campo |
| `hint` | `string\|null` | `null` | Texto de ayuda |
| `name` | `string\|null` | `null` | Nombre; si falta se deduce del `wire:model` |
| `error` | `string\|null` | `null` | Error manual |
| `placeholder` | `string\|null` | config | Texto mientras está vacío |
| `toolbar` | `array\|null` | config | Qué botones y en qué orden; `\|` es un separador |
| `minHeight` | `string` | `12rem` | Alto mínimo del área de escritura |
| `maxHeight` | `string\|null` | `null` | Alto máximo; a partir de ahí, scroll |
| `maxlength` | `int\|null` | `null` | Límite de caracteres (cuenta texto, no marcado) |
| `counter` | `bool` | `false` | Enseña el contador. Con `maxlength` se activa solo |
| `markdown` | `bool` | `false` | Guarda markdown en vez de HTML. Ver [arriba](#markdown-la-forma-de-no-tener-que-acordarse) |
| `uploadProperty` | `string\|null` | `null` | Propiedad Livewire que recibe la imagen. Ver [imágenes](#imagenes) |
| `uploadMethod` | `string\|null` | `null` | Método que guarda y devuelve la URL |
| `uploadMimes` | `array\|null` | config | Tipos de imagen admitidos |
| `uploadMaxSize` | `int\|null` | `2048` | Tamaño máximo en KB |
| `debounce` | `int` | `400` | Milisegundos desde la última tecla hasta mandar |
| `disabled` | `bool` | `false` | Atenuado y sin barra |
| `readonly` | `bool` | `false` | Se lee y se envía, pero no se edita |
| `required` | `bool` | `false` | Marca el campo y añade `aria-required` |
| `showError` | `bool` | `true` | Pinta los errores de validación. A `false` calla **todos**: ni el bag `$errors` ni un `error` escrito a mano |

## La barra

```blade
<x-kore::editor :toolbar="['bold', 'italic', '|', 'ul', 'ol']" />
```

Disponibles: `bold`, `italic`, `underline`, `strike`, `h2`, `h3`, `ul`, `ol`,
`quote`, `pre`, `left`, `center`, `right`, `link`, `unlink`, `image`, `clear`,
`undo`, `redo`, `fullscreen`, y `|` como separador.

Dos se caen solos: `image` si no hay subida configurada, y `underline` junto a
los tres de alineación en modo markdown, que no tiene sintaxis para ellos.

**Quitar un botón no impide el formato**: los atajos de teclado siguen ahí y el
HTML pegado también. Para acotar de verdad lo que se admite, la lista blanca es
la del sanitizador.

Atajos: `Ctrl/Cmd+B`, `Ctrl/Cmd+I`, `Ctrl/Cmd+U` y `Ctrl/Cmd+K` para enlazar.

**Tab anida la lista** en la que esté el cursor, y `Shift+Tab` la saca un nivel.
Fuera de una lista, Tab se deja pasar a propósito: capturarlo siempre encerraría
a quien navega con teclado dentro del editor, sin forma de llegar al resto del
formulario.

**Dos Enter seguidos salen de un bloque de código.** Dentro, Enter escribe un
salto de línea, así que sin esto la única puerta era el botón.

## Escribir sin ir a la barra

Los prefijos convierten el bloque en cuanto se termina de escribirlos:

| Se escribe | Sale |
|---|---|
| `# ` | Título |
| `## ` | Subtítulo |
| `- `, `* `, `+ ` | Lista con viñetas |
| `1. `, `1) ` | Lista numerada |
| `> ` | Cita |

Se dispara con el **espacio**, no con cada tecla: al vuelo, un `-` recién
escrito se convertiría en lista antes de saber si lo que viene es «- pero» o
«-5 grados». Y solo cuenta si el cursor está justo detrás del prefijo, así que
en mitad de un párrafo un guion es un guion.

Atajos: `Ctrl/Cmd+B`, `Ctrl/Cmd+I`, `Ctrl/Cmd+U` y `Ctrl/Cmd+K` para enlazar.

## Alineación y bloques de código

La alineación es el único formato que necesita una clase, así que la lista blanca
admite `class` **con uno de estos cuatro valores y nada más**:
`kore-editor-izquierda`, `kore-editor-centro`, `kore-editor-derecha`,
`kore-editor-justificado`. No es «se permite class»: es una lista cerrada de
cuatro, y solo sobre `p`, `h2`, `h3`, `blockquote` y `li`.

Ojo con lo que `execCommand` haría por su cuenta: `justifyCenter` escribe el
atributo `align` —obsoleto desde HTML4— o un `style` que la lista blanca tira.
Por eso la clase se pone directamente sobre el bloque.

Un bloque de código es siempre `<pre><code>`, venga del botón o del markdown, y
dentro **no se interpreta nada**: una almohadilla ahí es una almohadilla. El
salto de línea es un salto de verdad y no un `<br>`, que es lo que espera quien
copie ese código —y lo que hace que sobreviva al viaje por markdown—.

Todo esto entra en el **historial de deshacer**, la alineación incluida: se pone
reemplazando el bloque con el motor de edición, no cambiándole la clase por
detrás, que ocurriría fuera del historial.

## Pantalla completa

El botón `fullscreen` lleva el editor a ocupar la ventana. Bloquea el
desplazamiento del fondo con el mismo contador que usan los modales y el cajón
lateral —si cada cosa lo tomara por su cuenta, la primera en cerrarse devolvería
el scroll con las demás abiertas— y se sale con `Escape`.

## Qué HTML produce

Solo estas etiquetas: `p`, `br`, `strong`, `em`, `u`, `s`, `h2`, `h3`, `ul`,
`ol`, `li`, `blockquote`, `a`, `code`, `img`, `pre`. Los únicos atributos que sobreviven
son `href`, `target` y `rel` en un enlace, y `src`, `alt` y `loading` en una
imagen.

Todo lo demás se desenvuelve conservando su texto —un `<span style="color:red">`
pierde el span, no la palabra—, salvo `script`, `style`, `iframe`, `object`,
`embed`, `template` y `noscript`, que se tiran enteros con su contenido.

En los enlaces, solo `http:`, `https:`, `mailto:`, `tel:`, rutas relativas y
anclas. `javascript:` y `data:` se rechazan, incluso escritos con mayúsculas
raras, con entidades HTML o con caracteres de control por medio —trucos que un
navegador ignora al resolver la URL y una comparación ingenua no ve—. Un
`target="_blank"` recibe siempre `rel="noopener noreferrer"`.

## En un móvil

La barra va en **una sola fila que se arrastra a lo ancho** en pantallas
estrechas, y vuelve a envolverse a partir de `sm`. Con veinte botones a tamaño
táctil, envolver en un teléfono ocupaba tres filas: un tercio del componente era
barra.

Si eso sigue siendo mucho, la barra se recorta por etiqueta:

```blade
<x-kore::editor :toolbar="['bold', 'italic', '|', 'ul', 'link']" />
```

El editor se prueba en un iPhone real (WebKit) además de en escritorio, y no por
gusto: se apoya en `execCommand`, que es justo donde cada motor escribe un HTML
distinto. Si WebKit produjera algo que la lista blanca no reconoce, el formato se
perdería al guardar y en Chrome no se vería jamás.

## Accesibilidad

- El área de escritura es `role="textbox"` con `aria-multiline`, y la etiqueta
  del campo apunta a ella.
- La barra es `role="toolbar"` y **una sola parada del tabulador**: se entra con
  Tab y se recorre con las flechas. Con catorce botones, lo contrario son
  catorce pulsaciones para llegar al texto.
- Cada botón lleva `aria-pressed` con el estado real del cursor.
- Con `maxlength`, el contador es `aria-live`.

## Su JavaScript se carga solo

El editor no viaja en el bundle principal: son 6,5 kB gzip que solo paga quien lo
usa. El componente lo pide cuando aparece en la página, una vez aunque haya
varios editores, y no hay que configurar nada.

Va declarado con `@assets` de Livewire y no con un `@once` corriente por un
motivo concreto: un `<script>` que llega dentro de una respuesta de Livewire —un
editor dentro de un modal que se abre— **no lo ejecuta el navegador**, porque el
morphing lo inserta como marcado. `@assets` existe justamente para eso. Y si aun
así llegara tarde, el propio bundle vuelve a inicializar los editores que Alpine
ya hubiera recorrido y dejado sin componente.

## Detalles que conviene conocer

**`wire:ignore`.** El área de escritura lo lleva puesto: sin él, cualquier
repintado del servidor reemplaza el árbol que el usuario está editando y se
lleva por delante el cursor, la selección y el historial de deshacer. La
contrapartida es que un cambio del valor desde el servidor solo entra cuando el
campo no está en uso.

**`execCommand`.** Sí, está obsoleto. La alternativa real es mantener un modelo
de documento propio —lo que hacen ProseMirror o Trix—, y eso pesa más que todo
este paquete junto. Sigue implementado en todos los navegadores; lo que cambia
entre motores es el HTML que produce, y de eso se encarga la lista blanca.

**El valor va en un input oculto.** Un `contenteditable` no es un control de
formulario: no tiene `value` que Livewire pueda leer.
