<?php

namespace KoreUi\DataTable\Views\Contracts;

use KoreUi\DataTable\Views\SavedView;

/**
 * Dónde viven las vistas guardadas de un usuario.
 *
 * La librería trae un driver de sesión, que funciona sin instalar nada. Para
 * persistencia real —vistas que sobrevivan al logout, o compartidas entre
 * usuarios— se implementa esta interfaz y se enlaza en el contenedor:
 *
 *     $this->app->bind(SavedViewStore::class, MiStoreEnBaseDeDatos::class);
 *
 * Se hace así, y no con un modelo y una migración propios, para no obligar a
 * migrar la base de datos de nadie por usar un DataTable.
 *
 * `$tableKey` identifica la tabla (clase + nombre de instancia); el ámbito por
 * usuario es cosa de la implementación, porque solo ella sabe qué es un usuario
 * en esa aplicación.
 */
interface SavedViewStore
{
    /**
     * @return SavedView[]  Indexadas por id.
     */
    public function all(string $tableKey): array;

    public function find(string $tableKey, string $id): ?SavedView;

    public function save(string $tableKey, SavedView $view): SavedView;

    public function delete(string $tableKey, string $id): void;
}
