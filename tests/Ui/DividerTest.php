<?php

it('renders horizontal divider by default', function () {
    $view = $this->blade('<x-kore::divider />');
    $view->assertSee('role="separator"', false);
});

it('renders with label text', function () {
    $view = $this->blade('<x-kore::divider label="Section" />');
    $view->assertSee('Section');
});

it('renders with icon', function () {
    $view = $this->blade('<x-kore::divider icon="star" />');
    $view->assertSee('<svg', false);
});

it('renders vertical orientation', function () {
    $view = $this->blade('<x-kore::divider orientation="vertical" />');
    $view->assertSee('aria-orientation="vertical"', false);
});

it('renders dashed type', function () {
    $view = $this->blade('<x-kore::divider type="dashed" />');
    $view->assertSee('border-dashed', false);
});

it('renders dotted type', function () {
    $view = $this->blade('<x-kore::divider type="dotted" />');
    $view->assertSee('border-dotted', false);
});

it('renders left align', function () {
    $view = $this->blade('<x-kore::divider label="Left" align="left" />');
    $view->assertSee('w-[5%]', false);
});

it('renders right align', function () {
    $view = $this->blade('<x-kore::divider label="Right" align="right" />');
    $view->assertSee('w-[5%]', false);
});
