<?php

use PhpMx\Datalayer;
use PhpMx\Dir;
use PhpMx\File;
use PhpMx\Import;
use PhpMx\Path;
use PhpMx\Terminal;

return new class extends Terminal {

    protected string $dbName = '';
    protected string $namespace = '';
    protected string $path = '';
    protected array $map = [];

    function __invoke($dbName = 'main')
    {
        $dbName = Datalayer::internalName($dbName);

        $map = Datalayer::get($dbName)->getConfig('__dbMap') ?? [];

        $namespace = 'Model\\' . strToPascalCase("db $dbName");

        $path = path('source', $namespace);

        $this->dbName = $dbName;
        $this->map = $map;
        $this->namespace = $namespace;
        $this->path = $path;

        self::echoLine();

        Dir::remove($this->path . "/Driver", true);

        $this->createDriver_database();
        $this->createClass_database();
        self::echo(" [OK] datalayer ");

        foreach ($this->map as $tableName => $tableMap) {
            $this->createDriver_table($tableName);
            $this->createDriver_record($tableName, $tableMap);

            $this->createClass_table($tableName);
            $this->createClass_record($tableName);
            self::echo(" [OK] table $tableName");
        }

        self::echoLine();
        self::echo("Driver installed");
    }

    protected function createDriver_database(): void
    {
        $fileName = strToPascalCase("Driver Db $this->dbName");

        $start = [];
        $method = [];
        $varTable = [];

        foreach ($this->map as $tableName => $table) {
            $tableClass = strToPascalCase("table $tableName");
            $recordClass = strToPascalCase("record $tableName");
            $tableMethod = strToCamelCase($tableName);
            $tableComment = $table['comment'] ? $table['comment'] : '';

            $data = [
                'mainClass' => $fileName,
                'tableClass' => $tableClass,
                'recordClass' => $recordClass,
                'tableMethod' => $tableMethod,
                'tableComment' => $tableComment,
            ];

            $start[] = $this->template('datalayer/driver/main/start', $data);
            $method[] = $this->template('datalayer/driver/main/method', $data);
            $varTable[] = $this->template('datalayer/driver/main/varTable', $data);
        }

        $data = [
            'mainClass' => $fileName,
            'start' => implode('', $start),
            'method' => implode('', $method),
            'varTable' => implode('', $varTable),
        ];

        $content = $this->template('datalayer/driver/main/class', $data);

        File::create($this->path . "/Driver/$fileName.php", $content, true);
    }

    protected function createDriver_table(string $tableName): void
    {
        $datalayer = $this->dbName;

        $tableClass = strToPascalCase("table $tableName");
        $recordClass = strToPascalCase("record $tableName");
        $tableMethod = strToCamelCase($tableName);

        $fileName = "Driver$tableClass";

        $data = [
            'datalayer' => $datalayer,
            'tableName' => $tableName,
            'tableMethod' => $tableMethod,
            'tableClass' => $tableClass,
            'recordClass' => $recordClass,
        ];

        $content = $this->template('datalayer/driver/table/class', $data);

        File::create($this->path . "/Driver/$fileName.php", $content, true);
    }

    protected function createDriver_record(string $tableName, array $tableMap): void
    {
        $datalayer = $this->dbName;
        $tableClass = strToPascalCase("table $tableName");
        $recordClass = strToPascalCase("record $tableName");

        $fileName = "Driver$recordClass";

        $autocomplete = [];
        $createFields = [];

        foreach ($tableMap['fields'] as $fieldName => $fieldMap) {

            $feildMethod = strToCamelCase($fieldName);

            if (!str_starts_with($fieldName, '_')) {
                $value = 'null';

                if (!is_null($fieldMap['default'])) {
                    if (is_string($fieldMap['default'])) {
                        $value = $fieldMap['default'] == "''" ? $fieldMap['default'] : "'$fieldMap[default]'";
                    } else if (is_numeric($fieldMap['default'])) {
                        $value = $fieldMap['default'];
                    }
                }

                $settings = [];

                switch ($fieldMap['type']) {
                    case 'int':
                        if ($fieldMap['size']) $settings['size'] = $fieldMap['size'];
                        if (isset($fieldMap['settings']['min'])) $settings['min'] = $fieldMap['settings']['min'];
                        if (isset($fieldMap['settings']['max'])) $settings['max'] = $fieldMap['settings']['max'];
                        if (isset($fieldMap['settings']['round'])) $settings['round'] = $fieldMap['settings']['round'];
                        break;

                    case 'float':
                        if ($fieldMap['size']) $settings['size'] = $fieldMap['size'];
                        if (isset($fieldMap['settings']['min'])) $settings['min'] = $fieldMap['settings']['min'];
                        if (isset($fieldMap['settings']['max'])) $settings['max'] = $fieldMap['settings']['max'];
                        if (isset($fieldMap['settings']['round'])) $settings['round'] = $fieldMap['settings']['round'];
                        if (isset($fieldMap['settings']['decimal'])) $settings['decimal'] = $fieldMap['settings']['decimal'];
                        break;

                    case 'email':
                    case 'string':
                    case 'text':
                        if ($fieldMap['size']) $settings['size'] = $fieldMap['size'];
                        if (isset($fieldMap['settings']['crop'])) $settings['crop'] = $fieldMap['settings']['crop'];
                        break;
                    case 'idx':
                    case 'ids':
                        $settings['datalayer'] = $fieldMap['settings']['datalayer'];
                        $settings['table'] = $fieldMap['settings']['table'];
                        break;
                    default:
                        $settings = [];
                }

                $fieldMap['phpType'] = match ($fieldMap['type']) {
                    'boolean' => 'bool',
                    'code', 'email', 'hash', 'string', 'text' => 'string',
                    'config' => 'mixed',
                    'float' => 'float',
                    'ids', 'log' => 'array',
                    'idx', 'int' => 'int',
                    'time' => 'int|string',
                    default => 'mixed'
                };

                $data = [
                    'fieldMethod' => $feildMethod,
                    'fieldComment' => $fieldMap['comment'],
                    'fieldType' => ucfirst($fieldMap['type']),
                    'fieldPhpType' => $fieldMap['phpType'],
                    'fieldValue' => $value,
                    'fieldUseNull' => $fieldMap['null'] ? 'true' : 'false',
                    'fieldSettings' => $this->arrayToDeclarationString($settings)
                ];

                if ($fieldMap['type'] == 'idx') {
                    $data['fieldNamespace'] = 'Model\\' . strToPascalCase("db " . $fieldMap['settings']['datalayer']);
                    $data['fieldRecordClass'] = strToPascalCase("record " . $fieldMap['settings']['table']);
                    $autocomplete[] = $this->template('datalayer/driver/record/autocomplete_dynamicId', $data);
                } else {
                    $autocomplete[] = $this->template('datalayer/driver/record/autocomplete', $data);
                }
                $createFields[] = $this->template("datalayer/driver/record/createFields", $data);
            }
        }

        $data = [
            'datalayer' => $datalayer,
            'tableName' => $tableName,
            'tableClass' => $tableClass,
            'recordClass' => $recordClass,
            'autocomplete' => implode("\n * ", $autocomplete),
            'createFields' => implode('', $createFields),
        ];

        $content = $this->template('datalayer/driver/record/class', $data);

        File::create($this->path . "/Driver/$fileName.php", $content, true);
    }

    protected function createClass_database(): void
    {
        $fileName = strToPascalCase("db $this->dbName");

        $data = ['className' => $fileName];

        $content = $this->template('datalayer/class/main/class', $data);

        File::create($this->path . "/$fileName.php", $content);
    }

    protected function createClass_table(string $tableName): void
    {
        $tableClass = strToPascalCase("table $tableName");
        $tableComment = $this->map[$tableName]['comment'];
        $tableComment = empty($tableComment) ? '' : "\n/** $tableComment */";

        $fileName = $tableClass;

        $data = [
            'tableComment' => $tableComment,
            'tableClass' => $tableClass
        ];

        $content = $this->template('datalayer/class/table/class', $data);

        File::create($this->path . "/Table/$fileName.php", $content);
    }

    protected function createClass_record(string $tableName): void
    {
        $recordClass = strToPascalCase("record $tableName");

        $fileName = $recordClass;

        $data = ['recordClass' => $recordClass];

        $content = $this->template('datalayer/class/record/class', $data);

        File::create($this->path . "/Record/$fileName.php", $content);
    }

    /** Retrona um teplate de driver */
    protected function template(string $file, array $data = []): string
    {
        $file = Path::seekFile("storage/template/terminal/$file.txt");

        $data['dbName'] = $this->dbName;
        $data['namespace'] = $this->namespace;

        $template = Import::content($file, $data);

        return prepare($template, $data);
    }

    /** Converte um array em string de declaração de array */
    protected function arrayToDeclarationString($array): string
    {
        $string = var_export($array, true);
        $string = str_replace(['array (', ')'], ['[', ']'], $string);
        $string = preg_replace('/\s+/', ' ', $string);
        $string = preg_replace('/,\s+\]/', ']', $string);
        $string = preg_replace('/\[\s+/', '[', $string);
        $string = trim($string);
        return $string;
    }
};
