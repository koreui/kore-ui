# Sidebar Group

Agrupa items bajo un título de sección. A diferencia de un item con hijos, un grupo **no navega**: solo organiza.

## Uso básico

```blade
<x-kore::sidebar.group label="Gestión">
    <x-kore::sidebar.item label="Usuarios" icon="users" route="users.index" />
    <x-kore::sidebar.item label="Roles" icon="shield" route="roles.index" />
</x-kore::sidebar.group>
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string` | `null` | Título de la sección |
| `icon` | `string` | `null` | Icono de Lucide junto al título |
| `collapsible` | `bool` | `false` | Permitir plegar la sección entera |
| `collapsed` | `bool` | `false` | Empezar plegada |
| `separator` | `'line'\|'space'\|'none'` | `'line'` | Separador visual antes del grupo |
| `header` | `string` | `null` | Título personalizado (sustituye a `label` + `icon`) |

## Grupo o item con hijos

Se parecen, pero no son lo mismo:

| | `sidebar.group` | `sidebar.item` con hijos |
|---|---|---|
| **Qué es** | Una sección | Un menú desplegable |
| **¿Navega?** | No | Sí, puede tener su propio destino |
| **Aspecto** | Título pequeño en mayúsculas | Un item normal, con icono y flecha |
| **Al colapsar** | El título desaparece; sus items siguen ahí como iconos | El item sigue ahí; los hijos salen en un panel flotante |

En resumen: usa un **grupo** para etiquetar un bloque de navegación, y un **item con hijos** cuando el padre sea en sí mismo una opción del menú.

## Grupo plegable

```blade
<x-kore::sidebar.group label="Informes" icon="bar-chart-2" :collapsible="true" :collapsed="true">
    <x-kore::sidebar.item label="Ventas" route="reports.sales" />
    <x-kore::sidebar.item label="Métricas" route="reports.metrics" />
</x-kore::sidebar.group>
```

Aunque lo declares `collapsed`, **si contiene la página actual se abre solo** — y lo hace ya en el HTML del servidor, no al arrancar Alpine.

## Al colapsar el sidebar

El título de sección **se encoge hasta desaparecer**, con animación. Sus items siguen visibles como iconos; lo único que se va es el rótulo, que ya no cabe. Queda el separador para que las secciones se sigan distinguiendo.

No se oculta de golpe a propósito: hacerlo así haría que todo lo de abajo pegara un salto hacia arriba.

## Accesibilidad

Un grupo plegable es un `<button>` con `aria-expanded` y `aria-controls`. Una vez plegado —o con el sidebar en modo iconos— su contenido **sale del orden de tabulación**, para que nadie acabe con el foco en un elemento invisible.
