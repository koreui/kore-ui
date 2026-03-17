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
        'password' => ['toggleable' => true],
        'textarea' => ['rows' => 4],
        'datepicker' => [
            'locale' => null,
            'start_of_week' => 1,
            'format' => null,
        ],
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
