<?php

it('renders the container', function () {
    $view = $this->blade('<x-kore::carousel><x-kore::carousel.slide>Slide 1</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertSee('KoreCarousel', false);
});

it('renders role region', function () {
    $view = $this->blade('<x-kore::carousel><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertSee('role="region"', false);
});

it('renders aria-roledescription carousel', function () {
    $view = $this->blade('<x-kore::carousel><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertSee('aria-roledescription="carousel"', false);
});

it('renders navigation buttons by default', function () {
    $view = $this->blade('<x-kore::carousel><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertSee('Previous slide', false)
        ->assertSee('Next slide', false);
});

it('hides navigation when disabled', function () {
    $view = $this->blade('<x-kore::carousel :showNavigation="false"><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertDontSee('Previous slide', false);
});

it('renders indicators by default', function () {
    $view = $this->blade('<x-kore::carousel><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertSee('role="tablist"', false);
});

it('hides indicators when disabled', function () {
    $view = $this->blade('<x-kore::carousel :showIndicators="false"><x-kore::carousel.slide>A</x-kore::carousel.slide></x-kore::carousel>');
    $view->assertDontSee('role="tablist"', false);
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
