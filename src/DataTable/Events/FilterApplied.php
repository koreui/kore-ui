<?php

namespace KoreUi\DataTable\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FilterApplied
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $tableClass,
        public array $filters,
        public string $search,
    ) {}
}
