<?php

namespace KoreUi\Shell;

/**
 * Cómo se entera el shell de qué sidebars tiene dentro.
 *
 * Blade evalúa el contenido de los slots ANTES de renderizar la plantilla del
 * componente que los contiene (compila a `startComponent()` → ob_start() → se
 * bufferiza el slot → `renderComponent()`). Así que cuando corre
 * shell/index.blade.php, todos los `<x-kore::sidebar>` de sus slots ya se han
 * renderizado y registrado aquí con su ancho, breakpoint y estado.
 *
 * El shell los consume y reserva el espacio del `main`, sin tener que inspeccionar
 * el HTML del slot.
 *
 * Bound como `scoped()` (no `singleton()`) para que Octane lo reinicie entre
 * requests y el estado de una petición no se filtre a la siguiente.
 */
final class ShellContext
{
    /** @var array<string, array<string, mixed>> indexado por placement */
    private array $sidebars = [];

    public function register(array $sidebar): void
    {
        $placement = ($sidebar['placement'] ?? 'left') === 'right' ? 'right' : 'left';

        // Un solo sidebar por lado: el último gana.
        $this->sidebars[$placement] = $sidebar;
    }

    /**
     * Lee y limpia. Vaciar al leer evita que dos shells seguidos —o dos
     * `$this->blade()` en el mismo test— se contaminen entre sí.
     *
     * @return array<string, array<string, mixed>>
     */
    public function consume(): array
    {
        $sidebars = $this->sidebars;
        $this->sidebars = [];

        return $sidebars;
    }
}
