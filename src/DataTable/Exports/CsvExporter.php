<?php

namespace KoreUi\DataTable\Exports;

use Illuminate\Database\Eloquent\Builder;
use KoreUi\DataTable\Exports\Contracts\Exporter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporter implements Exporter
{
    protected string $delimiter = ',';

    protected string $enclosure = '"';

    protected bool $includeBom = true;

    public function __construct(string $delimiter = ',', string $enclosure = '"', bool $includeBom = true)
    {
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->includeBom = $includeBom;
    }

    public function export(Builder $query, array $columns, string $fileName, int $maxRows = 0): StreamedResponse
    {
        return response()->streamDownload(function () use ($query, $columns, $maxRows) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            if ($this->includeBom) {
                fwrite($handle, "\xEF\xBB\xBF");
            }

            // Header row
            $headers = array_map(fn ($col) => $col->getLabel(), $columns);
            fputcsv($handle, $headers, $this->delimiter, $this->enclosure);

            // Data rows. A counter enforces the cap because chunk()'s forPage()
            // overrides any ->limit() set on the query before chunking.
            $written = 0;
            $query->chunk(1000, function ($rows) use ($handle, $columns, $maxRows, &$written) {
                foreach ($rows as $row) {
                    if ($maxRows > 0 && $written >= $maxRows) {
                        return false;
                    }

                    $data = [];

                    foreach ($columns as $column) {
                        $value = $column->getValue($row);

                        if (is_bool($value)) {
                            $value = $value ? 'Sí' : 'No';
                        } elseif (is_array($value)) {
                            $value = implode(', ', $value);
                        } elseif ($value === null) {
                            $value = '';
                        }

                        $data[] = $this->neutralizeFormula((string) $value);
                    }

                    fputcsv($handle, $data, $this->delimiter, $this->enclosure);
                    $written++;
                }
            });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => $this->mimeType(),
        ]);
    }

    /**
     * Prevent CSV/formula injection: values beginning with a formula trigger
     * (= + - @ TAB CR) are executed by Excel/LibreOffice/Sheets when opened.
     * Prefixing with a single quote forces them to be read as plain text.
     */
    protected function neutralizeFormula(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }

    public function extension(): string
    {
        return 'csv';
    }

    public function mimeType(): string
    {
        return 'text/csv; charset=UTF-8';
    }
}
