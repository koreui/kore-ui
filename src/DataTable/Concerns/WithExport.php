<?php

namespace KoreUi\DataTable\Concerns;

use InvalidArgumentException;
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

    /**
     * Formato → clase de Exporter. Una tabla puede añadir los suyos con
     * `registerExporter()` desde configure().
     */
    protected array $exporters = [
        'csv' => CsvExporter::class,
    ];

    /**
     * Inicializa el export desde `config('kore-ui.datatable.export')`. Se llama
     * explícitamente desde mount() ANTES de configure(), para que un
     * setExportEnabled() de la tabla siempre gane sobre el valor global.
     *
     * Deliberadamente NO se llama `mountWithExport()`: Livewire invoca por su
     * cuenta los hooks `mount{Trait}`, así que ese nombre se ejecutaría dos
     * veces por montaje y en un orden que no controlamos respecto a configure().
     */
    protected function applyExportConfig(): void
    {
        $this->exportEnabled = (bool) config('kore-ui.datatable.export.enabled', $this->exportEnabled);
        $this->exportFormats = (array) config('kore-ui.datatable.export.formats', $this->exportFormats);
        $this->exportMaxRows = (int) config('kore-ui.datatable.export.max_rows', $this->exportMaxRows);
    }

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

        // applyEagerLoading() igual que en buildRowsQuery(): sin él, una columna
        // con dot-notation dispara una consulta por fila exportada (N+1 sobre
        // el dataset entero, no sobre una página).
        $query = $this->applyEagerLoading($this->applySorts($this->baseFilteredQuery()));

        // chunk() pages the result set; without a deterministic, unique order a
        // non-unique sort column can skip or duplicate rows across pages. Append
        // the primary key as a stable tiebreaker.
        $primaryKey = method_exists($this, 'getPrimaryKey') ? $this->getPrimaryKey() : 'id';
        $query = $query->orderBy($query->getModel()->qualifyColumn($primaryKey));

        $columns = $this->getExportColumns();

        $exporter = $this->resolveExporter($format);

        $fileName = $this->exportFileName
            ?? class_basename($this) . '_' . now()->format('Y-m-d_His') . '.' . $exporter->extension();

        // El tope se aplica dentro del exporter porque chunk() ignora ->limit().
        // Si de verdad recorta, se avisa: hasta ahora el usuario recibía un
        // archivo truncado sin ninguna señal de que faltaban filas.
        if ($this->exportMaxRows > 0 && $this->baseFilteredQuery()->count() > $this->exportMaxRows) {
            $this->notifyExportTruncated($this->exportMaxRows);
        }

        return $exporter->export($query, $columns, $fileName, $this->exportMaxRows);
    }

    /**
     * Sobreescribible: por defecto lanza un toast si la librería de feedback
     * está disponible en la tabla.
     */
    protected function notifyExportTruncated(int $maxRows): void
    {
        if (! method_exists($this, 'toast')) {
            return;
        }

        $template = config(
            'kore-ui.datatable.translations.export_truncated',
            'La exportación se limitó a las primeras :max filas.',
        );

        $this->toast()->warning(strtr($template, [':max' => $maxRows]))->send();
    }

    protected function getExportColumns(): array
    {
        $columns = $this->exportOnlyVisible
            ? $this->resolveColumns()
            : $this->cachedColumns();

        return collect($columns)
            ->reject(fn ($col) => $col instanceof ActionColumn)
            ->values()
            ->all();
    }

    /**
     * Registra un exporter para un formato.
     *
     *     $this->registerExporter('xlsx', XlsxExporter::class)
     *           ->setExportFormats(['csv', 'xlsx']);
     */
    public function registerExporter(string $format, string|Exporter $exporter): static
    {
        $this->exporters[$format] = $exporter;

        return $this;
    }

    /**
     * El `default` de antes devolvía un CsvExporter para cualquier formato
     * desconocido: en cuanto alguien añadiera 'xlsx' a setExportFormats(), el
     * botón habría descargado un CSV con extensión .csv sin decir nada.
     */
    protected function resolveExporter(string $format): Exporter
    {
        $exporter = $this->exporters[$format] ?? null;

        if ($exporter === null) {
            throw new InvalidArgumentException(
                "No hay ningún exporter registrado para el formato [{$format}]. "
                . 'Regístralo con registerExporter() antes de añadirlo a setExportFormats().'
            );
        }

        return is_string($exporter) ? new $exporter() : $exporter;
    }
}
