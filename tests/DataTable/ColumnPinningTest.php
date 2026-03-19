<?php

use Illuminate\Support\Facades\Schema;
use KoreUi\DataTable\Columns\Column;
use KoreUi\Tests\DataTable\Fixtures\TestUser;
use Livewire\Livewire;

it('supports pinned left fluent API', function () {
    $column = Column::make('ID', 'id')->pinned();

    expect($column->isPinned())->toBeTrue()
        ->and($column->getPinnedSide())->toBe('left');
});

it('supports pinned right', function () {
    $column = Column::make('Actions', 'actions')->pinned('right');

    expect($column->isPinned())->toBeTrue()
        ->and($column->getPinnedSide())->toBe('right');
});

it('is not pinned by default', function () {
    $column = Column::make('Name', 'name');

    expect($column->isPinned())->toBeFalse()
        ->and($column->getPinnedSide())->toBeNull();
});

it('includes pinned in toArray', function () {
    $column = Column::make('ID', 'id')->pinned('left');

    $array = $column->toArray();

    expect($array)->toHaveKey('pinned')
        ->and($array['pinned'])->toBe('left');
});

it('renders pinned columns with sticky CSS', function () {
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
    ]);

    // Create an anonymous table class with pinned column
    $tableClass = new class extends \KoreUi\DataTable\KoreDataTable {
        public function query(): \Illuminate\Database\Eloquent\Builder
        {
            return TestUser::query();
        }

        public function columns(): array
        {
            return [
                Column::make('ID', 'id')->pinned('left')->width(80),
                Column::make('Nombre', 'name'),
                Column::make('Email', 'email'),
            ];
        }
    };

    Livewire::test($tableClass::class)
        ->assertSee('position: sticky');

    Schema::dropIfExists('test_users');
});
