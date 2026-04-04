<?php

namespace KoreUi;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use KoreUi\Breadcrumbs\BreadcrumbManager;
use KoreUi\Feedback\ConfirmDialog;
use KoreUi\Feedback\FeedbackManager;
use KoreUi\Overlay\OverlayManager;
use KoreUi\Spotlight\SpotlightManager;
use Livewire\Livewire;

class KoreUiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/kore-ui.php', 'kore-ui');

        $this->app->singleton('kore-ui', fn () => new KoreManager);

        if (config('kore-ui.breadcrumbs.enabled', true)) {
            $this->app->singleton(BreadcrumbManager::class);
        }
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'kore');

        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'kore');
        Blade::componentNamespace('KoreUi\\View\\Components', 'kore');

        Livewire::component('kore-overlay-manager', OverlayManager::class);
        Livewire::component('kore-feedback-manager', FeedbackManager::class);
        Livewire::component('kore-confirm-dialog', ConfirmDialog::class);
        Livewire::component('kore-spotlight-manager', SpotlightManager::class);

        Route::get('/vendor/kore-ui/kore-ui.js', function () {
            return response()->file(__DIR__.'/../dist/kore-ui.js', [
                'Content-Type' => 'application/javascript; charset=utf-8',
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ]);
        })->name('kore-ui.scripts');

        Blade::directive('koreScripts', function () {
            return "<?php echo '<script src=\"'.route('kore-ui.scripts').'\">'.'</script>'; ?>";
        });

        Blade::directive('koreThemeScript', function () {
            $nonce = config('kore-ui.theme.nonce');
            $nonceAttr = $nonce ? ' nonce="'.e($nonce).'"' : '';

            return '<?php echo \'<script'.$nonceAttr.'>(function(){try{var m=localStorage.getItem("kore-theme")||"system";var d=m==="dark"||(m==="system"&&window.matchMedia("(prefers-color-scheme:dark)").matches);if(d){document.documentElement.classList.add("dark");document.documentElement.setAttribute("data-theme","dark")}}catch(e){}})();</script>\'; ?>';
        });

        if (config('kore-ui.breadcrumbs.enabled', true)) {
            $this->loadBreadcrumbs();
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/kore-ui.php' => config_path('kore-ui.php'),
            ], 'kore-ui-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/kore'),
            ], 'kore-ui-views');
        }
    }

    protected function loadBreadcrumbs(): void
    {
        $files = config('kore-ui.breadcrumbs.files');

        if ($files === null) {
            return;
        }

        if (is_string($files) && ! is_file($files)) {
            return;
        }

        foreach ((array) $files as $file) {
            if (is_file($file)) {
                require $file;
            }
        }
    }
}
