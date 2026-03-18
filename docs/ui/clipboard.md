# Clipboard

Componente para copiar texto al portapapeles con feedback visual.

## Uso básico

```blade
<x-kore::clipboard text="npm install kore-ui" />
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `text` | `string` | `null` | Texto a copiar |
| `variant` | `string` | `input` | Variante: `input`, `inline`, `icon` |
| `label` | `string\|null` | `null` | Etiqueta (solo variante `input`) |
| `secret` | `bool` | `false` | Oculta el texto (password/dots) |
| `feedbackDuration` | `int` | `2000` | Duración del feedback en ms |

## Variantes

### Input (default)

```blade
<x-kore::clipboard text="sk_live_abc123" label="API Key" />
<x-kore::clipboard text="super-secret" label="Token" :secret="true" />
```

### Inline

```blade
<x-kore::clipboard text="192.168.1.100" variant="inline" />
```

### Icon only

```blade
<span>git clone https://github.com/example/repo.git</span>
<x-kore::clipboard text="git clone https://github.com/example/repo.git" variant="icon" />
```

## Modo secreto

```blade
{{-- Input: muestra type="password" --}}
<x-kore::clipboard text="secret" :secret="true" />

{{-- Inline: muestra dots --}}
<x-kore::clipboard text="secret" variant="inline" :secret="true" />
```

## Eventos

| Evento | Datos | Descripción |
|--------|-------|-------------|
| `clipboard-copied` | `{ text }` | Disparado al copiar exitosamente |

## Configuración

En `config/kore-ui.php`:

```php
'clipboard' => [
    'variant' => 'input',
],
```
