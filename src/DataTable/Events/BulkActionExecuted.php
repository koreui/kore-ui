<?php

namespace KoreUi\DataTable\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BulkActionExecuted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $tableClass,
        public string $action,
        public array $ids,
        public int $count,
    ) {}
}
