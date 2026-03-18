<?php

namespace KoreUi\Breadcrumbs\Exceptions;

use RuntimeException;

class UnnamedRouteException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The current route does not have a name and cannot be used with breadcrumbs.');
    }
}
