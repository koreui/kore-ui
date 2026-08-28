/**
 * La traducción entre lo que el usuario ve y lo que se guarda, cuando el editor
 * trabaja en markdown.
 *
 * El editor sigue siendo el mismo por dentro: un `contenteditable` con HTML. Lo
 * que cambia es la puerta. Al salir, ese HTML se convierte en markdown; al
 * entrar, el markdown se convierte en HTML. Lo que viaja y se guarda es texto
 * plano, y el HTML definitivo lo fabrica el servidor con `KoreUi\Editor\Markdown`.
 *
 * **Las dos mitades tienen que coincidir.** Si `aMarkdown` escribe algo que
 * `aHtml` no sabe leer, el texto cambia solo al recargar la página: se escribe
 * una cita, se guarda, se vuelve y es un párrafo. Por eso ambas cubren
 * exactamente el mismo subconjunto, y el de PHP también.
 */

/** Lo que hay que escapar para que no se lea como sintaxis al volver. */
const escapar = (texto) => texto.replace(/([\\`*_[\]#>~])/g, '\\$1');

/** Escapa para meter texto en HTML. El otro `escapar` es para markdown. */
const escaparHtmlSuelto = (v) => v.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

/** HTML → markdown. */
export function aMarkdown(raiz) {
    const bloques = [];

    for (const nodo of raiz.childNodes) {
        if (nodo.nodeType === 3) {
            const suelto = nodo.textContent.trim();

            // Texto que el navegador dejó fuera de cualquier bloque: cuenta como
            // párrafo, o se perdería sin más.
            if (suelto) bloques.push(escapar(suelto));

            continue;
        }

        if (nodo.nodeType !== 1) continue;

        switch (nodo.tagName) {
            case 'H2':
                bloques.push('## ' + enLinea(nodo));
                break;

            case 'H3':
                bloques.push('### ' + enLinea(nodo));
                break;

            case 'UL':
            case 'OL':
                bloques.push([...nodo.querySelectorAll(':scope > li')]
                    .map((li, i) => (nodo.tagName === 'OL' ? `${i + 1}. ` : '- ') + enLinea(li))
                    .join('\n'));
                break;

            case 'PRE':
                // El texto de dentro va TAL CUAL: escaparlo metería barras
                // invertidas en el código de quien lo escribió.
                bloques.push('```\n' + (nodo.textContent ?? '') + '\n```');
                break;

            case 'BLOCKQUOTE':
                // La cita puede traer párrafos dentro; cada línea lleva su `>`.
                bloques.push(enLinea(nodo).split('\n').map((l) => '> ' + l).join('\n'));
                break;

            // Una imagen suelta en la raíz —así la deja `insertHTML` cuando el
            // cursor no estaba dentro de un párrafo— no tiene hijos, así que
            // `enLinea()` no la vería y se perdía al guardar.
            case 'IMG': {
                const origen = nodo.getAttribute('src') ?? '';
                if (origen) bloques.push(`![${escapar(nodo.getAttribute('alt') ?? '')}](${origen})`);
                break;
            }

            case 'BR':
                break;

            default: {
                const contenido = enLinea(nodo);
                if (contenido.trim()) bloques.push(contenido);
            }
        }
    }

    // Un bloque por línea en blanco: es lo que separa párrafos en markdown.
    return bloques.filter((b) => b.trim() !== '').join('\n\n');
}

/** El formato dentro de un bloque. */
function enLinea(nodo) {
    let salida = '';

    for (const hijo of nodo.childNodes) {
        if (hijo.nodeType === 3) {
            salida += escapar(hijo.textContent);
            continue;
        }

        if (hijo.nodeType !== 1) continue;

        const dentro = enLinea(hijo);

        switch (hijo.tagName) {
            case 'STRONG': salida += dentro.trim() ? `**${dentro}**` : dentro; break;
            case 'EM': salida += dentro.trim() ? `*${dentro}*` : dentro; break;
            case 'S': salida += dentro.trim() ? `~~${dentro}~~` : dentro; break;
            // Markdown no tiene subrayado. Se guarda el texto sin él, que es
            // preferible a inventarse una sintaxis que el servidor no leería.
            case 'U': salida += dentro; break;
            case 'CODE': salida += `\`${hijo.textContent}\``; break;
            case 'A': {
                const destino = hijo.getAttribute('href') ?? '';
                salida += destino ? `[${dentro}](${destino})` : dentro;
                break;
            }
            case 'IMG': {
                const origen = hijo.getAttribute('src') ?? '';
                salida += origen ? `![${escapar(hijo.getAttribute('alt') ?? '')}](${origen})` : '';
                break;
            }
            case 'BR': salida += '\n'; break;
            case 'P': salida += (salida && !salida.endsWith('\n') ? '\n' : '') + dentro; break;
            default: salida += dentro;
        }
    }

    return salida;
}

/**
 * Markdown → HTML.
 *
 * Es la misma gramática que `KoreUi\Editor\Markdown::aHtml()`, en el mismo
 * orden: escapar primero, apartar el código después, y los marcadores al final.
 * El HTML que sale de aquí solo se usa para PINTAR el editor —lo que se guarda
 * es el markdown—, pero igual pasa por la limpieza del componente.
 */
export function aHtml(markdown) {
    const texto = (markdown ?? '').trim();

    if (texto === '') return '';

    const salida = [];
    let parrafo = [];
    let lista = null;
    let items = [];
    let cita = [];
    let codigo = null;

    const cerrarParrafo = () => {
        if (!parrafo.length) return;
        salida.push('<p>' + parrafo.map(linea).join('<br>') + '</p>');
        parrafo = [];
    };

    const cerrarLista = () => {
        if (!lista) return;
        salida.push(`<${lista}>` + items.map((i) => `<li>${linea(i)}</li>`).join('') + `</${lista}>`);
        lista = null;
        items = [];
    };

    const cerrarCita = () => {
        if (!cita.length) return;
        salida.push('<blockquote><p>' + cita.map(linea).join('<br>') + '</p></blockquote>');
        cita = [];
    };

    for (const cruda of texto.split(/\r?\n/)) {
        const l = cruda.replace(/\s+$/, '');

        // Dentro de un bloque de código no se interpreta nada, así que se mira
        // antes que ninguna otra regla.
        if (codigo !== null) {
            if (/^ {0,3}(`{3,}|~{3,})\s*$/.test(l)) {
                salida.push('<pre><code>' + escaparHtmlSuelto(codigo.join('\n')) + '</code></pre>');
                codigo = null;
                continue;
            }

            codigo.push(cruda);
            continue;
        }

        if (/^ {0,3}(`{3,}|~{3,})\s*\S*\s*$/.test(l)) {
            cerrarParrafo(); cerrarLista(); cerrarCita();
            codigo = [];
            continue;
        }

        if (!l.trim()) {
            cerrarParrafo(); cerrarLista(); cerrarCita();
            continue;
        }

        let m = l.match(/^ {0,3}(#{1,6})\s+(.*)$/);

        if (m) {
            cerrarParrafo(); cerrarLista(); cerrarCita();
            const nivel = m[1].length <= 2 ? 2 : 3;
            salida.push(`<h${nivel}>${linea(m[2].replace(/[\s#]+$/, ''))}</h${nivel}>`);
            continue;
        }

        m = l.match(/^ {0,3}> ?(.*)$/);

        if (m) {
            cerrarParrafo(); cerrarLista();
            cita.push(m[1]);
            continue;
        }

        m = l.match(/^ {0,3}([-+*]|\d+[.)])\s+(.*)$/);

        if (m) {
            cerrarParrafo(); cerrarCita();
            const tipo = ['-', '+', '*'].includes(m[1]) ? 'ul' : 'ol';
            if (lista && lista !== tipo) cerrarLista();
            lista = tipo;
            items.push(m[2]);
            continue;
        }

        cerrarLista(); cerrarCita();
        parrafo.push(l);
    }

    cerrarParrafo(); cerrarLista(); cerrarCita();

    // Un bloque sin cerrar —el texto se acabó antes— se cierra igual: perderlo
    // sería tirar lo que el usuario escribió.
    if (codigo !== null) salida.push('<pre><code>' + escaparHtmlSuelto(codigo.join('\n')) + '</code></pre>');

    return salida.join('');
}

const ABRE = '';
const CIERRA = '';

function linea(texto) {
    const apartados = [];
    const apartar = (html) => `${ABRE}${apartados.push(html) - 1}${CIERRA}`;
    const escaparHtml = (v) => v.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

    let salida = escaparHtml(texto)
        .replace(/\\([\\`*_[\]#>~\-+.!()])/g, (_, c) => apartar(escaparHtml(c)))
        .replace(/(`+)([^`]+?)\1/g, (_, __, codigo) => apartar(`<code>${codigo}</code>`))
        // Las imágenes ANTES que los enlaces: `![alt](url)` empieza por `!` y
        // luego es igual, así que al revés los enlaces se llevan la parte de
        // dentro y dejan el `!` suelto.
        .replace(/!\[([^\]]*)]\(((?:[^()\s]|\([^()\s]*\))+)\)/g, (_, alt, origen) =>
            /^(https?:|\/|\.\/|\.\.\/)/i.test(origen.trim()) || !origen.includes(':')
                ? apartar(`<img src="${escaparHtml(origen)}" alt="${escaparHtml(alt)}" loading="lazy">`)
                : apartar(alt))
        .replace(/\[([^\]]+)]\(((?:[^()\s]|\([^()\s]*\))+)\)/g, (_, txt, destino) =>
            /^(https?:|mailto:|tel:|#|\/|\.\/|\.\.\/)/i.test(destino.trim())
                ? apartar(`<a href="${escaparHtml(destino)}">${txt}</a>`)
                : apartar(txt))
        .replace(/(\*\*|__)(?=\S)([\s\S]*?\S)\1/g, '<strong>$2</strong>')
        .replace(/(?<![\w*])(\*|_)(?=\S)([\s\S]*?\S)\1(?![\w*])/g, '<em>$2</em>')
        .replace(/~~(?=\S)([\s\S]*?\S)~~/g, '<s>$1</s>');

    return salida.replace(new RegExp(`${ABRE}(\\d+)${CIERRA}`, 'g'), (_, i) => apartados[Number(i)] ?? '');
}
