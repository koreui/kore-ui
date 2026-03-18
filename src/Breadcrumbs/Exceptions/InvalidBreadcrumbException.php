<?php

namespace KoreUi\Breadcrumbs\Exceptions;

use RuntimeException;

class InvalidBreadcrumbException extends RuntimeException
{
    public function __construct(string $route)
    {
        parent::__construct("No breadcrumb defined for route [{$route}].");
    }
}
