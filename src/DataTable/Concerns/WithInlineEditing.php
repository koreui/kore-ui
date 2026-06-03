<?php

namespace KoreUi\DataTable\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use KoreUi\DataTable\Columns\Column;
use KoreUi\DataTable\Events\RowUpdated;

trait WithInlineEditing
{
    public function updateCell(string|int $rowId, string $field, mixed $value): void
    {
        $column = $this->findEditableColumn($field);

        if (! $column) {
            return;
        }

        // Coerce the client value to the column's declared type before
        // validating/saving (boolean toggles, numeric inputs, …).
        $value = $this->coerceEditableValue($column, $value);

        // Validate if rules exist
        $rules = $column->getEditableRules();

        if (! empty($rules)) {
            $validator = Validator::make(
                [$field => $value],
                [$field => $rules],
            );

            if ($validator->fails()) {
                $this->dispatch(
                    'kore:datatable-edit-error',
                    rowId: $rowId,
                    field: $field,
                    error: $validator->errors()->first($field),
                );

                return;
            }
        }

        $primaryKey = $this->getPrimaryKey();
        $callback   = $column->getEditableCallback();

        $result = DB::transaction(function () use ($primaryKey, $rowId, $field, $value, $callback) {
            // Resolve through query() so row-level scopes/authorization apply.
            // Using the model statically would let any client edit records
            // outside the table's authorized dataset (IDOR).
            $record = $this->query()
                ->where($primaryKey, $rowId)
                ->lockForUpdate()
                ->first();

            if (! $record) {
                return ['found' => false, 'old' => null];
            }

            $old = data_get($record, $field);

            if ($callback) {
                $callback($rowId, $field, $value);
            } else {
                $record->update([$field => $value]);
            }

            return ['found' => true, 'old' => $old];
        });

        if (! $result['found']) {
            $this->dispatch(
                'kore:datatable-edit-error',
                rowId: $rowId,
                field: $field,
                error: config(
                    'kore-ui.datatable.translations.edit_unauthorized',
                    'No se pudo actualizar el registro.'
                ),
            );

            return;
        }

        event(new RowUpdated(static::class, $rowId, $field, $value, $result['old']));

        // An edit may move a row in/out of a preset → refresh preset badges.
        if (method_exists($this, 'invalidatePresetCounts')) {
            $this->invalidatePresetCounts();
        }

        $this->dispatch('kore:datatable-edit-success', rowId: $rowId, field: $field);
    }

    /**
     * Coerce a client-provided value to the column's declared type so the right
     * scalar reaches validation and persistence.
     */
    protected function coerceEditableValue(Column $column, mixed $value): mixed
    {
        return match ($column->getType()) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value,
            'number'  => is_numeric($value) ? $value + 0 : $value,
            default   => $value,
        };
    }

    public function hasEditableColumns(): bool
    {
        return collect($this->columns())->contains(fn (Column $col) => $col->isEditable());
    }

    public function getEditableColumnsMap(): array
    {
        return collect($this->columns())
            ->filter(fn (Column $col) => $col->isEditable())
            ->mapWithKeys(fn (Column $col) => [
                $col->getField() => [
                    'component' => $col->getEditableComponent(),
                    'rules'     => $col->getEditableRules(),
                ],
            ])
            ->all();
    }

    protected function findEditableColumn(string $field): ?Column
    {
        return collect($this->columns())
            ->first(fn (Column $col) => $col->getField() === $field && $col->isEditable());
    }
}
