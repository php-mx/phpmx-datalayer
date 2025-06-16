<?php

switch ($map['type']) {
    case 'id':
        $map['size'] = 10;
        $map['index'] = $map['index'] ?? true;
        break;

    case 'idx':
        $map['size'] = 10;
        $map['index'] = $map['index'] ?? true;
        $map['settings']['datalayer'] = Datalayer::internalName($map['settings']['datalayer']);
        $map['settings']['table'] = Datalayer::internalName($map['settings']['table']);
        break;

    case 'int':
    case 'float':
        $map['size'] = $map['size'] ?? 10;
        break;

    case 'boolean':
        $map['size'] = 1;
        if (is_bool($map['default']))
            $map['default'] = intval($map['default']);
        break;

    case 'email':
        $map['size'] = $map['size'] ?? 200;
        break;

    case 'hash':
        $map['size'] = 32;
        break;

    case 'code':
        $map['size'] = 34;
        break;

    case 'text':
        break;

    case 'json':
        $map['default'] = $map['default'] ?? '[]';
        break;

    case 'ids':
        $map['size'] = null;
        $map['null'] = false;
        $map['settings']['datalayer'] = Datalayer::internalName($map['settings']['datalayer']);
        $map['settings']['table'] = Datalayer::internalName($map['settings']['table']);
        break;

    case 'log':
        $map['size'] = null;
        $map['null'] = false;
        break;
    case 'config':
        $map['size'] = null;
        $map['null'] = false;
        if (isset($map['default'])) {
            if (is_array($map['default']))
                $map['default'] = json_encode($map['default']);
            else
                unset($map['default']);
        }
        break;

    case 'time':
        $map['size'] = 11;
        break;

    case 'string':
    default:
        $map['type'] = 'string';
        $map['size'] = $map['size'] ?? 50;
        break;
}
