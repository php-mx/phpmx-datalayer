<?php

use PhpMx\Datalayer\Driver\Table;

if (!function_exists('syncIds')) {

    /** Sincroniza dois registros com IDSs cruzados */
    function syncIds(int $recordId, array $oldValues, array $newValues, Table $table, string $fieldName): void
    {
        $old = $oldValues;
        $new = $newValues;

        $add = [];
        $remove = [];

        foreach ($old as $id)
            if (!in_array($id, $new))
                $remove[] = $id;

        foreach ($new as $id)
            if (!in_array($id, $old))
                $add[] = $id;

        $table->getAll('id', [...$add, ...$remove]);

        foreach ($add as $id) {
            $tableRecord = $table->getOne($id);
            $tableRecord->{$fieldName}->add($recordId);
            $tableRecord->_save();
        }

        foreach ($remove as $id) {
            $tableRecord = $table->getOne($id);
            $tableRecord->{$fieldName}->remove($recordId);
            $tableRecord->_save();
        }
    }
}
