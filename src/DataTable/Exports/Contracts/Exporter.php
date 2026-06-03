<?php

namespace KoreUi\DataTable\Exports\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface Exporter
{
    /**
     * Export data from the query using the given columns.
     *
     * @param  \KoreUi\DataTable\Columns\Column[]  $columns
     * @param  int  $maxRows  Hard cap on exported rows (0 = unlimited).
     */
    public function export(Builder $query, array $columns, string $fileName, int $maxRows = 0): StreamedResponse;

    public function extension(): string;

    public function mimeType(): string;
}
