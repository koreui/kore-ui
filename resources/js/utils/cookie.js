/**
 * Lectura/escritura de cookies desde el cliente.
 *
 * El sidebar persiste en cookie (y no en localStorage) para que el SERVIDOR
 * conozca el estado y pueda emitir el HTML con el ancho correcto en el primer
 * paint. Con localStorage el estado solo se sabe una vez arranca Alpine, que es
 * justo lo que produce el salto de layout que queremos evitar.
 *
 * OJO: Laravel encripta las cookies. Esta se escribe en texto plano desde JS,
 * así que el ServiceProvider la registra en `EncryptCookies::except()`. Sin eso,
 * Laravel no puede desencriptarla y la anula en silencio (la deja a null).
 */

export function readCookie(name, doc = typeof document !== 'undefined' ? document : null) {
    if (!doc) return null;

    const prefix = encodeURIComponent(name) + '=';

    for (const part of doc.cookie.split(';')) {
        const cookie = part.trim();

        if (cookie.startsWith(prefix)) {
            try {
                return decodeURIComponent(cookie.slice(prefix.length));
            } catch {
                return null; // valor mal codificado: se trata como ausente
            }
        }
    }

    return null;
}

export function writeCookie(name, value, opts = {}, doc = typeof document !== 'undefined' ? document : null) {
    if (!doc) return;

    const maxAge = opts.maxAge ?? 31536000; // 1 año
    const path = opts.path ?? '/';
    const sameSite = opts.sameSite ?? 'Lax';

    let cookie = `${encodeURIComponent(name)}=${encodeURIComponent(value)}; path=${path}; max-age=${maxAge}; SameSite=${sameSite}`;

    // Secure solo bajo https: en http lo rechazaría el navegador y no se guardaría nada.
    if (opts.secure ?? (typeof location !== 'undefined' && location.protocol === 'https:')) {
        cookie += '; Secure';
    }

    doc.cookie = cookie;
}
