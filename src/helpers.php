<?php

if (! function_exists('kore_toast')) {
    /**
     * Create a new Toast builder instance.
     *
     * Automatically uses session flash when called outside a Livewire component.
     */
    function kore_toast(): \KoreUi\Feedback\Toast
    {
        return new \KoreUi\Feedback\Toast();
    }
}

if (! function_exists('kore_breadcrumbs')) {
    /**
     * Get the BreadcrumbManager singleton.
     */
    function kore_breadcrumbs(): \KoreUi\Breadcrumbs\BreadcrumbManager
    {
        return app(\KoreUi\Breadcrumbs\BreadcrumbManager::class);
    }
}

if (! function_exists('kore_confirm')) {
    /**
     * Create a new Confirm builder instance.
     *
     * Automatically uses session flash when called outside a Livewire component.
     */
    function kore_confirm(string $title): \KoreUi\Feedback\Confirm
    {
        return new \KoreUi\Feedback\Confirm($title);
    }
}
