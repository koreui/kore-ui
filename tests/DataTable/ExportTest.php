<?php

use Illuminate\Support\Facades\Schema;
use KoreUi\Tests\DataTable\Fixtures\TestExportTable;
use KoreUi\Tests\DataTable\Fixtures\TestUser;
use Livewire\Livewire;

beforeEach(function () {
    Schema::create('test_users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('city')->nullable();
        $table->boolean('is_active')->default(true);
        $table->integer('age')->nullable();
    });

    TestUser::insert([
        ['name' => 'Alice', 'email' => 'alice@test.com', 'city' => 'Madrid', 'is_active' => true, 'age' => 30],
        ['name' => 'Bob', 'email' => 'bob@test.com', 'city' => 'Barcelona', 'is_active' => false, 'age' => 25],
        ['name' => 'Charlie', 'email' => 'charlie@test.com', 'city' => 'Madrid', 'is_active' => true, 'age' => 45],
    ]);
});

afterEach(function () {
    Schema::dropIfExists('test_users');
});

it('export is enabled via configure', function () {
    $component = Livewire::test(TestExportTable::class);

    expect($component->instance()->isExportEnabled())->toBeTrue();
});

it('export returns a streamed response', function () {
    $component = Livewire::test(TestExportTable::class);
    $response = $component->instance()->exportAs('csv');

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('Content-Type'))->toContain('text/csv');
});

it('export excludes ActionColumn', function () {
    $component = Livewire::test(TestExportTable::class);
    $exportColumns = (new \ReflectionMethod($component->instance(), 'getExportColumns'))
        ->invoke($component->instance());

    $types = array_map(fn ($col) => $col->getType(), $exportColumns);

    expect($types)->not->toContain('action');
});

it('export has correct number of data columns', function () {
    $component = Livewire::test(TestExportTable::class);
    $exportColumns = (new \ReflectionMethod($component->instance(), 'getExportColumns'))
        ->invoke($component->instance());

    // ID, Nombre, Email, Activo (ActionColumn excluded)
    expect($exportColumns)->toHaveCount(4);
});

it('renders export button in toolbar', function () {
    Livewire::test(TestExportTable::class)
        ->assertSee('Exportar');
});

it('supports setting export file name', function () {
    $component = Livewire::test(TestExportTable::class);
    $component->instance()->setExportFileName('usuarios.csv');

    $response = $component->instance()->exportAs('csv');
    $disposition = $response->headers->get('Content-Disposition');

    expect($disposition)->toContain('usuarios.csv');
});

it('supports setting max rows', function () {
    $component = Livewire::test(TestExportTable::class);
    $component->instance()->setExportMaxRows(1);

    // Should limit to 1 row (no error means it worked)
    $response = $component->instance()->exportAs('csv');
    expect($response->getStatusCode())->toBe(200);
});

// S3 — exportAs() is a public Livewire method callable from the browser. The UI
// only hides the export button when disabled; the method itself must refuse to
// run (the button being hidden is not an authorization check).
it('blocks exportAs when export is disabled (server-side guard)', function () {
    $component = Livewire::test(TestExportTable::class);
    $component->instance()->setExportEnabled(false);

    try {
        $component->instance()->exportAs('csv');
        $this->fail('exportAs should be blocked when export is disabled');
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        expect($e->getStatusCode())->toBe(403);
    }
});

it('rejects an unsupported export format instead of silently falling back to csv', function () {
    $component = Livewire::test(TestExportTable::class);

    try {
        $component->instance()->exportAs('pdf');
        $this->fail('exportAs should reject formats outside getExportFormats()');
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        expect($e->getStatusCode())->toBe(404);
    }
});
