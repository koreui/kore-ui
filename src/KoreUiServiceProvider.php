<?php

namespace KoreUi;

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use KoreUi\Breadcrumbs\BreadcrumbManager;
use KoreUi\Charts\ChartContext;
use KoreUi\DataTable\Views\Contracts\SavedViewStore;
use KoreUi\DataTable\Views\SessionSavedViewStore;
use KoreUi\Feedback\ConfirmDialog;
use KoreUi\Feedback\FeedbackManager;
use KoreUi\Overlay\OverlayManager;
use KoreUi\Shell\ShellContext;
use KoreUi\Shell\SidebarState;
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

        // scoped, no singleton: Octane reutiliza el contenedor entre requests y el
        // registro de sidebars de una petición no debe filtrarse a la siguiente.
        $this->app->scoped(ShellContext::class);

        // Mismo motivo, y además: el contador de ids de gráfico tiene que empezar en 1 en
        // cada petición, o los ids dejarían de ser deterministas entre renders y el morph de
        // Livewire reemplazaría los nodos en vez de actualizarlos.
        $this->app->scoped(ChartContext::class);

        // Driver por defecto de las vistas guardadas del DataTable. Se enlaza
        // con bindIf para que una aplicación pueda registrar el suyo —contra su
        // propia tabla, con su propio criterio de usuario— sin tener que pelear
        // con el orden de carga de los providers.
        $this->app->bindIf(SavedViewStore::class, fn ($app) => new SessionSavedViewStore($app['session.store']));
    }

    public function boot(): void
    {
        // La cookie del sidebar la escribe JavaScript en texto plano. Laravel
        // encripta todas las cookies y, al no poder desencriptar esta, la anularía
        // (EncryptCookies::decrypt() hace $request->cookies->set($key, null) al
        // capturar DecryptException). El estado no persistiría jamás, y sin ningún
        // error visible. Esto tiene que correr antes del middleware: boot() lo hace.
        EncryptCookies::except(SidebarState::COOKIE);

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'kore');

        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'kore');
        Blade::componentNamespace('KoreUi\\View\\Components', 'kore');

        Livewire::component('kore-overlay-manager', OverlayManager::class);
        Livewire::component('kore-feedback-manager', FeedbackManager::class);
        Livewire::component('kore-confirm-dialog', ConfirmDialog::class);
        Livewire::component('kore-spotlight-manager', SpotlightManager::class);

        Route::get('/vendor/kore-ui/kore-ui.js', function () {
            $bundle = __DIR__.'/../dist/kore-ui.js';

            // `immutable` con un año de caché solo es honesto si la URL cambia
            // cuando cambia el archivo: @koreScripts le añade ?id= con la huella
            // del bundle. Sin esa huella —alguien que pida la ruta a pelo— se
            // sirve revalidando, para no dejar clavado en el navegador un
            // bundle viejo hasta 2027.
            $versionado = request()->filled('id');

            return response()->file($bundle, [
                'Content-Type'  => 'application/javascript; charset=utf-8',
                'Cache-Control' => $versionado
                    ? 'public, max-age=31536000, immutable'
                    : 'public, max-age=0, must-revalidate',
            ]);
        })->name('kore-ui.scripts');

        Blade::directive('koreScripts', function () {
            return "<?php echo '<script src=\"'.\KoreUi\KoreUiServiceProvider::scriptUrl().'\"></script>'; ?>";
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

    /**
     * URL del bundle con la huella del archivo.
     *
     * Sin ella la ruta es fija y la respuesta se marcaba `immutable` con un año
     * de caducidad: al publicar una versión nueva de la librería, el navegador
     * de cada usuario seguía ejecutando el bundle anterior y no había forma de
     * invalidarlo. La huella es el mtime, que cambia con cada build.
     */
    public static function scriptUrl(): string
    {
        static $huella = null;

        if ($huella === null) {
            $bundle = __DIR__.'/../dist/kore-ui.js';
            $huella = is_file($bundle) ? substr(md5((string) filemtime($bundle)), 0, 8) : 'dev';
        }

        return route('kore-ui.scripts', ['id' => $huella]);
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
