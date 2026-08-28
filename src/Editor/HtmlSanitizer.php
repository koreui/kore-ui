<?php

namespace KoreUi\Editor;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * El HTML del editor, saneado en el servidor.
 *
 * **Por qué existe, y por qué en PHP.** El editor limpia lo que escribe y lo que
 * se pega, pero eso pasa en el navegador, y el navegador no es una frontera de
 * seguridad: el valor viaja por `wire:model`, y cualquiera con las herramientas
 * de desarrollo abiertas —o un `curl`— puede mandar por ese hilo lo que le dé la
 * gana. Si lo guardado se pinta luego con `{!! !!}`, que es la única forma de
 * que el texto enriquecido se vea enriquecido, eso es un XSS almacenado.
 *
 * Ninguna librería del stack TALL trae esta pieza: todas sanean en el cliente y
 * ahí lo dejan.
 *
 * ```php
 * // En el componente que recibe el valor
 * public function updatedContenido(string $valor): void
 * {
 *     $this->contenido = HtmlSanitizer::limpiar($valor);
 * }
 * ```
 *
 * **Qué NO es.** No es un purificador de propósito general para HTML de
 * cualquier origen; es la contrapartida exacta de lo que este editor produce.
 * Para HTML arbitrario de terceros, HTMLPurifier sigue siendo la respuesta.
 */
class HtmlSanitizer
{
    /** Las mismas etiquetas que admite el editor en el navegador. */
    public const ETIQUETAS = ['p', 'br', 'strong', 'em', 'u', 's', 'h2', 'h3', 'ul', 'ol', 'li', 'blockquote', 'a', 'code', 'img', 'pre'];

    /**
     * La alineación es el único formato que necesita una clase, así que `class`
     * se admite con UNA de estas cuatro y nada más. No es «se permite class»:
     * es «se permite esta lista cerrada de cuatro valores».
     */
    public const ALINEACIONES = ['kore-editor-izquierda', 'kore-editor-centro', 'kore-editor-derecha', 'kore-editor-justificado'];

    /** Los bloques que pueden llevarla. */
    public const ALINEABLES = ['p', 'h2', 'h3', 'blockquote', 'li'];

    /** Atributos permitidos por etiqueta. Lo que no esté aquí se cae, `style` incluido. */
    public const ATRIBUTOS = [
        'a' => ['href', 'target', 'rel'],
        // `alt` no es decoración: una imagen sin él es invisible para quien no
        // la ve. `loading` se añade abajo, no lo escribe el usuario.
        'img' => ['src', 'alt', 'loading'],
    ];

    /**
     * Esquemas de enlace admitidos.
     *
     * `javascript:` es la vía clásica, pero no la única: `data:text/html` sirve
     * un documento entero, y un `href` con espacios o saltos de línea delante
     * —`java\nscript:`— esquiva una comparación ingenua. Por eso se compara
     * sobre el valor ya decodificado y sin espacios de ningún tipo.
     */
    public const ESQUEMAS = ['http:', 'https:', 'mailto:', 'tel:'];

    /** Equivalencias: lo que un navegador puede haber escrito y aquí se unifica. */
    public const EQUIVALENCIAS = ['b' => 'strong', 'i' => 'em', 'strike' => 's', 'del' => 's', 'div' => 'p', 'h1' => 'h2', 'h4' => 'h3', 'h5' => 'h3', 'h6' => 'h3'];

    public static function limpiar(?string $html, ?array $etiquetas = null): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $etiquetas = $etiquetas ?? self::ETIQUETAS;

        $documento = new DOMDocument;

        // `LIBXML_NOERROR`: el HTML de un editor no tiene por qué ser válido y no
        // interesa el aviso, interesa el resultado. El prefacio con el charset
        // evita que DOMDocument lo lea como ISO-8859-1 y destroce los acentos.
        $anterior = libxml_use_internal_errors(true);

        $documento->loadHTML(
            '<?xml encoding="UTF-8"><div id="kore-raiz">' . $html . '</div>',
            LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();
        libxml_use_internal_errors($anterior);

        $raiz = $documento->getElementById('kore-raiz');

        if (! $raiz) {
            return '';
        }

        self::recorrer($raiz, $etiquetas);

        // Dentro de un bloque de código, un salto de línea es un salto de línea y
        // no un `<br>`: es lo que espera cualquiera que copie ese código, y lo
        // que hace que el texto sobreviva al viaje por markdown.
        foreach (iterator_to_array($raiz->getElementsByTagName('pre')) as $pre) {
            foreach (iterator_to_array($pre->getElementsByTagName('br')) as $br) {
                $br->parentNode->replaceChild($documento->createTextNode("\n"), $br);
            }
        }

        // Un bloque de código es `<pre><code>`, no un `<pre>` a secas: misma
        // forma venga de donde venga.
        foreach (iterator_to_array($raiz->getElementsByTagName('pre')) as $pre) {
            if ($pre->getElementsByTagName('code')->length > 0) {
                continue;
            }

            $codigo = $documento->createElement('code');

            while ($pre->firstChild) {
                $codigo->appendChild($pre->firstChild);
            }

            $pre->appendChild($codigo);
        }

        $salida = '';

        foreach ($raiz->childNodes as $hijo) {
            $salida .= $documento->saveHTML($hijo);
        }

        return trim($salida);
    }

    /** ¿Este HTML pasaría el filtro sin perder nada? */
    public static function esSeguro(?string $html): bool
    {
        return self::limpiar($html) === trim((string) $html);
    }

    private static function recorrer(DOMNode $nodo, array $etiquetas): void
    {
        // Se itera sobre una copia: quitar o reemplazar nodos mientras se recorre
        // `childNodes` —que es una lista viva— se salta la mitad de los hermanos.
        foreach (iterator_to_array($nodo->childNodes) as $hijo) {
            if ($hijo->nodeType === XML_COMMENT_NODE) {
                $hijo->parentNode->removeChild($hijo);

                continue;
            }

            if (! $hijo instanceof DOMElement) {
                continue;   // texto: se queda tal cual, ya escapado por el DOM
            }

            self::recorrer($hijo, $etiquetas);

            $etiqueta = strtolower($hijo->nodeName);
            $equivalente = self::EQUIVALENCIAS[$etiqueta] ?? null;

            if ($equivalente && in_array($equivalente, $etiquetas, true)) {
                $hijo = self::renombrar($hijo, $equivalente);
                $etiqueta = $equivalente;
            }

            if (! in_array($etiqueta, $etiquetas, true)) {
                self::desenvolver($hijo);

                continue;
            }

            self::limpiarAtributos($hijo, $etiqueta);
        }
    }

    private static function renombrar(DOMElement $elemento, string $nombre): DOMElement
    {
        $nuevo = $elemento->ownerDocument->createElement($nombre);

        while ($elemento->firstChild) {
            $nuevo->appendChild($elemento->firstChild);
        }

        $elemento->parentNode->replaceChild($nuevo, $elemento);

        return $nuevo;
    }

    /**
     * Quita la etiqueta y deja dentro lo que tenía.
     *
     * Se desenvuelve en vez de borrarse porque dentro puede haber texto que el
     * usuario escribió: un `<span style="...">hola</span>` pierde el span, no el
     * «hola». Un `<script>` es la excepción —su contenido es código, no texto—,
     * así que ahí sí se tira todo.
     */
    private static function desenvolver(DOMElement $elemento): void
    {
        $etiqueta = strtolower($elemento->nodeName);

        if (in_array($etiqueta, ['script', 'style', 'iframe', 'object', 'embed', 'template'], true)) {
            $elemento->parentNode->removeChild($elemento);

            return;
        }

        while ($elemento->firstChild) {
            $elemento->parentNode->insertBefore($elemento->firstChild, $elemento);
        }

        $elemento->parentNode->removeChild($elemento);
    }

    private static function limpiarAtributos(DOMElement $elemento, string $etiqueta): void
    {
        $permitidos = self::ATRIBUTOS[$etiqueta] ?? [];

        // La alineación se rescata antes de barrer, y solo si es una de las
        // nuestras: cualquier otra clase se cae con el resto.
        $alineacion = null;

        if (in_array($etiqueta, self::ALINEABLES, true)) {
            foreach (preg_split('/\s+/', trim($elemento->getAttribute('class'))) as $clase) {
                if (in_array($clase, self::ALINEACIONES, true)) {
                    $alineacion = $clase;

                    break;
                }
            }
        }

        foreach (iterator_to_array($elemento->attributes) as $atributo) {
            if (! in_array(strtolower($atributo->nodeName), $permitidos, true)) {
                $elemento->removeAttribute($atributo->nodeName);
            }
        }

        if ($alineacion !== null) {
            $elemento->setAttribute('class', $alineacion);
        }

        if ($etiqueta === 'img') {
            self::limpiarImagen($elemento);

            return;
        }

        if ($etiqueta !== 'a') {
            return;
        }

        $destino = $elemento->getAttribute('href');

        if (! self::destinoAceptable($destino)) {
            self::desenvolver($elemento);

            return;
        }

        // Una pestaña nueva sin `noopener` le da a la página de destino acceso a
        // la de origen por `window.opener`.
        if ($elemento->getAttribute('target') === '_blank') {
            $elemento->setAttribute('rel', 'noopener noreferrer');
        }
    }

    /**
     * Una imagen, con el origen acotado.
     *
     * **`data:` no entra**, aunque para un `src` parezca lo natural. Un
     * `data:image/svg+xml` es un documento SVG completo, y un SVG puede llevar
     * `<script>` dentro: abierto en su propia pestaña, se ejecuta. Además, una
     * imagen en base64 dentro del cuerpo del texto multiplica por cuatro lo que
     * ocupa y viaja entera en cada guardado.
     *
     * Sin `src` utilizable la etiqueta se cae del todo —no se desenvuelve, no
     * hay texto dentro que salvar—, pero si traía `alt` se conserva como texto:
     * es lo que el autor quiso decir.
     */
    private static function limpiarImagen(DOMElement $elemento): void
    {
        $origen = $elemento->getAttribute('src');

        if (! self::origenDeImagenAceptable($origen)) {
            $alt = trim($elemento->getAttribute('alt'));

            if ($alt !== '') {
                $elemento->parentNode->replaceChild(
                    $elemento->ownerDocument->createTextNode($alt),
                    $elemento
                );

                return;
            }

            $elemento->parentNode->removeChild($elemento);

            return;
        }

        // Una imagen dentro de un texto largo casi nunca está en pantalla al
        // cargar: que el navegador la pida cuando toque.
        $elemento->setAttribute('loading', 'lazy');
    }

    private static function origenDeImagenAceptable(string $origen): bool
    {
        $limpio = strtolower(preg_replace('/[\s\x00-\x20]+/', '', html_entity_decode($origen, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        if ($limpio === '') {
            return false;
        }

        if (str_starts_with($limpio, '/') || str_starts_with($limpio, './') || str_starts_with($limpio, '../')) {
            return true;
        }

        if (str_starts_with($limpio, 'http:') || str_starts_with($limpio, 'https:')) {
            return true;
        }

        return ! str_contains($limpio, ':');
    }

    private static function destinoAceptable(string $destino): bool
    {
        // Los espacios de cualquier tipo —incluidos los de control, que un
        // navegador ignora al resolver la URL— se quitan antes de mirar nada:
        // «java\tscript:alert(1)» se ejecuta igual que sin la tabulación.
        $limpio = strtolower(preg_replace('/[\s\x00-\x20]+/', '', html_entity_decode($destino, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        if ($limpio === '') {
            return false;
        }

        // Relativos y anclas: no llevan esquema, así que no hay nada que colar.
        if (str_starts_with($limpio, '#') || str_starts_with($limpio, '/') || str_starts_with($limpio, './') || str_starts_with($limpio, '../')) {
            return true;
        }

        foreach (self::ESQUEMAS as $esquema) {
            if (str_starts_with($limpio, $esquema)) {
                return true;
            }
        }

        // Sin esquema reconocible y sin dos puntos: es una ruta relativa suelta
        // («pagina.html»), que tampoco puede ejecutar nada.
        return ! str_contains($limpio, ':');
    }
}
