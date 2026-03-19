<?php

namespace KoreUi\DataTable\Support;

class UrlSanitizer
{
    private const ALLOWED_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    /**
     * Valida que la URL use un protocolo seguro.
     * Rutas relativas (/path, ?query, #anchor) siempre se permiten.
     * Devuelve null para protocolos peligrosos (javascript:, data:, vbscript:…).
     */
    public static function sanitize(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return $url;
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
