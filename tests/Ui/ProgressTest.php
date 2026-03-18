<?php

it('renders progress bar', function () {
    $view = $this->blade('<x-kore::progress :value="50" />');
    $view->assertSee('role="progressbar"', false);
});

it('renders with value and percentage', function () {
    $view = $this->blade('<x-kore::progress :value="75" />');
    $view->assertSee('aria-valuenow="75"', false);
});

it('renders with label', function () {
    $view = $this->blade('<x-kore::progress :value="50" label="Upload" />');
    $view->assertSee('Upload');
});

it('renders showValue displays percentage', function () {
    $view = $this->blade('<x-kore::progress :value="65" :showValue="true" />');
    $view->assertSee('65%');
});

it('renders sm size', function () {
    $view = $this->blade('<x-kore::progress :value="50" size="sm" />');
    $view->assertSee('h-1.5', false);
});

it('renders lg size', function () {
    $view = $this->blade('<x-kore::progress :value="50" size="lg" />');
    $view->assertSee('h-4', false);
});

it('renders success color', function () {
    $view = $this->blade('<x-kore::progress :value="50" color="success" />');
    $view->assertSee('bg-kore-success', false);
});

it('renders auto color with high value', function () {
    $view = $this->blade('<x-kore::progress :value="80" color="auto" />');
    $view->assertSee('bg-kore-success', false);
});

it('renders auto color with low value', function () {
    $view = $this->blade('<x-kore::progress :value="20" color="auto" />');
    $view->assertSee('bg-kore-destructive', false);
});

it('renders indeterminate', function () {
    $view = $this->blade('<x-kore::progress :indeterminate="true" />');
    $view->assertSee('kore-progress-indeterminate', false);
});

it('renders striped', function () {
    $view = $this->blade('<x-kore::progress :value="50" :striped="true" />');
    $view->assertSee('kore-progress-striped', false);
});

it('renders animated', function () {
    $view = $this->blade('<x-kore::progress :value="50" :animated="true" />');
    $view->assertSee('kore-progress-animated', false);
});

it('renders circle variant', function () {
    $view = $this->blade('<x-kore::progress.circle :value="50" />');
    $view->assertSee('viewBox', false);
});

it('renders circle with showValue', function () {
    $view = $this->blade('<x-kore::progress.circle :value="72" :showValue="true" />');
    $view->assertSee('72%');
});
