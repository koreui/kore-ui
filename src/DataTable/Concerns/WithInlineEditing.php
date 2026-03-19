<?php

namespace KoreUi\DataTable\Concerns;

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

        // Validate if rules exist
        $rules = $column->getEditableRules();

        if (! empty($rules)) {
            $validator = Validator::make(
                [$field => $value],
                [$field => $rules],
            );

            if ($validator->fails()) {
                $this->dispatch('kore:datatable-edit-error', [
                    'rowId' => $rowId,
                    'field' => $field,
                    'error' => $validator->errors()->first($field),
                ]);

                return;
            }
        }

        // Capture old value before update
        $model = $this->query()->getModel();
        $primaryKey = $this->getPrimaryKey();
        $oldValue = $model::where($primaryKey, $rowId)->value($field);

        // Execute callback or direct update
        $callback = $column->getEditableCallback();

        if ($callback) {
            $callback($rowId, $field, $value);
        } else {
            $model::where($primaryKey, $rowId)->update([$field => $value]);
        }

        event(new RowUpdated(static::class, $rowId, $field, $value, $oldValue));

        $this->dispatch('kore:datatable-edit-success', [
            'rowId' => $rowId,
            'field' => $field,
        ]);
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
