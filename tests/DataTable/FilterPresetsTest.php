<?php

use Illuminate\Support\Facades\Schema;
use KoreUi\DataTable\Presets\FilterPreset;
use KoreUi\Tests\DataTable\Fixtures\TestDefaultPresetTable;
use KoreUi\Tests\DataTable\Fixtures\TestPresetsTable;
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
        ['name' => 'Diana', 'email' => 'diana@test.com', 'city' => 'Sevilla', 'is_active' => true, 'age' => 35],
        ['name' => 'Fiona', 'email' => 'fiona@test.com', 'city' => 'Barcelona', 'is_active' => false, 'age' => 28],
    ]);
});

afterEach(function () {
    Schema::dropIfExists('test_users');
});

it('creates a filter preset with make', function () {
    $preset = FilterPreset::make('active', 'Activos')
        ->icon('check')
        ->filters(['is_active' => '1']);

    expect($preset->getIdentifier())->toBe('active')
        ->and($preset->getLabel())->toBe('Activos')
        ->and($preset->getIcon())->toBe('check')
        ->and($preset->hasFilters())->toBeTrue()
        ->and($preset->getFilters())->toBe(['is_active' => '1']);
});

it('supports all fluent methods', function () {
    $preset = FilterPreset::make('test', 'Test')
        ->filters(['a' => '1'])
        ->sorts(['name' => 'asc'])
        ->search('hello')
        ->perPage(50)
        ->count(fn () => 10)
        ->default()
        ->hidden();

    expect($preset->hasFilters())->toBeTrue()
        ->and($preset->hasSorts())->toBeTrue()
        ->and($preset->hasSearch())->toBeTrue()
        ->and($preset->hasPerPage())->toBeTrue()
        ->and($preset->hasCount())->toBeTrue()
        ->and($preset->isDefault())->toBeTrue()
        ->and($preset->isHidden())->toBeTrue()
        ->and($preset->getSearch())->toBe('hello')
        ->and($preset->getPerPage())->toBe(50);
});

it('applies preset and filters data', function () {
    Livewire::test(TestPresetsTable::class)
        ->call('applyPreset', 'active')
        ->assertSet('activePreset', 'active')
        ->assertSet('filters.is_active', '1')
        ->assertSee('Alice')
        ->assertSee('Charlie')
        ->assertDontSee('Bob')
        ->assertDontSee('Fiona');
});

it('applies preset with sorts', function () {
    $component = Livewire::test(TestPresetsTable::class)
        ->call('applyPreset', 'madrid');

    expect($component->get('filters.city'))->toBe('Madrid')
        ->and($component->get('sorts.name'))->toBe('asc')
        ->and($component->get('activePreset'))->toBe('madrid');
});

it('clears preset and resets state', function () {
    Livewire::test(TestPresetsTable::class)
        ->call('applyPreset', 'active')
        ->call('clearPreset')
        ->assertSet('activePreset', null)
        ->assertSet('filters', [])
        ->assertSet('sorts', [])
        ->assertSet('search', '')
        ->assertSee('Alice')
        ->assertSee('Bob');
});

it('deactivates preset on manual filter change', function () {
    Livewire::test(TestPresetsTable::class)
        ->call('applyPreset', 'active')
        ->assertSet('activePreset', 'active')
        ->set('filters.city', 'Madrid')
        ->assertSet('activePreset', null);
});

it('deactivates preset on manual search change', function () {
    Livewire::test(TestPresetsTable::class)
        ->call('applyPreset', 'active')
        ->assertSet('activePreset', 'active')
        ->set('search', 'Alice')
        ->assertSet('activePreset', null);
});

it('resolves visible presets (hides hidden ones)', function () {
    $component = Livewire::test(TestPresetsTable::class);
    $presets = $component->instance()->resolveFilterPresets();

    expect($presets)->toHaveCount(3);

    $identifiers = array_map(fn ($p) => $p->getIdentifier(), $presets);
    expect($identifiers)->not->toContain('hidden_preset');
});

it('computes preset counts', function () {
    $component = Livewire::test(TestPresetsTable::class);
    $counts = $component->instance()->getPresetCounts();

    expect($counts)->toHaveKey('active')
        ->and($counts['active'])->toBe(3);
});

it('finds a preset by identifier', function () {
    $component = Livewire::test(TestPresetsTable::class);
    $preset = $component->instance()->findPreset('active');

    expect($preset)->not->toBeNull()
        ->and($preset->getLabel())->toBe('Activos');
});

it('returns null for non-existent preset', function () {
    $component = Livewire::test(TestPresetsTable::class);

    expect($component->instance()->findPreset('nonexistent'))->toBeNull();
});

it('renders preset tabs in view', function () {
    Livewire::test(TestPresetsTable::class)
        ->assertSee('Activos')
        ->assertSee('Inactivos')
        ->assertSee('Madrid')
        ->assertDontSee('Oculto');
});

it('a default preset keeps the ?page from the URL on mount', function () {
    // Regression: applying the default preset used to call resetPage() during
    // mount(), which pre-initialized the paginator and disabled the ?page URL
    // binding. Need > 1 page at the default perPage (25) so page 2 is valid.
    TestUser::insert(collect(range(1, 30))->map(fn ($i) => [
        'name'      => "User {$i}",
        'email'     => "u{$i}@test.com",
        'city'      => 'Madrid',
        'is_active' => true,
        'age'       => 20,
    ])->all());

    $component = Livewire::withQueryParams(['page' => 2])
        ->test(TestDefaultPresetTable::class);

    expect($component->instance()->activePreset)->toBe('all')
        ->and($component->instance()->getPage())->toBe(2);
});
