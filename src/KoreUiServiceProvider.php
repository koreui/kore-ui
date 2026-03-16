<?php

namespace KoreUi;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use KoreUi\Overlay\OverlayManager;
use Livewire\Livewire;

class KoreUiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/kore-ui.php', 'kore-ui');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'kore');

        Blade::componentNamespace('KoreUi\\View\\Components', 'kore');

        Livewire::component('kore-overlay-manager', OverlayManager::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/kore-ui.php' => config_path('kore-ui.php'),
            ], 'kore-ui-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/kore'),
            ], 'kore-ui-views');
        }
    }
}
