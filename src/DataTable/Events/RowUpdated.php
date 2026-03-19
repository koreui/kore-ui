<?php

namespace KoreUi\DataTable\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RowUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $tableClass,
        public string|int $rowId,
        public string $field,
        public mixed $value,
        public mixed $oldValue,
    ) {}
}
