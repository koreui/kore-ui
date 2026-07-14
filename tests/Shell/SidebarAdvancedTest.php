<?php

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/dashboard', fn () => '')->name('dashboard');
    Route::getRoutes()->refreshNameLookups();
});

// --- Rail / expand on hover ---

it('marks rail and expand-on-hover with the same attribute the CSS keys off', function () {
    // Los dos modos hacen lo mismo visualmente (ensancharse al pasar el cursor); lo que
    // los distingue es de dónde sale el estado de reposo, y eso ya está resuelto.
    $this->blade('<x-kore::sidebar :rail="true">x</x-kore::sidebar>')
        ->assertSee('data-hover-expand="true"', false);

    $this->blade('<x-kore::sidebar :expand-on-hover="true">x</x-kore::sidebar>')
        ->assertSee('data-hover-expand="true"', false);

    $this->blade('<x-kore::sidebar>x</x-kore::sidebar>')
        ->assertDontSee('data-hover-expand', false);
});

// --- Flyout y tooltip ---

it('wires the hover handlers on the item, not on the link', function () {
    // El panel del flyout es descendiente del <li>: escuchando ahí, mover el cursor del
    // icono al panel no dispara mouseleave y el flyout no se cierra en la cara.
    $view = $this->blade('<x-kore::sidebar.item label="Inicio" href="/x" />');

    $view->assertSee('x-on:mouseenter="onItemEnter($event)"', false)
        ->assertSee('x-on:mouseleave="onItemLeave($event)"', false);
});

it('reacts to keyboard focus too, not only to the mouse', function () {
    // Sin esto, quien navega con Tab por un sidebar colapsado ve iconos sin etiqueta.
    $view = $this->blade('<x-kore::sidebar.item label="Inicio" href="/x" />');

    $view->assertSee('x-on:focusin="onItemEnter($event)"', false)
        ->assertSee('x-on:focusout="onItemLeave($event)"', false);
});

it('renders one shared tooltip per sidebar', function () {
    $view = $this->blade('<x-kore::sidebar>x</x-kore::sidebar>');

    $html = $view->__toString();

    expect(substr_count($html, 'kore-sidebar-tooltip'))->toBe(1)
        ->and($html)->toContain('x-ref="tooltip"')
        ->and($html)->toContain('role="tooltip"');
});

// --- Teclado ---

it('handles keyboard navigation at the sidebar level', function () {
    $view = $this->blade('<x-kore::sidebar>x</x-kore::sidebar>');

    $view->assertSee('x-on:keydown="onKeydown($event)"', false);
});

// --- Focus trap ---

it('traps focus ONLY while the mobile drawer is open', function () {
    // En escritorio el sidebar es parte de la página: atrapar el foco allí sería un fallo
    // de accesibilidad, no una mejora. Por eso la condición lleva el isMobile().
    $view = $this->blade('<x-kore::sidebar>x</x-kore::sidebar>');

    $view->assertSee('x-trap=', false)
        ->assertSee('isMobile', false)
        ->assertSee('isOpen', false);
});

it('does not use the noscroll modifier, because the scroll lock is shared', function () {
    // x-trap.noscroll bloquearía el body por su cuenta y se pelearía con utils/scroll-lock.js,
    // que es quien lleva la cuenta de dueños del lock.
    $view = $this->blade('<x-kore::sidebar>x</x-kore::sidebar>');

    $view->assertDontSee('x-trap.noscroll', false);
});

// --- Marca compacta y cierre del drawer ---

it('renders a close button for the mobile drawer', function () {
    // El CSS solo lo enciende por debajo del breakpoint: en escritorio el sidebar es parte
    // de la página y no hay nada que cerrar.
    $view = $this->blade('<x-kore::sidebar>x</x-kore::sidebar>');

    $view->assertSee('kore-sidebar-close', false)
        ->assertSee('aria-label="Cerrar navegación"', false)
        ->assertSee('closeMobile', false);
});

// --- Badges ---

it('anchors the dot badge to the icon, not to the item', function () {
    // Colgado del item quedaba descentrado; sobre el icono funciona igual con el sidebar
    // ancho y reducido a iconos.
    $view = $this->blade('<x-kore::sidebar.item label="Mensajes" icon="mail" href="/m" badge="9" badge-variant="dot" badge-color="destructive" />');

    $view->assertSee('kore-sidebar-dot', false)
        ->assertSee('bg-kore-destructive', false)
        ->assertSee('sr-only', false);   // el punto tiene su texto para lectores de pantalla
});

// --- El badge se encoge a un contador al colapsar ---

it('turns a numeric badge into a counter on the icon corner', function () {
    $view = $this->blade('<x-kore::sidebar.item label="Usuarios" icon="users" href="/u" badge="12" />');

    // La píldora del final de la fila (solo con el sidebar ancho)...
    $view->assertSee('kore-sidebar-label shrink-0 rounded-full', false)
        // ...y el contador en la esquina del icono (solo en modo iconos).
        ->assertSee('kore-sidebar-count', false)
        ->assertSee('>12</span>', false);
});

it('caps an absurd count instead of blowing the icon apart', function () {
    // Con 100000000 el contador reventaría el icono, y de todas formas nadie lee nueve
    // dígitos a 10px. Se acorta SOLO ahí: la píldora del sidebar ancho y el texto para
    // lectores de pantalla siguen dando el número real.
    $html = $this->blade('<x-kore::sidebar.item label="Alertas" icon="bell" href="/a" badge="100000000" />')->__toString();

    expect($html)->toContain('kore-sidebar-count')
        ->and($html)->toContain('>99+</span>')                              // el de la esquina
        ->and($html)->toContain('<span class="sr-only">100000000</span>');  // el real, íntegro
});

it('honours a custom cap', function () {
    $this->blade('<x-kore::sidebar.item label="X" icon="bell" href="/a" badge="150" :badge-max="999" />')
        ->assertSee('>150</span>', false);

    $this->blade('<x-kore::sidebar.item label="X" icon="bell" href="/a" badge="150" :badge-max="9" />')
        ->assertSee('>9+</span>', false);
});

it('lets a short text badge through as-is', function () {
    $this->blade('<x-kore::sidebar.item label="2FA" icon="lock" href="/a" badge="!" />')
        ->assertSee('kore-sidebar-count', false)
        ->assertSee('>!</span>', false);
});

it('degrades a badge that will not fit to an empty dot', function () {
    // "9 sin leer" no cabe en la esquina de un icono ni acortándolo. El contador se
    // renderiza vacío y el CSS (:empty) lo convierte en un punto: al menos queda constancia.
    $view = $this->blade('<x-kore::sidebar.item label="Mensajes" icon="mail" href="/m" badge="9 sin leer" />');

    $view->assertSee('kore-sidebar-count', false)
        ->assertSee('aria-hidden="true"></span>', false);
});

// --- Accesibilidad del modo iconos ---

it('keeps an accessible name when the label leaves the DOM', function () {
    // Al colapsar, la etiqueta visible es display:none, así que TAMBIÉN sale del árbol de
    // accesibilidad: sin el duplicado sr-only, el enlace se quedaría sin nombre y un lector
    // de pantalla solo anunciaría "enlace".
    $view = $this->blade('<x-kore::sidebar.item label="Usuarios" icon="users" href="/u" badge="12" />');

    $html = $view->__toString();

    expect($html)->toContain('<span class="sr-only">Usuarios</span>')
        ->and($html)->toContain('<span class="sr-only">12</span>')
        // Y lo visible se marca aria-hidden para que no se anuncie dos veces.
        ->and($html)->toContain('aria-hidden="true">Usuarios</span>');
});

it('writes badge colour classes in full, so Tailwind can find them', function () {
    // Concatenarlas ("bg-kore-{$color}") las vuelve invisibles para el escáner de Tailwind,
    // que lee el código como texto: la clase solo existiría si otro componente la hubiera
    // generado por casualidad.
    $source = file_get_contents(__DIR__.'/../../resources/views/components/sidebar/item.blade.php');

    expect($source)->not->toContain("'bg-kore-'.\$badgeColor")
        ->and($source)->toContain("'bg-kore-destructive'")
        ->and($source)->toContain("'bg-kore-primary'");
});

it('renders a pill badge with a readable warning tone', function () {
    // --kore-warning es demasiado claro para leerse como texto: existe -text para eso.
    $view = $this->blade('<x-kore::sidebar.item label="2FA" href="/x" badge="!" badge-color="warning" />');

    $view->assertSee('text-kore-warning-text', false);
});

it('drives the row geometry from CSS, so the icon centres when collapsed', function () {
    // El gap y el padding lateral tienen que desaparecer al colapsar: si se quedan como
    // clases fijas de Tailwind, el icono queda empujado contra el borde izquierdo en vez
    // de centrado en la columna. Por eso los lleva `kore-sidebar-link` desde el CSS.
    $view = $this->blade('<x-kore::sidebar.item label="Inicio" icon="home" href="/x" />');

    $view->assertSee('kore-sidebar-link', false)
        ->assertDontSee('items-center gap-3 rounded-kore-md px-3', false);
});
