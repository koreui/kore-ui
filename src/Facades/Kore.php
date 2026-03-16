<?php

namespace KoreUi\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \KoreUi\Feedback\Toast toast()
 * @method static \KoreUi\Feedback\Confirm confirm(string $title)
 *
 * @see \KoreUi\KoreManager
 */
class Kore extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'kore-ui';
    }
}
