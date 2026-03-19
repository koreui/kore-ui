<?php

namespace KoreUi\Tests\Spotlight\Fixtures;

use KoreUi\Spotlight\SpotlightItem;
use KoreUi\Spotlight\SpotlightProvider;

class DemoProvider extends SpotlightProvider
{
    public function group(): string
    {
        return 'Demo';
    }

    public function priority(): int
    {
        return 10;
    }

    public function items(): array
    {
        return [
            SpotlightItem::make('Inicio')
                ->icon('home')
                ->route('home'),

            SpotlightItem::make('Usuarios')
                ->icon('users')
                ->route('users.index')
                ->synonym('people', 'clientes'),

            SpotlightItem::make('Configuración')
                ->icon('settings')
                ->route('settings')
                ->shortcut('⌘,'),
        ];
    }
}
