<?php

namespace KoreUi\DataTable\Concerns;

use KoreUi\DataTable\Columns\ActionColumn;
use KoreUi\DataTable\Exports\Contracts\Exporter;
use KoreUi\DataTable\Exports\CsvExporter;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait WithExport
{
    protected bool $exportEnabled = false;

    protected array $exportFormats = ['csv'];

    protected ?string $exportFileName = null;

    protected bool $exportOnlyVisible = true;

    protected int $exportMaxRows = 10000;

    public function setExportEnabled(bool $enabled = true): static
    {
        $this->exportEnabled = $enabled;

        return $this;
    }

    public function setExportFormats(array $formats): static
    {
        $this->exportFormats = $formats;

        return $this;
    }

    public function setExportFileName(string $fileName): static
    {
        $this->exportFileName = $fileName;

        return $this;
    }

    public function setExportOnlyVisible(bool $onlyVisible = true): static
    {
        $this->exportOnlyVisible = $onlyVisible;

        return $this;
    }

    public function setExportMaxRows(int $maxRows): static
    {
        $this->exportMaxRows = $maxRows;

        return $this;
    }

    public function isExportEnabled(): bool
    {
        return $this->exportEnabled;
    }

    public function getExportFormats(): array
    {
        return $this->exportFormats;
    }

    public function exportAs(string $format = 'csv'): StreamedResponse
    {
        // exportAs() is a public Livewire method, reachable from the browser via
        // $wire.exportAs(). Hiding the toolbar button is not an authorization
        // check — enforce it here, and reject formats outside the configured set
        // so an unknown format can't silently fall back to CSV.
        abort_unless($this->isExportEnabled(), 403);
        abort_unless(in_array($format, $this->getExportFormats(), true), 404);

        $query = $this->applySorts($this->baseFilteredQuery());

        // chunk() pages the result set; without a deterministic, unique order a
        // non-unique sort column can skip or duplicate rows across pages. Append
        // the primary key as a stable tiebreaker.
        $primaryKey = method_exists($this, 'getPrimaryKey') ? $this->getPrimaryKey() : 'id';
        $query = $query->orderBy($query->getModel()->qualifyColumn($primaryKey));

        $columns = $this->getExportColumns();

        $exporter = $this->resolveExporter($format);

        $fileName = $this->exportFileName
            ?? class_basename($this) . '_' . now()->format('Y-m-d_His') . '.' . $exporter->extension();

        // maxRows is enforced inside the exporter: chunk() ignores ->limit().
        return $exporter->export($query, $columns, $fileName, $this->exportMaxRows);
    }

    protected function getExportColumns(): array
    {
        $columns = $this->exportOnlyVisible
            ? $this->resolveColumns()
            : $this->columns();

        return collect($columns)
            ->reject(fn ($col) => $col instanceof ActionColumn)
            ->values()
            ->all();
    }

    protected function resolveExporter(string $format): Exporter
    {
        return match ($format) {
            'csv' => new CsvExporter(),
            default => new CsvExporter(),
        };
    }
}
