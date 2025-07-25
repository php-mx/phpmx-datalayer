<?php

if (!function_exists('applyChanges')) {

    /** Aplica mudanças em um array */
    function applyChanges(&$array, $changes): void
    {
        foreach ($changes as $key => $newValue) {
            if (isset($array[$key])) {
                if (is_null($newValue)) {
                    unset($array[$key]);
                } elseif (is_array($newValue) && is_array($array[$key])) {
                    applyChanges($array[$key], $newValue);
                } else {
                    $array[$key] = $newValue;
                }
            } elseif (!is_null($newValue)) {
                $array[$key] = $newValue;
            }
        }
    }
}

if (!function_exists('getChanges')) {

    /** Retorna as mudanças realizadas em um array */
    function getChanges($changed, $original): array
    {
        $changes = [];
        foreach ($changed as $key => $newValue) {
            if (isset($original[$key])) {
                if (is_array($newValue) && is_array($original[$key])) {
                    $innerChanges = getChanges($newValue, $original[$key]);
                    if (count($innerChanges)) {
                        $changes[$key] = $innerChanges;
                    }
                } elseif ($newValue !== $original[$key]) {
                    $changes[$key] = $newValue;
                }
            } else {
                $changes[$key] = $newValue;
            }
        }

        foreach ($original as $key => $value) {
            if (!isset($changed[$key])) {
                $changes[$key] = null;
            }
        }

        return $changes;
    }
}
