<?php

namespace KoreUi\DataTable\Support;

class UrlSanitizer
{
    private const ALLOWED_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    /**
     * Valida que la URL use un protocolo seguro.
     * Rutas relativas (/path, ?query, #anchor) siempre se permiten.
     * Devuelve null para protocolos peligrosos (javascript:, data:, vbscript:…),
     * URLs scheme-relative (//host → open-redirect) y esquemas colados con
     * caracteres de control (p.ej. "java\tscript:").
     */
    public static function sanitize(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return $url;
        }

        // Quita espacios alrededor y caracteres de control embebidos (tabs,
        // saltos de línea, NUL) que el navegador ignora pero que sirven para
        // disfrazar un esquema peligroso: "java\tscript:alert(1)" → "javascript:".
        $url = preg_replace('/[\x00-\x20]+/', '', trim($url));

        if ($url === '' || $url === null) {
            return null;
        }

        // Scheme-relative (//evil.com): hereda el esquema de la página y apunta a
        // un host arbitrario → open-redirect. Rechazar antes del atajo de rutas.
        if (str_starts_with($url, '//')) {
            return null;
        }

        // Rutas relativas: permitidas sin validación de scheme
        if (str_starts_with($url, '/') || str_starts_with($url, '?') || str_starts_with($url, '#')) {
            return $url;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (! in_array($scheme, self::ALLOWED_SCHEMES, strict: true)) {
            return null;
        }

        return $url;
    }
}
