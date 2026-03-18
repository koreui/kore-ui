# Tab

Sistema de pestañas para organizar contenido en paneles con navegación. Usa Alpine.js para la interactividad.

## Uso básico

```blade
<x-kore::tab selected="general">
    <x-kore::tab.item id="general" label="General">
        Contenido de la pestaña General.
    </x-kore::tab.item>

    <x-kore::tab.item id="seguridad" label="Seguridad">
        Contenido de la pestaña Seguridad.
    </x-kore::tab.item>

    <x-kore::tab.item id="notificaciones" label="Notificaciones">
        Contenido de la pestaña Notificaciones.
    </x-kore::tab.item>
</x-kore::tab>
```

## Props del contenedor

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `selected` | `string\|null` | `null` | ID de la pestaña activa inicial |
| `variant` | `string` | `line` | Variante visual: `line`, `pill`, `enclosed` |
| `orientation` | `string` | `horizontal` | Orientación: `horizontal`, `vertical` |
| `scrollable` | `bool` | `false` | Habilita scroll horizontal cuando las pestañas desbordan |

## Props de item

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `id` | `string` | requerido | Identificador único de la pestaña |
| `label` | `string` | requerido | Texto de la pestaña |
| `icon` | `string\|null` | `null` | Icono Lucide junto al label |
| `badge` | `string\|null` | `null` | Texto de badge junto al label |
| `disabled` | `bool` | `false` | Desactiva la pestaña |
| `lazy` | `bool` | `false` | Renderiza el contenido solo al activar la pestaña |
| `closeable` | `bool` | `false` | Permite cerrar/remover la pestaña |

## Variantes

```blade
{{-- Línea inferior (por defecto) --}}
<x-kore::tab variant="line" selected="tab1">
    <x-kore::tab.item id="tab1" label="Tab 1">Contenido</x-kore::tab.item>
    <x-kore::tab.item id="tab2" label="Tab 2">Contenido</x-kore::tab.item>
</x-kore::tab>

{{-- Píldoras --}}
<x-kore::tab variant="pill" selected="tab1">
    <x-kore::tab.item id="tab1" label="Tab 1">Contenido</x-kore::tab.item>
    <x-kore::tab.item id="tab2" label="Tab 2">Contenido</x-kore::tab.item>
</x-kore::tab>

{{-- Encerrado (tipo card) --}}
<x-kore::tab variant="enclosed" selected="tab1">
    <x-kore::tab.item id="tab1" label="Tab 1">Contenido</x-kore::tab.item>
    <x-kore::tab.item id="tab2" label="Tab 2">Contenido</x-kore::tab.item>
</x-kore::tab>
```

## Con iconos y badges

```blade
<x-kore::tab selected="mensajes">
    <x-kore::tab.item id="mensajes" label="Mensajes" icon="mail" badge="3">
        Lista de mensajes.
    </x-kore::tab.item>

    <x-kore::tab.item id="contactos" label="Contactos" icon="users">
        Lista de contactos.
    </x-kore::tab.item>
</x-kore::tab>
```

## Orientación vertical

```blade
<x-kore::tab orientation="vertical" selected="perfil">
    <x-kore::tab.item id="perfil" label="Perfil" icon="user">
        Datos del perfil.
    </x-kore::tab.item>

    <x-kore::tab.item id="cuenta" label="Cuenta" icon="settings">
        Configuración de la cuenta.
    </x-kore::tab.item>
</x-kore::tab>
```

## Carga lazy

```blade
<x-kore::tab selected="resumen">
    <x-kore::tab.item id="resumen" label="Resumen">
        Se renderiza inmediatamente.
    </x-kore::tab.item>

    <x-kore::tab.item id="detalles" label="Detalles" lazy>
        Se renderiza solo cuando el usuario selecciona esta pestaña.
    </x-kore::tab.item>
</x-kore::tab>
```

## Pestañas cerrables

```blade
<x-kore::tab selected="doc1">
    <x-kore::tab.item id="doc1" label="documento.txt" closeable>
        Contenido del documento.
    </x-kore::tab.item>

    <x-kore::tab.item id="doc2" label="notas.md" closeable>
        Contenido de notas.
    </x-kore::tab.item>
</x-kore::tab>
```

## Scroll horizontal

```blade
{{-- Útil cuando hay muchas pestañas --}}
<x-kore::tab scrollable selected="tab1">
    @for ($i = 1; $i <= 12; $i++)
        <x-kore::tab.item :id="'tab'.$i" :label="'Pestaña '.$i">
            Contenido {{ $i }}
        </x-kore::tab.item>
    @endfor
</x-kore::tab>
```

## Integración con Livewire

```blade
<x-kore::tab wire:model="activeTab">
    <x-kore::tab.item id="general" label="General">
        El estado se sincroniza con la propiedad $activeTab del componente Livewire.
    </x-kore::tab.item>
</x-kore::tab>
```

## Navegación por teclado

El plugin `KoreTab` gestiona automáticamente la navegación por teclado:

- **Flechas izquierda/derecha**: Navegar entre pestañas (horizontal)
- **Flechas arriba/abajo**: Navegar entre pestañas (vertical)
- **Home**: Ir a la primera pestaña
- **End**: Ir a la última pestaña

## Plugin Alpine

El componente usa el plugin `KoreTab` que se registra automáticamente. Gestiona la selección de pestañas, la navegación por teclado, la carga lazy y los atributos ARIA de accesibilidad.
