<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Overlay Defaults
    |--------------------------------------------------------------------------
    |
    | Default configuration for overlay components (modal, drawer, confirm,
    | bottom-sheet, fullscreen). Each overlay component can override these
    | values via static methods.
    |
    */
    'overlay' => [
        'defaults' => [
            'type' => 'modal',
            'size' => '2xl',
            'position' => 'center',
            'close_on_click_away' => true,
            'close_on_escape' => true,
            'escape_closes_all' => true,
            'dispatch_close_event' => false,
            'destroy_on_close' => false,
            'backdrop_blur' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feedback Defaults
    |--------------------------------------------------------------------------
    |
    | Configuration for toast notifications and confirm dialogs.
    |
    */
    'feedback' => [
        'toast' => [
            'position'         => 'top-right',
            'timeout'          => 5,
            'dismissible'      => true,
            'max_visible'      => 5,
            'spacing'          => 'gap-3',
            'z_index'          => 'z-[60]',
            'expand_delay'     => 150,
            'collapse_delay'   => 300,
            'swipe_to_dismiss' => true,
        ],
        'confirm' => [
            'size'               => 'md',
            'confirm_text'       => 'Confirmar',
            'cancel_text'        => 'Cancelar',
            'closes_on_escape'   => true,
            'closes_on_click_away' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Form Defaults
    |--------------------------------------------------------------------------
    |
    | Default configuration for form components.
    |
    */
    'form' => [
        'size' => 'md',
        'show_errors' => true,
        'select' => [
            'debounce' => 300,
            'min_search' => 2,
            'search_threshold' => 10,
        ],
        'password' => [
            'toggleable' => true,
            'strength' => false,
            'min_length' => 8,
        ],
        'number' => [
            'currency' => 'USD',
            'locale' => null,
            'precision' => 2,
        ],
        'textarea' => ['rows' => 4],
        'datepicker' => [
            'locale' => null,
            'start_of_week' => 1,
            'format' => null,
        ],
        'upload' => [
            'max_size' => null,
            'delete_method' => 'deleteUpload',
            'auto_upload' => true,
            'retryable' => false,
            'max_retries' => 3,
            'retry_delay' => 2000,
        ],
        'rating' => [
            'stars' => 5,
            'clearable' => true,
        ],
        'color_picker' => [
            'columns' => 8,
            'allow_custom' => true,
        ],
        'maskable' => [
            'slot_char' => '_',
            'auto_clear' => false,
            'emit_formatted' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Component Defaults
    |--------------------------------------------------------------------------
    |
    | Default configuration for general UI components (button, alert, badge,
    | card, dropdown, tooltip, avatar, loading).
    |
    */
    'ui' => [
        'size' => 'md',
        'button' => [
            'variant' => 'solid',
            'color' => 'primary',
        ],
        'alert' => [
            'variant' => 'soft',
        ],
        'badge' => [
            'variant' => 'soft',
        ],
        'card' => [
            'bordered' => true,
            'shadow' => true,
        ],
        'dropdown' => [
            'position' => 'bottom-start',
            'width' => 'auto',
        ],
        'tooltip' => [
            'position' => 'top',
            'delay' => 200,
        ],
        'avatar' => [
            'shape' => 'circle',
        ],
        'loading' => [
            'type' => 'spinner',
        ],
        'page-loading' => [
            'type' => 'spinner',
            'blur' => true,
            'text' => null,
        ],
        'divider' => [
            'type' => 'solid',
        ],
        'progress' => [
            'size' => 'md',
            'color' => 'primary',
        ],
        'toolbar' => [
            'variant' => 'default',
        ],
        'accordion' => [
            'variant' => 'bordered',
        ],
        'tab' => [
            'variant' => 'line',
        ],
        'stepper' => [
            'variant' => 'horizontal',
            'linear' => false,
        ],
        'skeleton' => [
            'animation' => 'shimmer',  // shimmer|pulse|none
        ],
        'chip' => [
            'variant' => 'soft',
        ],
        'timeline' => [
            'variant' => 'left',
        ],
        'stats' => [
            'variant' => 'default',
            'animated' => true,
        ],
        'clipboard' => [
            'variant' => 'input',
        ],
        'kbd' => [
            'size' => 'md',
        ],
        'boolean' => [
            'true_icon' => 'check',
            'false_icon' => 'x',
            'true_color' => 'success',
            'false_color' => 'destructive',
        ],
        'speed-dial' => [
            'icon' => 'plus',
            'direction' => 'up',
            'size' => 'sm',
        ],
        'splitter' => [
            'orientation' => 'horizontal',
            'gutter_size' => 8,
        ],
        'carousel' => [
            'autoplay' => false,
            'interval' => 5000,
            'loop' => true,
            'pause_on_hover' => true,
            'show_indicators' => true,
            'show_navigation' => true,
            'num_visible' => 1,
            'gap' => 16,
        ],
        'icon' => [
            'prefix' => 'lucide',
            'size' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Breadcrumbs
    |--------------------------------------------------------------------------
    |
    | Configuration for the breadcrumb navigation component and its
    | PHP registration system.
    |
    */
    'breadcrumbs' => [
        // Set to false to disable the breadcrumb registration system entirely.
        // Useful when using another breadcrumbs library (e.g. diglactic/laravel-breadcrumbs).
        // The <x-kore::breadcrumbs /> component still works with manual :items.
        'enabled' => true,

        // File(s) where breadcrumbs are defined. Supports string or array of paths.
        // Uses a kore-specific filename to avoid conflicts with other libraries.
        'files' => base_path('routes/kore-breadcrumbs.php'),

        'separator' => 'icon:chevron-right',
        'size' => 'md',
        'json_ld' => false,
        'exceptions' => [
            'missing' => true,
            'unnamed' => true,
            'duplicate' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | DataTable Defaults
    |--------------------------------------------------------------------------
    |
    | Default configuration for the DataTable component (sorting, search,
    | pagination, density).
    |
    */
    'datatable' => [
        'per_page'              => 25,
        'per_page_options'      => [10, 25, 50, 100],
        'density'               => 'normal',      // compact|normal|relaxed
        'pagination_type'       => 'standard',    // standard|simple|cursor
        'search_debounce'       => 300,
        'filter_layout'         => 'popover',     // popover|slide-down|inline|drawer
        'column_select'         => true,
        'responsive_mode'       => 'scroll',      // scroll|collapse|card
        'responsive_breakpoint' => 768,
        'query_string'          => false,          // persist state in URL
        'export'                => [
            'enabled'    => false,
            'formats'    => ['csv'],
            'max_rows'   => 10000,
        ],
        'deferred_loading'      => false,
        'empty_text'            => 'No se encontraron resultados',
        'empty_icon'            => 'inbox',
        'translations'          => [
            'search'        => 'Buscar...',
            'per_page'      => 'Por página',
            'showing'       => 'Mostrando :from a :to de :total resultados',
            'no_results'    => 'No se encontraron resultados',
            'filters'       => 'Filtros',
            'actions'       => 'Acciones',
            'selected'      => 'seleccionado(s)',
            'clear_filters' => 'Limpiar filtros',
            'apply'         => 'Aplicar',
            'columns'       => 'Columnas',
            'reset_columns' => 'Restablecer',
            'sorted_by'     => 'Ordenado por',
            'clear_sorts'   => 'Limpiar ordenamiento',
            'export'        => 'Exportar',
            'cancel'        => 'Cancelar',
            'edit_unauthorized' => 'No se pudo actualizar el registro.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Spotlight (Command Palette)
    |--------------------------------------------------------------------------
    |
    | Configuration for the Spotlight / command palette component.
    | Activated with Cmd+K / Ctrl+K.
    |
    */
    'spotlight' => [
        // PHP provider classes that supply spotlight items
        'providers' => [],

        // Keyboard shortcut key combined with Cmd (Mac) or Ctrl (Windows/Linux)
        'shortcut' => 'k',

        // Placeholder text shown in the search input
        'placeholder' => 'Buscar acciones, páginas...',

        // Show provider items when input is empty (before typing)
        'show_results_without_input' => true,

        // Show recent history section when input is empty
        'show_recent' => true,

        // Number of recent items to show (from localStorage)
        'recent_count' => 5,

        // Maximum history entries persisted in localStorage
        'max_history' => 10,

        // Global remote search endpoint (optional, can be overridden per-component)
        'search_url' => null,

        // HTTP method for remote search requests
        'search_method' => 'GET',

        // Debounce delay in milliseconds for remote search
        'debounce' => 300,

        // Maximum total results to display (local + remote)
        'max_results' => 50,

        // z-index for the spotlight panel (above overlays z-50 and feedback z-60)
        'z_index' => 'z-[70]',
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme Defaults
    |--------------------------------------------------------------------------
    |
    | Configuration for the theme switcher component and anti-FOUC script.
    |
    */
    'theme' => [
        'default' => 'system',  // 'light', 'dark', 'system'
        'nonce' => null,         // CSP nonce for the anti-FOUC inline script
    ],

];
