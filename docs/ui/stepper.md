# Stepper

Indicador de pasos para flujos multi-etapa como wizards, formularios y procesos. Usa Alpine.js para la interactividad.

## Uso básico

```blade
<x-kore::stepper selected="datos">
    <x-kore::stepper.item id="datos" label="Datos personales">
        <form>Formulario de datos...</form>
    </x-kore::stepper.item>

    <x-kore::stepper.item id="direccion" label="Dirección">
        <form>Formulario de dirección...</form>
    </x-kore::stepper.item>

    <x-kore::stepper.item id="confirmar" label="Confirmación">
        Resumen final.
    </x-kore::stepper.item>
</x-kore::stepper>
```

## Props del contenedor

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `selected` | `string\|null` | `null` | ID del paso activo inicial |

Sin `selected`, el paso activo es **el primero**. Los que van detrás quedan como `pending` y los que quedan atrás, como `complete`.
| `linear` | `bool` | `false` | Fuerza navegación secuencial (no permite saltar pasos) |
| `variant` | `string` | `horizontal` | Variante visual: `horizontal`, `vertical`, `compact` |

## Props de item

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `id` | `string` | requerido | Identificador único del paso |
| `label` | `string` | requerido | Título del paso |
| `description` | `string\|null` | `null` | Descripción breve bajo el título |
| `icon` | `string\|null` | `null` | Icono Lucide personalizado para el paso |
| `status` | `string` | `pending` | Estado: `pending`, `active`, `complete`, `error` |
| `disabled` | `bool` | `false` | Desactiva la interacción con el paso |

## Variantes

```blade
{{-- Horizontal (por defecto) --}}
<x-kore::stepper variant="horizontal" selected="paso1">
    <x-kore::stepper.item id="paso1" label="Paso 1">Contenido</x-kore::stepper.item>
    <x-kore::stepper.item id="paso2" label="Paso 2">Contenido</x-kore::stepper.item>
    <x-kore::stepper.item id="paso3" label="Paso 3">Contenido</x-kore::stepper.item>
</x-kore::stepper>

{{-- Vertical --}}
<x-kore::stepper variant="vertical" selected="paso1">
    <x-kore::stepper.item id="paso1" label="Paso 1" description="Primera etapa">
        Contenido del paso 1.
    </x-kore::stepper.item>

    <x-kore::stepper.item id="paso2" label="Paso 2" description="Segunda etapa">
        Contenido del paso 2.
    </x-kore::stepper.item>
</x-kore::stepper>

{{-- Compacto (solo indicadores sin contenido expandido) --}}
<x-kore::stepper variant="compact" selected="paso2">
    <x-kore::stepper.item id="paso1" label="Datos" />
    <x-kore::stepper.item id="paso2" label="Pago" />
    <x-kore::stepper.item id="paso3" label="Confirmar" />
</x-kore::stepper>
```

## Con descripciones e iconos

```blade
<x-kore::stepper selected="cuenta">
    <x-kore::stepper.item id="cuenta" label="Cuenta" description="Crear tu cuenta" icon="user">
        Formulario de registro.
    </x-kore::stepper.item>

    <x-kore::stepper.item id="pago" label="Pago" description="Método de pago" icon="credit-card">
        Formulario de pago.
    </x-kore::stepper.item>

    <x-kore::stepper.item id="envio" label="Envío" description="Dirección de entrega" icon="truck">
        Formulario de envío.
    </x-kore::stepper.item>
</x-kore::stepper>
```

## Estados

```blade
<x-kore::stepper selected="pago">
    <x-kore::stepper.item id="datos" label="Datos" status="complete">
        Completado.
    </x-kore::stepper.item>

    <x-kore::stepper.item id="pago" label="Pago" status="active">
        En progreso.
    </x-kore::stepper.item>

    <x-kore::stepper.item id="envio" label="Envío" status="error">
        Error en la validación.
    </x-kore::stepper.item>

    <x-kore::stepper.item id="confirmar" label="Confirmar" status="pending" disabled>
        Pendiente.
    </x-kore::stepper.item>
</x-kore::stepper>
```

## Navegación lineal

```blade
{{-- No permite saltar pasos: solo avanzar al siguiente o retroceder al anterior --}}
<x-kore::stepper linear selected="paso1">
    <x-kore::stepper.item id="paso1" label="Paso 1">Contenido</x-kore::stepper.item>
    <x-kore::stepper.item id="paso2" label="Paso 2">Contenido</x-kore::stepper.item>
    <x-kore::stepper.item id="paso3" label="Paso 3">Contenido</x-kore::stepper.item>
</x-kore::stepper>
```

## Slot de navegación

El slot `navigation` pertenece al contenedor `<x-kore::stepper>`, no a los items individuales:

```blade
<x-kore::stepper selected="paso1">
    <x-kore::stepper.item id="paso1" label="Paso 1">
        <p>Contenido del paso 1.</p>
    </x-kore::stepper.item>

    <x-kore::stepper.item id="paso2" label="Paso 2">
        <p>Contenido del paso 2.</p>
    </x-kore::stepper.item>

    <x-slot:navigation>
        <div class="flex justify-between">
            <x-kore::button label="Anterior" variant="outline"
                x-on:click="previous()" x-bind:disabled="isFirst()" />
            <x-kore::button label="Siguiente"
                x-on:click="next()" x-bind:disabled="isLast()" />
        </div>
    </x-slot:navigation>
</x-kore::stepper>
```

## Integración con Livewire

```blade
<x-kore::stepper wire:model="currentStep">
    <x-kore::stepper.item id="paso1" label="Paso 1">
        El estado se sincroniza con la propiedad $currentStep del componente Livewire.
    </x-kore::stepper.item>
</x-kore::stepper>
```

## Plugin Alpine

El componente usa el plugin `KoreStepper` que se registra automáticamente. Expone los siguientes métodos:

| Método | Descripción |
|--------|-------------|
| `next()` | Avanza al siguiente paso |
| `previous()` | Retrocede al paso anterior |
| `isFirst()` | Retorna `true` si está en el primer paso |
| `isLast()` | Retorna `true` si está en el último paso |

También gestiona los atributos ARIA de accesibilidad y las transiciones entre pasos.
