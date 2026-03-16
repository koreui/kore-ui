<?php

namespace KoreUi;

use KoreUi\Feedback\Confirm;
use KoreUi\Feedback\Toast;

class KoreManager
{
    /**
     * Create a new Toast builder instance.
     */
    public function toast(): Toast
    {
        return new Toast();
    }

    /**
     * Create a new Confirm builder instance.
     */
    public function confirm(string $title): Confirm
    {
        return new Confirm($title);
    }
}
