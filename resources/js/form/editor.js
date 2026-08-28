/**
 * El editor de texto enriquecido.
 *
 * **Por qué `document.execCommand` si está obsoleto.** La alternativa real es
 * mantener un modelo de documento propio —lo que hacen ProseMirror o Trix— y eso
 * son decenas de miles de líneas y un peso que esta librería no quiere arrastrar
 * (hoy el bundle entero pesa menos que Trix solo). `execCommand` sigue
 * implementado en todos los navegadores y no hay señales de retirada; lo que sí
 * es cierto es que **cada motor escupe un HTML distinto** —`<b>` aquí, `<font>`
 * allá, `style="font-weight:bold"` más allá—, y por eso nada de lo que produce
 * sale de aquí sin pasar por `limpiar()`.
 *
 * **La salida se sanea SIEMPRE**, tanto lo que escribe el usuario como lo que se
 * pega. Aun así, esto es el navegador: quien quiera puede mandar por el hilo de
 * Livewire lo que le dé la gana. La frontera de seguridad de verdad está en PHP,
 * en `KoreUi\Editor\HtmlSanitizer`.
 */

import { aMarkdown, aHtml as markdownAHtml } from './editor-markdown.js';
import { lockScroll as bloquearScroll, unlockScroll as soltarScroll } from '../utils/scroll-lock.js';

/** Un identificador por editor, para el contador del bloqueo de scroll. */
let contadorDeInstancias = 0;

/** Lo que puede sobrevivir. Todo lo demás se desenvuelve o se tira. */
const ETIQUETAS = ['P', 'BR', 'STRONG', 'EM', 'U', 'S', 'H2', 'H3', 'UL', 'OL', 'LI', 'BLOCKQUOTE', 'A', 'CODE', 'IMG', 'PRE'];

/** Atributos permitidos por etiqueta. El resto se cae, `style` incluido. */
const ATRIBUTOS = { A: ['href', 'target', 'rel'], IMG: ['src', 'alt', 'loading'] };

/**
 * La alineación, que es el único formato que necesita una clase.
 *
 * No se admite `class` a secas ni `style`: se admite EXACTAMENTE una de estas
 * cuatro clases sobre un bloque. Cualquier otra cosa se cae, así que la lista
 * blanca sigue siendo una lista blanca y no una puerta abierta.
 */
const ALINEACIONES = {
    left: 'kore-editor-izquierda',
    center: 'kore-editor-centro',
    right: 'kore-editor-derecha',
    justify: 'kore-editor-justificado',
};

const CLASES_ALINEACION = Object.values(ALINEACIONES);

/** Los bloques que pueden llevar alineación. */
const ALINEABLES = ['P', 'H2', 'H3', 'BLOCKQUOTE', 'LI'];

/** Esquemas de enlace admitidos: `javascript:` y `data:` no están. */
const ESQUEMAS = /^(https?:|mailto:|tel:|#|\/|\.\/|\.\.\/)/i;

/**
 * Y los de una imagen, que son menos: `data:` fuera también aquí, aunque para un
 * `src` parezca lo natural. Un `data:image/svg+xml` es un documento SVG entero y
 * un SVG puede llevar `<script>` dentro.
 */
const ORIGENES = /^(https?:|\/|\.\/|\.\.\/)/i;

/** Lo que se tira entero, con contenido incluido: dentro hay código, no texto. */
const PROHIBIDAS = ['SCRIPT', 'STYLE', 'IFRAME', 'OBJECT', 'EMBED', 'TEMPLATE', 'NOSCRIPT'];

/** Lo que un contenteditable deja cuando el usuario lo vacía. */
const VACIOS = ['', '<br>', '<p></p>', '<p><br></p>', '<div><br></div>'];

const equivalencias = { B: 'STRONG', I: 'EM', STRIKE: 'S', DEL: 'S', DIV: 'P', H1: 'H2', H4: 'H3', H5: 'H3', H6: 'H3' };

export default (config = {}) => ({
    /** Lo que se manda al servidor. */
    contenido: '',
    /** Qué formatos tiene el cursor encima, para encender los botones. */
    formatos: { bold: false, italic: false, underline: false, strike: false, h2: false, h3: false, ul: false, ol: false, quote: false, link: false, pre: false, left: false, center: false, right: false },
    /** La clase de alineación del bloque actual, o null. */
    alineacion: null,
    caracteres: 0,
    vacio: true,
    /** Lo que había marcado antes de abrir el diálogo del enlace, en caracteres. */
    rango: null,
    /** Lo último que salió de aquí, para reconocer nuestro propio eco. */
    _ultimoEnviado: null,
    /** Hay una edición en curso: el servidor no manda mientras dure. */
    _editando: false,
    dialogoEnlace: false,
    urlEnlace: '',
    /** El editor ocupando la pantalla entera. */
    pantallaCompleta: false,
    /** Subida de imágenes en curso: 0–100, o null si no hay ninguna. */
    progresoImagen: null,
    errorImagen: null,
    _sincronizar: null,
    _formatos: null,
    _claveScroll: null,

    init() {
        this._claveScroll = `kore-editor-${++contadorDeInstancias}`;

        // Sin esto, Chrome envuelve cada línea en un <div> y Firefox no envuelve
        // nada: el mismo texto daba dos árboles distintos según el navegador.
        try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch { /* da igual: se normaliza igual */ }

        const oculto = this.$refs.hiddenInput;

        if (oculto?.value) {
            this.$refs.editable.innerHTML = this.desdeElServidor(oculto.value);
        }

        this.recontar();

        // El servidor puede cambiar el valor por su cuenta —un reset del
        // formulario, otra pestaña—. El editable vive dentro de `wire:ignore`,
        // así que el morph no lo toca y hay que traerlo a mano.
        if (oculto && this.$wire) {
            const modelo = oculto.getAttribute('wire:model.live')
                || oculto.getAttribute('wire:model.blur')
                || oculto.getAttribute('wire:model.defer')
                || oculto.getAttribute('wire:model');

            if (modelo) {
                this.$wire.$watch(modelo, (valor) => {
                    const nuevo = valor ?? '';

                    // El eco de lo que acabamos de mandar. Llega SIEMPRE con
                    // `wire:model.live`, y con retraso: para cuando vuelve, el
                    // usuario ya ha escrito otras cuatro palabras. Reescribir con
                    // él dejaba el texto de hace medio segundo y el cursor al
                    // principio; era lo que barajaba las frases.
                    if (nuevo === this._ultimoEnviado || nuevo === this.contenido) return;

                    // Mientras se edita manda el navegador. Comprobar solo el
                    // foco no basta: pulsar un botón de la barra lo saca del
                    // editable durante un instante, y justo ahí caía el eco.
                    if (this._editando || this.tieneFoco()) return;

                    this.$refs.editable.innerHTML = this.desdeElServidor(nuevo);
                    this.contenido = this.salida();
                    this.recontar();
                });
            }
        }

        this.rove();
    },

    destroy() {
        clearTimeout(this._sincronizar);
        clearTimeout(this._formatos);

        // Si el componente se va mientras está a pantalla completa —una
        // navegación con `wire:navigate`, por ejemplo—, el bloqueo se quedaría
        // puesto y la página siguiente no se podría desplazar.
        if (this.pantallaCompleta) soltarScroll(this._claveScroll);
    },

    // ---------------------------------------------------------------- entrada

    /**
     * Lo que llega del servidor tampoco se cree a ciegas.
     *
     * En modo markdown lo que llega es TEXTO: hay que convertirlo a HTML para
     * poder pintarlo, y ese HTML lo fabrica el parser de aquí, no el remitente.
     * Pasa igualmente por la limpieza: la conversión es la misma que la del
     * servidor, pero el valor pudo escribirlo cualquiera.
     */
    desdeElServidor(valor) {
        const html = config.markdown ? markdownAHtml(valor ?? '') : (valor ?? '');

        return this.limpiar(html);
    },

    tieneFoco() {
        return this.$refs.editable?.contains(document.activeElement)
            || this.$refs.editable === document.activeElement;
    },

    // ----------------------------------------------------------------- salida

    /**
     * Deja el HTML en la lista blanca: renombra lo equivalente, desenvuelve lo
     * que no conoce —quedándose con su texto, que es lo que el usuario ve— y
     * borra todo atributo que no esté permitido.
     */
    limpiar(html) {
        const molde = document.createElement('template');
        molde.innerHTML = html;

        const recorrer = (nodo) => {
            for (const hijo of [...nodo.childNodes]) {
                if (hijo.nodeType === 8) {           // comentarios
                    hijo.remove();
                    continue;
                }

                if (hijo.nodeType !== 1) continue;    // texto: se queda

                recorrer(hijo);

                const equivalente = equivalencias[hijo.tagName];

                if (equivalente) {
                    const nuevo = document.createElement(equivalente);
                    while (hijo.firstChild) nuevo.appendChild(hijo.firstChild);
                    hijo.replaceWith(nuevo);
                    this.limpiarAtributos(nuevo);
                    continue;
                }

                if (!ETIQUETAS.includes(hijo.tagName)) {
                    // Lo que lleva dentro CÓDIGO y no texto se tira entero.
                    // Desenvolverlo dejaba el cuerpo del script como párrafo:
                    // medido al pegar desde un procesador de textos, se guardaba
                    // «alert(1)» tan tranquilo. Parece inofensivo hasta que
                    // alguien vuelve a meter eso en un innerHTML.
                    if (PROHIBIDAS.includes(hijo.tagName)) {
                        hijo.remove();
                        continue;
                    }

                    // El resto se desenvuelve en vez de borrarse: dentro puede
                    // haber texto que el usuario escribió y no tiene por qué
                    // perder.
                    hijo.replaceWith(...hijo.childNodes);
                    continue;
                }

                this.limpiarAtributos(hijo);
            }
        };

        recorrer(molde.content);

        // Chrome mete la lista DENTRO del párrafo que había —`<p><ul>…</ul></p>`—,
        // que además de feo no es HTML válido: el navegador que lo lea después
        // cerrará el párrafo por su cuenta y el árbol dejará de parecerse al que
        // se guardó.
        for (const parrafo of [...molde.content.querySelectorAll('p')]) {
            if (parrafo.querySelector('ul, ol, blockquote, h2, h3, p')) {
                parrafo.replaceWith(...parrafo.childNodes);
            }
        }

        // Dentro de un bloque de código, un salto de línea es un salto de línea:
        // el `<br>` que escribe el navegador no lo ve `textContent`, así que al
        // guardar en markdown el código salía con todas sus líneas pegadas.
        for (const pre of [...molde.content.querySelectorAll('pre')]) {
            for (const br of [...pre.querySelectorAll('br')]) {
                br.replaceWith(document.createTextNode('\n'));
            }
        }

        // Un bloque de código es `<pre><code>`, no un `<pre>` a secas: es la
        // convención de HTML y es lo que produce el parser de markdown, así que
        // sin esto el mismo texto tendría dos formas según por dónde entrara.
        for (const pre of [...molde.content.querySelectorAll('pre')]) {
            if (pre.querySelector('code')) continue;

            const codigo = document.createElement('code');
            while (pre.firstChild) codigo.appendChild(pre.firstChild);
            pre.appendChild(codigo);
        }

        // Y los restos que deja el parser al deshacer ese anidamiento: al leer
        // `<p><ul>…` reparte el párrafo en dos mitades vacías, una a cada lado de
        // la lista. Un `<p><br></p>` NO entra aquí: esa es una línea en blanco
        // que el usuario ha pedido con un Enter.
        for (const parrafo of [...molde.content.querySelectorAll('p')]) {
            if (parrafo.childNodes.length === 0) {
                parrafo.remove();
            }
        }

        return molde.innerHTML;
    },

    limpiarAtributos(elemento) {
        const permitidos = ATRIBUTOS[elemento.tagName] ?? [];

        // La alineación se conserva ANTES de barrer los atributos, y solo si es
        // una de las nuestras. `execCommand` la escribe de tres formas distintas
        // según el navegador —`align="center"`, `style="text-align:center"` o
        // una clase—, así que las tres se traducen a la misma clase.
        const alineacion = ALINEABLES.includes(elemento.tagName) ? this.alineacionDe(elemento) : null;

        for (const atributo of [...elemento.attributes]) {
            if (!permitidos.includes(atributo.name)) {
                elemento.removeAttribute(atributo.name);
            }
        }

        if (alineacion) {
            elemento.setAttribute('class', alineacion);
        }

        if (elemento.tagName === 'IMG') {
            const origen = (elemento.getAttribute('src') ?? '').trim();

            if (!ORIGENES.test(origen) && origen.includes(':')) {
                elemento.remove();

                return;
            }

            elemento.setAttribute('loading', 'lazy');

            return;
        }

        if (elemento.tagName === 'A') {
            const destino = elemento.getAttribute('href') ?? '';

            if (!ESQUEMAS.test(destino.trim())) {
                // Un enlace sin destino aceptable deja de ser un enlace, pero su
                // texto se queda.
                elemento.replaceWith(...elemento.childNodes);
                return;
            }

            if (elemento.getAttribute('target') === '_blank') {
                elemento.setAttribute('rel', 'noopener noreferrer');
            }
        }
    },

    /** Qué alineación lleva un bloque, mire donde mire el navegador. */
    alineacionDe(elemento) {
        for (const clase of elemento.classList) {
            if (CLASES_ALINEACION.includes(clase)) return clase;
        }

        const enLinea = (elemento.style?.textAlign || elemento.getAttribute('align') || '').toLowerCase();

        return ALINEACIONES[enLinea] ?? null;
    },

    /**
     * Lo que sale del editor: HTML limpio, o markdown si se pidió así.
     *
     * En modo markdown se serializa desde el HTML **ya limpio**, no desde el que
     * escribe el navegador: de lo contrario un `<font>` o un `style` de un
     * pegado se colaría en la conversión y saldría como texto raro.
     */
    salida() {
        const html = this.limpiar(this.$refs.editable.innerHTML).trim();

        if (VACIOS.includes(html)) return '';

        if (!config.markdown) return html;

        const molde = document.createElement('div');
        molde.innerHTML = html;

        return aMarkdown(molde);
    },

    programarSincronia() {
        this._editando = true;
        clearTimeout(this._sincronizar);
        this._sincronizar = setTimeout(() => this.sincronizar(), config.debounce ?? 400);
    },

    sincronizar() {
        const html = this.salida();

        this.recontar();

        if (html === this.contenido) return;

        this.contenido = html;
        this._ultimoEnviado = html;

        const oculto = this.$refs.hiddenInput;

        if (oculto) {
            oculto.value = html;
            oculto.dispatchEvent(new Event('input', { bubbles: true }));
        }

        this.$dispatch('kore-editor-input', { html });
    },

    /** Se cierra la edición: es cuando `wire:model.blur` espera su evento. */
    cerrar() {
        clearTimeout(this._sincronizar);
        this._editando = false;
        this.sincronizar();

        const oculto = this.$refs.hiddenInput;

        if (oculto) {
            oculto.dispatchEvent(new Event('change', { bubbles: true }));
            oculto.dispatchEvent(new Event('blur', { bubbles: true }));
        }
    },

    recontar() {
        this.caracteres = (this.$refs.editable.innerText ?? '').replace(/ /g, ' ').trim().length;
        this.vacio = this.salida() === '';
    },

    // --------------------------------------------------------------- comandos

    /**
     * Un editable vacío no tiene ningún bloque dentro, y sin bloque los comandos
     * de párrafo —título, cita— no tienen sobre qué actuar: el navegador escribe
     * el texto suelto contra la raíz y `formatBlock` no hace nada. Se le pone un
     * párrafo desde el principio.
     */
    asegurarBloque() {
        if (config.readonly || config.disabled) return;

        const editable = this.$refs.editable;

        if (editable.innerHTML.trim() === '' || editable.innerHTML.trim() === '<br>') {
            editable.innerHTML = '<p><br></p>';

            const rango = document.createRange();
            rango.setStart(editable.firstChild, 0);
            rango.collapse(true);

            const seleccion = window.getSelection();
            seleccion.removeAllRanges();
            seleccion.addRange(rango);
        }
    },

    /**
     * Cuántos caracteres de texto hay por delante del cursor.
     *
     * Los comandos que reconstruyen el bloque —listas, títulos, cita— dejan el
     * cursor al principio: se escribía «primero», se pulsaba «lista» y lo
     * siguiente que se tecleaba salía DENTRO de la primera palabra. Un rango
     * guardado no sirve para volver, porque apunta a nodos que el comando acaba
     * de tirar; una posición contada en caracteres sobrevive a la reconstrucción.
     */
    posicionDelCursor() {
        const seleccion = window.getSelection();

        if (!seleccion?.rangeCount) return null;

        const rango = seleccion.getRangeAt(0);

        if (!this.$refs.editable.contains(rango.endContainer)) return null;

        const hasta = rango.cloneRange();
        hasta.selectNodeContents(this.$refs.editable);
        hasta.setEnd(rango.endContainer, rango.endOffset);

        return { posicion: hasta.toString().length, colapsada: seleccion.isCollapsed };
    },

    /**
     * Coloca el cursor —o marca un tramo, si se le dan dos posiciones— contando
     * caracteres desde el principio del editable.
     */
    colocarCursor(desde, hasta = null) {
        const editable = this.$refs.editable;
        const punto = (objetivo) => {
            const paseante = document.createTreeWalker(editable, NodeFilter.SHOW_TEXT);
            let recorrido = 0;
            let nodo;
            let ultimo = null;

            while ((nodo = paseante.nextNode())) {
                const largo = nodo.textContent.length;

                if (recorrido + largo >= objetivo) {
                    return [nodo, Math.max(0, objetivo - recorrido)];
                }

                recorrido += largo;
                ultimo = nodo;
            }

            // Más allá del final: al final de lo último que haya.
            return ultimo ? [ultimo, ultimo.textContent.length] : null;
        };

        const inicio = punto(desde);

        if (!inicio) return;

        const rango = document.createRange();
        rango.setStart(inicio[0], inicio[1]);

        const fin = hasta === null ? null : punto(hasta);

        if (fin) {
            rango.setEnd(fin[0], fin[1]);
        } else {
            rango.collapse(true);
        }

        const seleccion = window.getSelection();
        seleccion.removeAllRanges();
        seleccion.addRange(rango);
    },

    /**
     * @param reponerCursor `false` cuando el comando deja un bloque VACÍO donde
     *        escribir —el `<li>` recién creado por el autoformato—: la posición
     *        se cuenta en caracteres y un bloque sin ninguno no tiene posición
     *        propia, así que la cuenta cae al final del bloque anterior y lo
     *        siguiente que se teclea aparece allí.
     */
    ejecutar(comando, valor = null, reponerCursor = true) {
        if (config.readonly || config.disabled) return;

        // Solo si hace falta: `focus()` sobre un editable que ya lo tiene puede
        // colapsar el cursor al principio, y entonces lo siguiente que se escriba
        // aparece delante de lo que ya había.
        if (!this.tieneFoco()) this.$refs.editable.focus();

        this._editando = true;

        // Solo con el cursor suelto: si hay texto marcado, el comando lo respeta
        // y devolver el cursor a mano deshace la selección que el usuario ve.
        const antes = this.posicionDelCursor();

        try {
            // Con `styleWithCSS` activo, el navegador escribe estilos en línea en
            // vez de etiquetas, y `limpiar()` los tira: el formato se perdía al
            // sincronizar. Se apaga en cada comando porque la bandera es del
            // documento entero y cualquiera puede haberla cambiado.
            document.execCommand('styleWithCSS', false, false);
            document.execCommand(comando, false, valor);
        } catch {
            // No todos los motores implementan todos los comandos, y los que
            // faltan son justo aquellos sin los que esto sigue funcionando.
        }

        if (reponerCursor && antes?.colapsada) {
            this.colocarCursor(antes.posicion);
        }

        this.programarSincronia();
        this.leerFormatos();
    },

    /**
     * Alinea el bloque donde está el cursor.
     *
     * No se usa `justifyCenter` y compañía: con `styleWithCSS` apagado escriben
     * el atributo `align`, que está obsoleto desde HTML4, y encendido escriben un
     * `style` que la lista blanca tira. Se pone la clase directamente sobre el
     * bloque, que es lo que sobrevive al viaje.
     */
    alinear(direccion) {
        if (config.readonly || config.disabled) return;

        const bloque = this.bloqueActual();

        if (!bloque) return;

        const copia = bloque.cloneNode(true);

        for (const clase of CLASES_ALINEACION) copia.classList.remove(clase);

        // Volver a pulsar la que ya está vuelve a la izquierda, que es el defecto.
        if (direccion !== 'left' && this.alineacion !== ALINEACIONES[direccion]) {
            copia.classList.add(ALINEACIONES[direccion]);
        }

        if (copia.classList.length === 0) copia.removeAttribute('class');

        // El bloque se REEMPLAZA con `insertHTML` en vez de tocarle la clase a
        // mano. Cambiar `classList` directamente funciona, pero ocurre fuera del
        // historial del navegador: se centraba un párrafo, se pulsaba Ctrl+Z y el
        // deshacer se saltaba ese paso como si nunca hubiera existido.
        //
        const posicion = this.posicionDelCursor();
        const rango = document.createRange();

        // Un `<li>` no se puede reemplazar suelto: `insertHTML` lo sacaría de su
        // lista. Se reemplaza la LISTA entera con el elemento ya cambiado, que
        // mantiene la estructura y sigue entrando en el historial de deshacer.
        if (bloque.tagName === 'LI') {
            const lista = bloque.closest('ul, ol');

            if (!lista) return;

            const listaNueva = lista.cloneNode(true);
            const indice = [...lista.children].indexOf(bloque);

            if (indice === -1 || !listaNueva.children[indice]) return;

            listaNueva.children[indice].replaceWith(copia);
            rango.selectNode(lista);

            const seleccionLista = window.getSelection();
            seleccionLista.removeAllRanges();
            seleccionLista.addRange(rango);

            document.execCommand('insertHTML', false, listaNueva.outerHTML);

            if (posicion) this.colocarCursor(posicion.posicion);

            this.leerFormatos();
            this.programarSincronia();

            return;
        }

        rango.selectNode(bloque);

        const seleccion = window.getSelection();
        seleccion.removeAllRanges();
        seleccion.addRange(rango);

        document.execCommand('insertHTML', false, copia.outerHTML);

        if (posicion) this.colocarCursor(posicion.posicion);

        this.leerFormatos();
        this.programarSincronia();
    },

    bloque(etiqueta) {
        // Volver a pulsar el mismo bloque devuelve a párrafo, que es lo que
        // espera cualquiera que haya usado un editor.
        const actual = this.formatos[etiqueta.toLowerCase()];

        this.ejecutar('formatBlock', actual ? '<p>' : `<${etiqueta.toLowerCase()}>`);
    },

    leerFormatos() {
        clearTimeout(this._formatos);

        this._formatos = setTimeout(() => {
            const estado = (comando) => {
                try { return document.queryCommandState(comando); } catch { return false; }
            };

            const bloque = this.bloqueActual();
            this.alineacion = bloque ? this.alineacionDe(bloque) : null;

            this.formatos = {
                bold: estado('bold'),
                italic: estado('italic'),
                underline: estado('underline'),
                strike: estado('strikeThrough'),
                ul: estado('insertUnorderedList'),
                ol: estado('insertOrderedList'),
                h2: this.dentroDe('H2'),
                h3: this.dentroDe('H3'),
                quote: this.dentroDe('BLOCKQUOTE'),
                link: this.dentroDe('A'),
                pre: this.dentroDe('PRE'),
                left: this.alineacion === null,
                center: this.alineacion === ALINEACIONES.center,
                right: this.alineacion === ALINEACIONES.right,
            };
        }, 30);
    },

    /** ¿El cursor está dentro de una etiqueta de este tipo? */
    dentroDe(etiqueta) {
        const seleccion = window.getSelection();

        if (!seleccion?.anchorNode || !this.$refs.editable.contains(seleccion.anchorNode)) return false;

        let nodo = seleccion.anchorNode.nodeType === 1 ? seleccion.anchorNode : seleccion.anchorNode.parentElement;

        while (nodo && nodo !== this.$refs.editable) {
            if (nodo.tagName === etiqueta) return true;
            nodo = nodo.parentElement;
        }

        return false;
    },

    // ---------------------------------------------------------------- enlaces

    abrirEnlace() {
        if (config.readonly || config.disabled) return;

        // Abrir un diálogo mueve el foco y con él se va la selección: hay que
        // guardarse el rango o el enlace acabaría aplicado en cualquier parte.
        this.guardarRango();
        this.urlEnlace = this.dentroDe('A') ? (this.enlaceActual()?.getAttribute('href') ?? '') : '';
        this.dialogoEnlace = true;

        this.$nextTick(() => this.$refs.campoEnlace?.focus());
    },

    enlaceActual() {
        const seleccion = window.getSelection();
        let nodo = seleccion?.anchorNode?.nodeType === 1 ? seleccion.anchorNode : seleccion?.anchorNode?.parentElement;

        while (nodo && nodo !== this.$refs.editable) {
            if (nodo.tagName === 'A') return nodo;
            nodo = nodo.parentElement;
        }

        return null;
    },

    /**
     * Guarda qué había marcado, en caracteres contados desde el principio.
     *
     * Un `Range` clonado no vale aquí: escribir en el campo del diálogo mueve la
     * selección del documento y el rango viejo deja de tener sentido. Medido: el
     * enlace acababa aplicándose al documento entero en vez de a la palabra
     * marcada. Dos números sobreviven a cualquier cosa que pase en medio.
     */
    guardarRango() {
        const seleccion = window.getSelection();

        if (!seleccion?.rangeCount) {
            this.rango = null;

            return;
        }

        const rango = seleccion.getRangeAt(0);

        if (!this.$refs.editable.contains(rango.commonAncestorContainer)) {
            this.rango = null;

            return;
        }

        const medir = (nodo, desplazamiento) => {
            const hasta = document.createRange();
            hasta.selectNodeContents(this.$refs.editable);
            hasta.setEnd(nodo, desplazamiento);

            return hasta.toString().length;
        };

        this.rango = {
            desde: medir(rango.startContainer, rango.startOffset),
            hasta: medir(rango.endContainer, rango.endOffset),
        };
    },

    recuperarRango() {
        if (!this.rango) return;

        this.colocarCursor(this.rango.desde, this.rango.hasta === this.rango.desde ? null : this.rango.hasta);
    },

    aplicarEnlace() {
        const url = (this.urlEnlace ?? '').trim();

        if (!url) {
            this.dialogoEnlace = false;

            return;
        }

        // El mismo filtro que en la limpieza, pero aquí se puede avisar en vez de
        // dejar el enlace a medias: `javascript:` es la vía clásica del XSS.
        if (!ESQUEMAS.test(url)) {
            this.$dispatch('kore-editor-enlace-rechazado', { url });

            return;
        }

        // El foco primero, la selección después: al revés, devolver el foco al
        // editable colapsa lo que se acaba de marcar.
        this.$refs.editable.focus();
        this.recuperarRango();

        this.ejecutar('createLink', url);

        // El cursor se queda DETRÁS del enlace, no encima. `createLink` deja el
        // texto seleccionado, así que la siguiente letra que se escriba lo
        // borraría entero: se acaba de enlazar una palabra y desaparece.
        if (this.rango) this.colocarCursor(this.rango.hasta);

        this.urlEnlace = '';

        // El diálogo se cierra al FINAL. Cerrándolo antes, el campo de la URL
        // —que tiene el foco— desaparece de la pantalla en mitad de la
        // operación, la selección se va con él y `createLink` se comía la
        // palabra en vez de enlazarla.
        this.dialogoEnlace = false;
    },

    quitarEnlace() {
        this.ejecutar('unlink');
    },

    // ------------------------------------------------------- bloques de código

    /** El `<pre>` donde está el cursor, si lo hay. */
    codigoActual() {
        const seleccion = window.getSelection();

        if (!seleccion?.anchorNode || !this.$refs.editable.contains(seleccion.anchorNode)) return null;

        let nodo = seleccion.anchorNode.nodeType === 1 ? seleccion.anchorNode : seleccion.anchorNode.parentElement;

        while (nodo && nodo !== this.$refs.editable) {
            if (nodo.tagName === 'PRE') return nodo;
            nodo = nodo.parentElement;
        }

        return null;
    },

    /**
     * ¿El cursor está al final del bloque y la última línea está vacía?
     *
     * Es la señal de «quiero salir», la misma que usan los editores que uno
     * conoce: un Enter escribe el salto, el segundo saca del bloque.
     */
    finalVacioDelCodigo() {
        const pre = this.codigoActual();

        if (!pre) return false;

        const texto = pre.textContent ?? '';

        // Solo al final del todo, no en mitad del código.
        if (this.desplazamientoEnBloque(pre) < texto.length) return false;

        // El salto de línea dentro de un `contenteditable` es un `<br>`, no un
        // `\n`: mirando solo el texto, la última línea nunca parecía vacía y del
        // bloque no se salía jamás.
        const contenido = (pre.querySelector('code') ?? pre).innerHTML ?? '';

        return /<br\s*\/?>\s*$/i.test(contenido) || texto.trim() === '';
    },

    /** Quita el `<br>` final —era la señal de salir, no código— y sale. */

    salirDelCodigo() {
        const pre = this.codigoActual();

        if (!pre) return;

        const codigo = pre.querySelector('code') ?? pre;
        // Todos los del final, no el último: Chrome escribe DOS `<br>` al saltar
        // de línea —el segundo es para que el cursor se vea— y quitando uno solo
        // quedaba una línea en blanco colgando dentro del bloque.
        codigo.innerHTML = (codigo.innerHTML ?? '').replace(/(?:<br\s*\/?>\s*)+$/i, '');

        const parrafo = document.createElement('p');
        parrafo.appendChild(document.createElement('br'));
        pre.after(parrafo);

        const rango = document.createRange();
        rango.setStart(parrafo, 0);
        rango.collapse(true);

        const seleccion = window.getSelection();
        seleccion.removeAllRanges();
        seleccion.addRange(rango);

        this.programarSincronia();
        this.leerFormatos();
    },

    // -------------------------------------------------------------- autoformato

    /**
     * Los prefijos que convierten el bloque al terminar de escribirlos.
     *
     * Es la costumbre que trae cualquiera que haya usado un editor moderno:
     * escribir `## ` y ver cómo la línea se vuelve un título, sin ir a la barra.
     * Se dispara con el ESPACIO, no con cada tecla: hacerlo al vuelo convertiría
     * un `-` recién escrito en una lista antes de saber si el usuario iba a
     * escribir «- pero» o «-5 grados».
     */
    autoformatear(evento) {
        if (evento.key !== ' ' || config.readonly || config.disabled) return;

        const bloque = this.bloqueActual();

        if (!bloque) return;

        // Solo cuenta el prefijo cuando el cursor está justo detrás de él: en
        // mitad de un párrafo, un «- » es un guion y ya.
        const posicion = this.posicionDelCursor();
        const texto = bloque.textContent ?? '';
        const antes = texto.slice(0, this.desplazamientoEnBloque(bloque));

        const reglas = [
            [/^#$/, () => this.ejecutar('formatBlock', '<h2>', false)],
            [/^##$/, () => this.ejecutar('formatBlock', '<h3>', false)],
            [/^[-*+]$/, () => this.ejecutar('insertUnorderedList', null, false)],
            [/^\d+[.)]$/, () => this.ejecutar('insertOrderedList', null, false)],
            [/^>$/, () => this.ejecutar('formatBlock', '<blockquote>', false)],
        ];

        for (const [patron, aplicar] of reglas) {
            if (!patron.test(antes)) continue;

            evento.preventDefault();

            // Fuera el prefijo: era sintaxis, no texto. Se borra ANTES de
            // convertir, o el título se quedaría con su propia almohadilla dentro.
            this.borrarDelBloque(bloque, antes.length);
            aplicar();

            this.programarSincronia();

            return;
        }
    },

    /**
     * El bloque MÁS CERCANO al cursor, no el hijo directo del editable.
     *
     * Chrome anida: al salir de una lista deja el párrafo nuevo dentro del `<p>`
     * que envuelve la `<ul>`. Buscando el hijo directo, el «bloque» era ese
     * envoltorio y su texto incluía el de la lista entera, así que ningún prefijo
     * volvía a coincidir después de la primera lista.
     */
    bloqueActual() {
        const BLOQUES = ['P', 'H2', 'H3', 'LI', 'BLOCKQUOTE', 'DIV'];
        const seleccion = window.getSelection();

        if (!seleccion?.anchorNode || !this.$refs.editable.contains(seleccion.anchorNode)) return null;

        let nodo = seleccion.anchorNode.nodeType === 1 ? seleccion.anchorNode : seleccion.anchorNode.parentElement;

        while (nodo && nodo !== this.$refs.editable) {
            if (BLOQUES.includes(nodo.tagName)) return nodo;
            nodo = nodo.parentElement;
        }

        return null;
    },

    /** Cuántos caracteres hay entre el principio del bloque y el cursor. */
    desplazamientoEnBloque(bloque) {
        const seleccion = window.getSelection();

        if (!seleccion?.rangeCount) return 0;

        const rango = seleccion.getRangeAt(0);
        const hasta = rango.cloneRange();
        hasta.selectNodeContents(bloque);
        hasta.setEnd(rango.endContainer, rango.endOffset);

        return hasta.toString().length;
    },

    /**
     * Quita el prefijo que se acaba de escribir.
     *
     * Se marca y se borra con el propio motor de edición en vez de vaciar nodos
     * a mano: `deleteContents()` deja el bloque sin ningún nodo de texto, la
     * selección se sube al editable y el `formatBlock` siguiente se aplica al
     * documento entero —medido: el primer título se tragaba todo lo que se
     * escribiera después—. Además así el borrado entra en el historial de
     * deshacer, que es lo que espera quien escribe `- ` sin querer.
     */
    borrarDelBloque(bloque, cuantos) {
        const paseante = document.createTreeWalker(bloque, NodeFilter.SHOW_TEXT);
        const nodo = paseante.nextNode();

        if (!nodo) return;

        const rango = document.createRange();
        rango.setStart(nodo, 0);
        rango.setEnd(nodo, Math.min(cuantos, nodo.textContent.length));

        const seleccion = window.getSelection();
        seleccion.removeAllRanges();
        seleccion.addRange(rango);

        document.execCommand('delete');
    },

    // ---------------------------------------------------------------- imágenes

    /**
     * Abre el selector de archivos.
     *
     * El `<input type="file">` vive escondido en el componente en vez de
     * fabricarse al vuelo: uno creado al momento y no insertado en el documento
     * no dispara `change` en algunos navegadores, y el archivo se pierde sin
     * error ninguno.
     */
    elegirImagen() {
        if (this.edicionBloqueada() || !config.upload) return;

        this.guardarRango();
        this.$refs.archivo?.click();
    },

    edicionBloqueada() {
        return Boolean(config.readonly || config.disabled);
    },

    alElegirArchivo(evento) {
        const archivo = evento.target.files?.[0];

        // Se limpia el input o elegir DOS VECES el mismo archivo no dispararía
        // el segundo `change`: para el navegador el valor no ha cambiado.
        evento.target.value = '';

        if (archivo) this.subirImagen(archivo);
    },

    /**
     * Sube una imagen y la inserta donde estaba el cursor.
     *
     * El viaje son dos pasos y no uno: `$wire.upload()` deja el archivo en una
     * propiedad como temporal, y el método del componente decide dónde se guarda
     * y con qué URL se sirve. Esa decisión es de la aplicación, no de la
     * librería: un disco público, S3, una ruta firmada… aquí solo se sabe que
     * hace falta una URL.
     */
    subirImagen(archivo) {
        if (this.edicionBloqueada() || !config.upload) return;

        const limite = config.upload.maxSize;
        const tipos = config.upload.mimes;

        // Se comprueba aquí antes de gastar el viaje: subir diez megas para que
        // el servidor conteste que sobran nueve es tiempo del usuario.
        if (tipos?.length && !tipos.includes(archivo.type)) {
            this.fallarImagen(config.upload.mensajes?.tipo ?? 'Ese tipo de archivo no se admite.');

            return;
        }

        if (limite && archivo.size > limite * 1024) {
            this.fallarImagen(config.upload.mensajes?.tamano ?? 'La imagen es demasiado grande.');

            return;
        }

        if (!this.$wire) {
            this.fallarImagen(config.upload.mensajes?.error ?? 'No se pudo subir la imagen.');

            return;
        }

        this.errorImagen = null;
        this.progresoImagen = 0;

        this.$wire.upload(
            config.upload.property,
            archivo,
            () => {
                this.$wire.call(config.upload.method)
                    .then((url) => {
                        this.progresoImagen = null;

                        if (!url) {
                            this.fallarImagen(config.upload.mensajes?.error ?? 'No se pudo subir la imagen.');

                            return;
                        }

                        this.insertarImagen(url, archivo.name);
                    })
                    .catch(() => {
                        this.progresoImagen = null;
                        this.fallarImagen(config.upload.mensajes?.error ?? 'No se pudo subir la imagen.');
                    });
            },
            () => {
                this.progresoImagen = null;
                this.fallarImagen(config.upload.mensajes?.error ?? 'No se pudo subir la imagen.');
            },
            (evento) => {
                this.progresoImagen = evento.detail?.progress ?? 0;
            }
        );
    },

    fallarImagen(mensaje) {
        this.progresoImagen = null;
        this.errorImagen = mensaje;
        this.$dispatch('kore-editor-imagen-rechazada', { mensaje });
    },

    insertarImagen(url, nombre = '') {
        // El texto alternativo sale del nombre del archivo, sin extensión: no es
        // una descripción, pero es infinitamente mejor que la cadena vacía y el
        // autor puede cambiarlo. Una imagen sin `alt` no existe para quien no la ve.
        const alt = nombre.replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ').trim();

        this.$refs.editable.focus();
        this.recuperarRango();

        this.ejecutar('insertHTML', `<img src="${url}" alt="${alt.replace(/"/g, '&quot;')}">`);
    },

    /** Las imágenes que vienen en un pegado o en un arrastre. */
    imagenesDe(datos) {
        if (!datos) return [];

        return [...(datos.files ?? [])].filter((archivo) => archivo.type.startsWith('image/'));
    },

    alSoltar(evento) {
        const imagenes = this.imagenesDe(evento.dataTransfer);

        if (!config.upload || imagenes.length === 0) return;

        // Sin esto el navegador abre la imagen y se lleva la página por delante.
        evento.preventDefault();

        this.guardarRango();
        this.subirImagen(imagenes[0]);
    },

    // ----------------------------------------------------------------- teclado

    alTeclear(evento) {
        if (config.readonly) {
            // Se dejan pasar las teclas que no escriben: mover el cursor,
            // seleccionar y copiar siguen teniendo sentido en solo lectura.
            const inofensiva = evento.ctrlKey || evento.metaKey
                || ['Tab', 'ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Home', 'End', 'PageUp', 'PageDown'].includes(evento.key);

            if (!inofensiva) evento.preventDefault();

            return;
        }

        this.autoformatear(evento);

        if (evento.defaultPrevented) return;

        // Tab SOLO dentro de una lista, donde significa «anidar». Fuera de ella
        // se deja pasar a propósito: capturarlo siempre encerraría al usuario de
        // teclado dentro del editor, sin forma de llegar al resto del formulario.
        if (evento.key === 'Tab' && this.dentroDe('LI')) {
            evento.preventDefault();
            this.ejecutar(evento.shiftKey ? 'outdent' : 'indent');

            return;
        }

        // Enter al final de un bloque de código, con la última línea vacía: es
        // como se sale de él. Dentro, Enter escribe saltos y nunca terminaba —la
        // única salida era el botón—.
        if (evento.key === 'Enter' && !evento.shiftKey && this.dentroDe('PRE') && this.finalVacioDelCodigo()) {
            evento.preventDefault();
            this.salirDelCodigo();

            return;
        }

        const atajo = (evento.ctrlKey || evento.metaKey) && !evento.altKey;

        if (atajo) {
            const comandos = { b: 'bold', i: 'italic', u: 'underline' };
            const comando = comandos[evento.key.toLowerCase()];

            if (comando) {
                evento.preventDefault();
                this.ejecutar(comando);
                return;
            }

            if (evento.key.toLowerCase() === 'k') {
                evento.preventDefault();
                this.abrirEnlace();
                return;
            }
        }

        if (config.maxlength && this.caracteres >= config.maxlength) {
            const escribe = evento.key.length === 1 && !evento.ctrlKey && !evento.metaKey;

            // Borrar y moverse siguen permitidos: si no, el campo se queda
            // atascado en el límite sin forma de corregir.
            if (escribe) evento.preventDefault();
        }
    },

    alPegar(evento) {
        if (config.readonly || config.disabled) return;

        // Una captura de pantalla en el portapapeles llega como archivo, no como
        // HTML: sin esto se perdía sin decir nada.
        const imagenes = this.imagenesDe(evento.clipboardData);

        if (config.upload && imagenes.length > 0) {
            evento.preventDefault();
            this.guardarRango();
            this.subirImagen(imagenes[0]);

            return;
        }

        evento.preventDefault();

        const datos = evento.clipboardData;
        const html = datos?.getData('text/html');
        const texto = datos?.getData('text/plain') ?? '';

        // Pegar desde un procesador de textos trae hojas de estilo enteras y
        // etiquetas de Office. Se limpia antes de que entre, no después.
        const limpio = html
            ? this.limpiar(html)
            : texto.split(/\n{2,}/).map((p) => `<p>${p.replace(/\n/g, '<br>')}</p>`).join('');

        document.execCommand('insertHTML', false, limpio);

        this.programarSincronia();
    },

    // -------------------------------------------------------- pantalla completa

    /**
     * El editor a pantalla completa.
     *
     * Bloquea el desplazamiento de la página con el mismo contador que usan los
     * modales y el cajón lateral: si cada cosa lo tomara por su cuenta, la
     * primera en cerrarse devolvería el scroll con las demás todavía abiertas.
     * La clave es una cadena por instancia, no el objeto del componente: Alpine
     * construye un proxy nuevo en cada expresión y el `Set` nunca reconocería al
     * que registró el bloqueo.
     */
    alternarPantallaCompleta() {
        this.pantallaCompleta = !this.pantallaCompleta;

        if (this.pantallaCompleta) {
            bloquearScroll(this._claveScroll);
        } else {
            soltarScroll(this._claveScroll);
        }

        this.$nextTick(() => this.$refs.editable?.focus());
    },

    // ----------------------------------------------------- barra de herramientas

    /** Los botones de la barra, para el recorrido con flechas. */
    botones() {
        return this.$refs.toolbar ? [...this.$refs.toolbar.querySelectorAll('button')] : [];
    },

    /**
     * Una barra de herramientas es UN solo parada del tabulador: se entra con
     * Tab y se recorre con las flechas. Sin esto, llegar al texto desde el
     * principio del formulario costaba una pulsación por botón.
     */
    rove() {
        this.botones().forEach((boton, indice) => boton.setAttribute('tabindex', indice === 0 ? '0' : '-1'));
    },

    mover(direccion) {
        const botones = this.botones();
        const actual = botones.indexOf(document.activeElement);

        if (actual === -1) return;

        const siguiente = (actual + direccion + botones.length) % botones.length;

        botones[actual].setAttribute('tabindex', '-1');
        botones[siguiente].setAttribute('tabindex', '0');
        botones[siguiente].focus();
    },
});
