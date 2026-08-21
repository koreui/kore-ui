<?php

it('renders the container', function () {
    $view = $this->blade('<x-kore::carousel><x-kore::carousel.slide>Slide 1</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertSee('KoreCarousel', false);
});

it('renders role region', function () {
    $view = $this->blade('<x-kore::carousel><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertSee('role="region"', false);
});

/**
 * `aria-roledescription` NO es un rol: es texto que el lector PRONUNCIA, así que
 * va en el idioma del contenido. Decía «carousel» y «slide», y eso es lo que se
 * oía en una interfaz en español.
 */
it('describe su rol en el idioma de la interfaz', function () {
    $view = $this->blade('<x-kore::carousel><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertSee('aria-roledescription="carrusel"', false)
        ->assertSee('aria-roledescription="diapositiva"', false);
});

it('renders navigation buttons by default', function () {
    $view = $this->blade('<x-kore::carousel><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertSee('Diapositiva anterior', false)
        ->assertSee('Diapositiva siguiente', false);
});

it('hides navigation when disabled', function () {
    $view = $this->blade('<x-kore::carousel :showNavigation="false"><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertDontSee('Diapositiva anterior', false);
});

it('renders indicators by default', function () {
    $view = $this->blade('<x-kore::carousel><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertSee('goTo((i - 1) * numVisible)', false);
});

it('hides indicators when disabled', function () {
    $view = $this->blade('<x-kore::carousel :showIndicators="false"><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertDontSee('goTo((i - 1) * numVisible)', false);
});

/**
 * Los indicadores decían ser pestañas —`role="tablist"` con sus `role="tab"`—
 * sin que hubiera un solo `role="tabpanel"` al otro lado. Medido: cuatro `tab` y
 * cero paneles. Y con `numVisible` mayor que uno cada punto lleva a un GRUPO de
 * diapositivas, así que la relación uno a uno que un tablist promete no puede
 * existir. Son botones con `aria-current`, que es lo que de verdad son.
 */
it('no finge ser una lista de pestañas', function () {
    $view = $this->blade('<x-kore::carousel><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertDontSee('role="tablist"', false)
        ->assertDontSee('role="tab"', false)
        ->assertSee('aria-current', false);
});

/** Un `role="region"` sin nombre no se anuncia como región. */
it('nombra la región', function () {
    $view = $this->blade('<x-kore::carousel><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertSee('aria-label="Carrusel"', false);
});

it('admite un nombre propio', function () {
    $view = $this->blade('<x-kore::carousel ariaLabel="Ofertas"><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertSee('aria-label="Ofertas"', false);
});

/** Cada diapositiva se anuncia como tal; la posición la pone el JavaScript. */
it('marca cada diapositiva como slide', function () {
    $view = $this->blade('<x-kore::carousel><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertSee('aria-roledescription="diapositiva"', false)
        ->assertSee('role="group"', false);
});

/**
 * WCAG 2.2.2: lo que se mueve solo más de cinco segundos necesita una forma de
 * pararlo. `pauseOnHover` solo sirve con ratón.
 */
it('ofrece parar el autoplay', function () {
    $view = $this->blade('<x-kore::carousel :autoplay="true"><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertSee('toggleParado()', false)
        ->assertSee('Parar el carrusel', false);
});

it('no pinta el botón de pausa sin autoplay', function () {
    $view = $this->blade('<x-kore::carousel><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertDontSee('toggleParado()', false);
});

/** Con autoplay, el foco dentro también para: tabular no debe mover el suelo. */
it('para el autoplay cuando el foco entra', function () {
    $view = $this->blade('<x-kore::carousel :autoplay="true"><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertSee('x-on:focusin="pause()"', false)
        ->assertSee('x-on:focusout="resume()"', false);
});

/**
 * El `.prevent` del atributo se tragaba el `pointerdown` de TODO lo que hubiera
 * dentro de una diapositiva, foco incluido: medido, al pulsar un botón dentro de
 * un slide el foco se quedaba en `<body>`. Ahora lo decide el JavaScript, que
 * sabe si el gesto empezó sobre un control.
 */
it('no previene el pointerdown desde el atributo', function () {
    $view = $this->blade('<x-kore::carousel><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertDontSee('pointerdown.prevent', false)
        ->assertSee('x-on:pointerdown="onPointerDown($event)"', false);
});

it('se mueve con las flechas', function () {
    $view = $this->blade('<x-kore::carousel><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertSee('x-on:keydown="onKeydown($event)"', false);
});

it('renders slide content', function () {
    $view = $this->blade('<x-kore::carousel><x-kore::carousel.slide>My Slide</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertSee('My Slide');
});

it('renders slide data attribute', function () {
    $view = $this->blade('<x-kore::carousel><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertSee('data-carousel-slide', false);
});

it('passes autoplay config', function () {
    $view = $this->blade('<x-kore::carousel :autoplay="true"><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertSee('autoplay: true', false);
});

it('passes interval config', function () {
    $view = $this->blade('<x-kore::carousel :interval="3000"><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertSee('interval: 3000', false);
});

it('passes loop config', function () {
    $view = $this->blade('<x-kore::carousel :loop="false"><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertSee('loop: false', false);
});

it('passes numVisible config', function () {
    $view = $this->blade('<x-kore::carousel :numVisible="3"><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertSee('numVisible: 3', false);
});

it('passes gap config', function () {
    $view = $this->blade('<x-kore::carousel :gap="24"><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertSee('gap: 24', false);
});

it('renders header slot', function () {
    $view = $this->blade('<x-kore::carousel><x-slot:header>My Header</x-slot:header><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertSee('My Header');
});

it('renders footer slot', function () {
    $view = $this->blade('<x-kore::carousel><x-slot:footer>My Footer</x-slot:footer><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertSee('My Footer');
});
