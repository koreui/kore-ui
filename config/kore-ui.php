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

];
