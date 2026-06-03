<?php

use Illuminate\Support\Facades\Schema;
use KoreUi\Tests\DataTable\Fixtures\TestBulkTable;
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

it('selection is enabled when bulk actions exist', function () {
    $component = Livewire::test(TestBulkTable::class);

    expect($component->instance()->isSelectionEnabled())->toBeTrue();
});

it('renders checkboxes when selection is enabled', function () {
    Livewire::test(TestBulkTable::class)
        ->assertSeeHtml('type="checkbox"');
});

it('returns row IDs for current page', function () {
    $component = Livewire::test(TestBulkTable::class);
    $rows = $component->instance()->getRows();
    $ids = $component->instance()->getRowIds($rows);

    expect($ids)->toHaveCount(3)
        ->and($ids)->each->toBeString();
});

it('uses custom primary key', function () {
    $component = Livewire::test(TestBulkTable::class);
    $component->instance()->setPrimaryKey('email');

    expect($component->instance()->getPrimaryKey())->toBe('email');
});

it('default primary key is id', function () {
    $component = Livewire::test(TestBulkTable::class);

    expect($component->instance()->getPrimaryKey())->toBe('id');
});

it('can disable selection', function () {
    $component = Livewire::test(TestBulkTable::class);
    $component->instance()->setSelectionEnabled(false);

    expect($component->instance()->isSelectionEnabled())->toBeFalse();
});

it('passes rowIds to view', function () {
    Livewire::test(TestBulkTable::class)
        ->assertSeeHtml('rowIds');
});

it('checkbox column adjusts empty state colspan', function () {
    Schema::dropIfExists('test_users');
    Schema::create('test_users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('city')->nullable();
        $table->boolean('is_active')->default(true);
        $table->integer('age')->nullable();
    });

    Livewire::test(TestBulkTable::class)
        ->assertSeeHtml('colspan="4"');
});

// --- Server-side selection state (Opción C: cross-page persistente) ---

it('toggleRow adds and removes an id', function () {
    Livewire::test(TestBulkTable::class)
        ->call('toggleRow', '1')
        ->assertSet('selected', ['1'])
        ->call('toggleRow', '1')
        ->assertSet('selected', []);
});

it('toggleRow casts ids to string', function () {
    Livewire::test(TestBulkTable::class)
        ->call('toggleRow', 1)
        ->assertSet('selected', ['1']);
});

it('toggleSelectAll selects the whole current page', function () {
    $selected = Livewire::test(TestBulkTable::class)
        ->call('toggleSelectAll')
        ->get('selected');

    sort($selected);
    expect($selected)->toBe(['1', '2', '3']);
});

it('toggleSelectAll on a fully selected page clears it', function () {
    Livewire::test(TestBulkTable::class)
        ->set('selected', ['1', '2', '3'])
        ->call('toggleSelectAll')
        ->assertSet('selected', []);
});

it('selection persists across pagination', function () {
    Livewire::test(TestBulkTable::class)
        ->set('perPage', 2)
        ->call('toggleRow', '1')
        ->call('nextPage')
        ->assertSet('selected', ['1'])
        ->call('previousPage')
        ->assertSet('selected', ['1']);
});

it('selectRange unions a range without duplicates', function () {
    Livewire::test(TestBulkTable::class)
        ->call('selectRange', ['1', '2'])
        ->assertSet('selected', ['1', '2'])
        ->call('selectRange', ['2', '3'])
        ->assertSet('selected', ['1', '2', '3']);
});

it('enableSelectAllMatching sets the flag and marks rows selected', function () {
    $component = Livewire::test(TestBulkTable::class)
        ->call('enableSelectAllMatching')
        ->assertSet('selectAllMatching', true);

    expect($component->instance()->isRowSelected('999'))->toBeTrue();
});

it('toggleRow exits selectAllMatching and materializes the page', function () {
    $component = Livewire::test(TestBulkTable::class)
        ->set('selectAllMatching', true)
        ->call('toggleRow', '1')
        ->assertSet('selectAllMatching', false);

    $selected = $component->get('selected');
    sort($selected);
    expect($selected)->toBe(['2', '3']);
});

it('clearSelection resets selected and matching', function () {
    Livewire::test(TestBulkTable::class)
        ->set('selected', ['1', '2'])
        ->set('selectAllMatching', true)
        ->call('clearSelection')
        ->assertSet('selected', [])
        ->assertSet('selectAllMatching', false);
});

it('runBulk acts on the live selection', function () {
    Livewire::test(TestBulkTable::class)
        ->set('selected', ['1', '2'])
        ->call('runBulk', 'activate')
        ->assertDispatched('kore:datatable-clear-selection');

    expect(TestUser::whereIn('id', [1, 2])->where('is_active', true)->count())->toBe(2);
});

it('runBulk with selectAllMatching resolves matching ids server-side', function () {
    TestUser::query()->update(['is_active' => false]);

    Livewire::test(TestBulkTable::class)
        ->set('selectAllMatching', true)
        ->call('runBulk', 'activate');

    expect(TestUser::where('is_active', true)->count())->toBe(3);
});

it('runBulk does nothing without a selection', function () {
    Livewire::test(TestBulkTable::class)
        ->call('runBulk', 'activate')
        ->assertNotDispatched('kore:datatable-clear-selection');
});

it('changing search keeps explicit ids but turns off selectAllMatching', function () {
    Livewire::test(TestBulkTable::class)
        ->set('selected', ['1'])
        ->set('selectAllMatching', true)
        ->set('search', 'Bob')
        ->assertSet('selected', ['1'])
        ->assertSet('selectAllMatching', false);
});

it('changing filters turns off selectAllMatching', function () {
    Livewire::test(TestBulkTable::class)
        ->set('selectAllMatching', true)
        ->set('filters', ['anything' => 'x'])
        ->assertSet('selectAllMatching', false);
});

it('changing page does not clear the selection', function () {
    Livewire::test(TestBulkTable::class)
        ->set('perPage', 2)
        ->set('selected', ['1', '2'])
        ->call('gotoPage', 2)
        ->assertSet('selected', ['1', '2']);
});

it('isPageFullySelected and isPagePartiallySelected reflect state', function () {
    $component = Livewire::test(TestBulkTable::class)->instance();

    $component->selected = ['1', '2', '3'];
    expect($component->isPageFullySelected(['1', '2', '3']))->toBeTrue()
        ->and($component->isPagePartiallySelected(['1', '2', '3']))->toBeFalse();

    $component->selected = ['1'];
    expect($component->isPageFullySelected(['1', '2', '3']))->toBeFalse()
        ->and($component->isPagePartiallySelected(['1', '2', '3']))->toBeTrue();
});

it('header data-checked reflects only the current page, not the whole selection', function () {
    // perPage 2 → page 1 = ids 1,2 (fully selected). On page 2 the header must
    // render data-checked="0": its rows aren't selected, even though ids from
    // page 1 remain selected. (Regression guard for the stale .checked morph bug.)
    Livewire::test(TestBulkTable::class)
        ->set('perPage', 2)
        ->set('selected', ['1', '2'])
        ->assertSeeHtml('data-checked="1"')
        ->call('nextPage')
        ->assertSeeHtml('data-checked="0"');
});

// --- Pagination clamp ---

it('clamps an out-of-range page instead of showing an empty page', function () {
    // 3 users, perPage 2 → 2 pages. Requesting page 5 must clamp to the last page.
    $component = Livewire::test(TestBulkTable::class)
        ->set('perPage', 2)
        ->call('gotoPage', 5);

    $rows = $component->instance()->getRows();

    expect($rows->total())->toBe(3)
        ->and($rows->count())->toBeGreaterThan(0)
        ->and($rows->currentPage())->toBe($rows->lastPage());
});
