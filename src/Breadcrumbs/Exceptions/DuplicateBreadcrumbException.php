<?php

namespace KoreUi\Breadcrumbs\Exceptions;

use RuntimeException;

class DuplicateBreadcrumbException extends RuntimeException
{
    public function __construct(string $route)
    {
        parent::__construct("Breadcrumb already defined for route [{$route}].");
    }
}
