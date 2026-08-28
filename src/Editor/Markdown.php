<?php

namespace KoreUi\Editor;

/**
 * El markdown que produce y consume `<x-kore::editor markdown>`.
 *
 * **Por qué guardar markdown en vez de HTML.** Guardando HTML, lo que hay en la
 * base de datos es marcado que alguien pintará con `{!! !!}`, y entonces todo
 * depende de que `HtmlSanitizer` se haya ejecutado por el camino: si un día
 * alguien guarda sin pasar por ahí, el agujero queda abierto. Guardando markdown
 * lo almacenado es **texto plano**, y el HTML no existe hasta que este parser lo
 * fabrica —con las etiquetas que decide él, no con las que venían—. El XSS deja
 * de ser algo que haya que acordarse de prevenir.
 *
 * ```blade
 * <x-kore::editor markdown wire:model="cuerpo" />
 * …
 * {!! KoreUi\Editor\Markdown::aHtml($articulo->cuerpo) !!}
 * ```
 *
 * **Qué NO es.** No es CommonMark. Cubre exactamente lo que el editor sabe
 * escribir —títulos de dos niveles, negrita, cursiva, tachado, código, listas,
 * cita y enlaces— y nada más. Para markdown de verdad, con tablas y notas al
 * pie, está league/commonmark; esto son doscientas líneas sin dependencias que
 * casan con la barra de herramientas botón por botón.
 */
class Markdown
{
    /** Los mismos esquemas que admite el resto del editor. */
    private const ESQUEMAS = ['http:', 'https:', 'mailto:', 'tel:'];

    /**
     * Marcadores de la zona de uso privado de Unicode: sirven para apartar
     * trozos ya convertidos —el interior de un `código`— de forma que la pasada
     * siguiente no los vuelva a mirar. No pueden chocar con nada que se teclee.
     */
    private const ABRE = "\u{E000}";

    private const CIERRA = "\u{E001}";

    public static function aHtml(?string $markdown): string
    {
        $texto = trim((string) $markdown);

        if ($texto === '') {
            return '';
        }

        $lineas = preg_split('/\R/', $texto);
        $salida = [];
        $parrafo = [];
        $lista = null;          // 'ul' | 'ol' | null
        $items = [];
        $cita = [];
        $codigo = null;         // las líneas de dentro de un bloque de código

        // Un párrafo, una lista o una cita se acumulan hasta que llega algo que
        // no encaja; entonces se cierran. Sin esto, dos líneas seguidas de texto
        // saldrían como dos párrafos y no como uno con un salto.
        $cerrarParrafo = function () use (&$parrafo, &$salida) {
            if ($parrafo === []) {
                return;
            }

            $salida[] = '<p>' . implode('<br>', array_map([self::class, 'linea'], $parrafo)) . '</p>';
            $parrafo = [];
        };

        $cerrarLista = function () use (&$lista, &$items, &$salida) {
            if ($lista === null) {
                return;
            }

            $salida[] = "<{$lista}>" . implode('', array_map(
                fn ($item) => '<li>' . self::linea($item) . '</li>',
                $items
            )) . "</{$lista}>";

            $lista = null;
            $items = [];
        };

        $cerrarCita = function () use (&$cita, &$salida) {
            if ($cita === []) {
                return;
            }

            $salida[] = '<blockquote><p>' . implode('<br>', array_map([self::class, 'linea'], $cita)) . '</p></blockquote>';
            $cita = [];
        };

        foreach ($lineas as $linea) {
            $limpia = rtrim($linea);

            // Dentro de un bloque de código no se interpreta NADA: una almohadilla
            // ahí dentro es una almohadilla, no un título. Por eso se mira antes
            // que ninguna otra regla y se sale.
            if ($codigo !== null) {
                if (preg_match('/^ {0,3}(`{3,}|~{3,})\s*$/', $limpia)) {
                    $salida[] = '<pre><code>' . htmlspecialchars(implode("\n", $codigo), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8') . '</code></pre>';
                    $codigo = null;

                    continue;
                }

                $codigo[] = $linea;

                continue;
            }

            if (preg_match('/^ {0,3}(`{3,}|~{3,})\s*\S*\s*$/', $limpia)) {
                $cerrarParrafo();
                $cerrarLista();
                $cerrarCita();

                $codigo = [];

                continue;
            }

            if (trim($limpia) === '') {
                $cerrarParrafo();
                $cerrarLista();
                $cerrarCita();

                continue;
            }

            // Título. El editor solo escribe dos niveles; los `#` de más se
            // quedan en el último que existe en vez de perderse.
            if (preg_match('/^ {0,3}(#{1,6})\s+(.*)$/', $limpia, $coincidencia)) {
                $cerrarParrafo();
                $cerrarLista();
                $cerrarCita();

                $nivel = strlen($coincidencia[1]) <= 2 ? 2 : 3;
                $salida[] = "<h{$nivel}>" . self::linea(trim($coincidencia[2], " \t#")) . "</h{$nivel}>";

                continue;
            }

            // Cita.
            if (preg_match('/^ {0,3}> ?(.*)$/', $limpia, $coincidencia)) {
                $cerrarParrafo();
                $cerrarLista();

                $cita[] = $coincidencia[1];

                continue;
            }

            // Elemento de lista, con o sin número.
            if (preg_match('/^ {0,3}([-+*]|\d+[.)])\s+(.*)$/', $limpia, $coincidencia)) {
                $cerrarParrafo();
                $cerrarCita();

                $tipo = in_array($coincidencia[1], ['-', '+', '*'], true) ? 'ul' : 'ol';

                // Cambiar de viñetas a números cierra una lista y abre otra.
                if ($lista !== null && $lista !== $tipo) {
                    $cerrarLista();
                }

                $lista = $tipo;
                $items[] = $coincidencia[2];

                continue;
            }

            $cerrarLista();
            $cerrarCita();

            $parrafo[] = $limpia;
        }

        $cerrarParrafo();
        $cerrarLista();
        $cerrarCita();

        // Un bloque de código sin su valla de cierre —el texto se acabó antes—
        // se cierra igualmente: perderlo sería tirar lo que el usuario escribió.
        if ($codigo !== null) {
            $salida[] = '<pre><code>' . htmlspecialchars(implode("\n", $codigo), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8') . '</code></pre>';
        }

        return implode('', $salida);
    }

    /**
     * El formato dentro de una línea.
     *
     * El orden importa. Primero se escapa TODO —lo que llega es texto, y un `<`
     * que el usuario escribió tiene que salir como `&lt;`, no como el principio
     * de una etiqueta—. Después se apartan los trozos de código, para que un
     * asterisco dentro de un `2 * 3` entre comillas no se lea como cursiva. Y
     * solo al final se miran los pares de marcadores.
     */
    private static function linea(string $texto): string
    {
        $apartados = [];

        $apartar = function (string $html) use (&$apartados) {
            $apartados[] = $html;

            return self::ABRE . (count($apartados) - 1) . self::CIERRA;
        };

        $escapar = fn (string $valor) => htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');

        $salida = $escapar($texto);

        // Escapes con barra invertida: se apartan antes de que nada pueda leerlos
        // como sintaxis.
        $salida = preg_replace_callback('/\\\\([\\\\`*_\[\]#>~\-+.!()])/u',
            fn ($c) => $apartar($escapar($c[1])),
            $salida);

        // Código: lo primero que se aparta, para que su interior no se formatee.
        $salida = preg_replace_callback('/(`+)([^`]+?)\1/u',
            fn ($c) => $apartar('<code>' . $c[2] . '</code>'),
            $salida);

        // Imágenes, ANTES que los enlaces: `![alt](url)` empieza por `!` seguido
        // de la misma forma, así que si los enlaces miran primero se llevan la
        // parte de dentro y dejan el `!` suelto.
        //
        // `data:` no entra tampoco aquí: un SVG en base64 puede llevar un script
        // dentro y ejecutarse si alguien abre la imagen en su propia pestaña.
        $salida = preg_replace_callback('/!\[([^\]]*)]\(((?:[^()\s]|\([^()\s]*\))+)\)/u', function ($c) use ($apartar, $escapar) {
            $origen = html_entity_decode($c[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if (! self::origenDeImagenAceptable($origen)) {
                return $apartar($c[1]);   // se queda el texto alternativo
            }

            return $apartar('<img src="' . $escapar($origen) . '" alt="' . $escapar($c[1]) . '" loading="lazy">');
        }, $salida);

        // Enlaces. El destino se valida igual que en el sanitizador: un
        // `javascript:` aquí sería el mismo agujero por otra puerta.
        // El destino admite un nivel de paréntesis balanceados, como cualquier
        // parser de markdown. Cortando en el primer `)`, un
        // `[x](javascript:alert(1))` se partía en dos: el destino se rechazaba
        // —bien— pero el paréntesis sobrante se quedaba en el texto.
        $salida = preg_replace_callback('/\[([^\]]+)]\(((?:[^()\s]|\([^()\s]*\))+)\)/u', function ($c) use ($apartar, $escapar) {
            $destino = html_entity_decode($c[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if (! self::destinoAceptable($destino)) {
                return $apartar($c[1]);   // se queda el texto, sin enlace
            }

            return $apartar('<a href="' . $escapar($destino) . '">' . $c[1] . '</a>');
        }, $salida);

        // Negrita antes que cursiva: si no, `**x**` se leería como dos cursivas
        // pegadas y saldría `<em><em>x</em></em>`.
        $salida = preg_replace('/(\*\*|__)(?=\S)(.+?\S)\1/u', '<strong>$2</strong>', $salida);
        $salida = preg_replace('/(?<![\w*])(\*|_)(?=\S)(.+?\S)\1(?![\w*])/u', '<em>$2</em>', $salida);
        $salida = preg_replace('/~~(?=\S)(.+?\S)~~/u', '<s>$1</s>', $salida);

        // Y de vuelta lo apartado.
        return preg_replace_callback(
            '/' . self::ABRE . '(\d+)' . self::CIERRA . '/u',
            fn ($c) => $apartados[(int) $c[1]] ?? '',
            $salida
        );
    }

    /** El mismo criterio que `HtmlSanitizer`: sin `data:` y sin `javascript:`. */
    private static function origenDeImagenAceptable(string $origen): bool
    {
        $limpio = strtolower(preg_replace('/[\s\x00-\x20]+/', '', $origen));

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
        $limpio = strtolower(preg_replace('/[\s\x00-\x20]+/', '', $destino));

        if ($limpio === '') {
            return false;
        }

        if (str_starts_with($limpio, '#') || str_starts_with($limpio, '/') || str_starts_with($limpio, './') || str_starts_with($limpio, '../')) {
            return true;
        }

        foreach (self::ESQUEMAS as $esquema) {
            if (str_starts_with($limpio, $esquema)) {
                return true;
            }
        }

        return ! str_contains($limpio, ':');
    }
}
