<?php

namespace PhpMx;

use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;

/**
 * Classe utilitária para extração e análise de esquemas de arquivos do phpMx via Reflection.
 * Suporta leitura de helpers, comandos, middlewares, rotas e classes do projeto.
 */
abstract class ReflectionMxFile
{
    /**
     * Retorna os esquemas das constantes, funções e environments de um arquivo de helper.
     * @param string $file Caminho do arquivo a ser analisado.
     * @return array[]
     */
    static function helperFile(string $file): array
    {
        $content = Import::content($file);
        $schemes = [];

        //Constantes
        preg_match_all('/^\s*define\s*\(\s*[\'"]([\w_]+)[\'"]\s*,/im', $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        foreach ($matches as $match) {
            $constantName = $match[1][0];
            $pos = $match[0][1];

            $docBlock = self::docBlockBefore($content, $pos);
            $docScheme = self::parseDocBlock($docBlock, ['description', 'examples', 'see', 'internal', 'context']);

            $schemes[] = [
                'key' => "constant:$constantName",
                'typeKey' => 'constant',

                'name' => $constantName,

                'origin' => Path::origin($file),
                'file' => $file,
                'line' => substr_count(substr($content, 0, $pos), "\n") + 1,

                ...$docScheme
            ];
        }

        //Funções
        preg_match_all('/^\s*function\s+(\w+)/im', $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        foreach ($matches as $match) {
            $functionName = $match[1][0];

            $reflection = new ReflectionFunction($functionName);
            $docBlock = $reflection->getDocComment();
            $docScheme = self::parseDocBlock($docBlock, ['description', 'params', 'return', 'examples', 'see', 'internal', 'context']);

            foreach ($reflection->getParameters() as $p) {
                $name = $p->getName();
                $type = $p->hasType() ? strval($p->getType()) : null;
                $optional = $p->isOptional();
                $default = $p->isDefaultValueAvailable() ? $p->getDefaultValue() : null;
                $reference = $p->isPassedByReference();

                $docScheme['params'][$name] = $docScheme['params'][$name] ?? ['name' => $name, 'description' => []];

                $docScheme['params'][$name]['type'] = $docScheme['params'][$name]['type'] ?? $type ?? null;
                $docScheme['params'][$name]['optional'] = $optional;
                $docScheme['params'][$name]['default'] = $default;
                $docScheme['params'][$name]['reference'] = $reference;
            }

            $returnType = $reflection->hasReturnType() ? strval($reflection->getReturnType()) : null;
            $docScheme['return'] = $docScheme['return'] ?? $returnType ?? null;

            $schemes[] = [
                'key' => "function:$functionName",
                'typeKey' => 'function',

                'name' => $functionName,

                'origin' => Path::origin($file),
                'file' => $reflection->getFileName(),
                'line' => $reflection->getStartLine(),

                ...$docScheme,
            ];
        }

        //Environments
        preg_match_all('/^\s*Env::default\s*\(\s*[\'"]([\w_]+)[\'"]\s*,\s*/im', $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        foreach ($matches as $match) {
            $environmentsName = $match[1][0];
            $pos = $match[0][1];

            $docBlock = self::docBlockBefore($content, $pos);
            $docScheme = self::parseDocBlock($docBlock, ['description', 'see', 'examples', 'internal', 'context']);
            $docScheme['see'][] = 'env()';
            $docScheme['examples'][] = "env('$environmentsName');";

            $schemes[] = [
                'key' => "environment:$environmentsName",
                'typeKey' => 'environment',

                'name' => $environmentsName,

                'origin' => Path::origin($file),
                'file' => $file,
                'line' => substr_count(substr($content, 0, $pos), "\n") + 1,

                ...$docScheme
            ];
        }

        return $schemes;
    }

    /**
     * Retorna o esquema de um arquivo de comando de terminal.
     * @param string $file Caminho do arquivo a ser analisado.
     * @return array
     */
    static function commandFile(string $file): array
    {
        $content = Import::content($file);

        preg_match('/(?:return|.*?=)\s*new\s+class/i', $content, $match, PREG_OFFSET_CAPTURE);

        if (!$match) return [];

        $pos = $match[0][1];

        $docBlock = self::docBlockBefore($content, $pos);
        $docScheme = self::parseDocBlock($docBlock, ['description', 'params', 'return', 'examples', 'see', 'internal', 'context']);

        $command = explode('system/terminal/', $file);
        $command = array_pop($command);
        $command = substr($command, 0, -4);
        $command = str_replace(['/', '\\'], '.', $command);

        preg_match('/function\s+__invoke\s*\((.*?)\)/s', $content, $invokeMatch);
        if (!empty($invokeMatch[1])) {
            $paramsStr = trim($invokeMatch[1]);
            if ($paramsStr !== '') {
                preg_match_all('/(?:([^\s,$]+)\s+)?(&)?\$(\w+)(?:\s*=\s*([^,]+))?/', $paramsStr, $paramMatches, PREG_SET_ORDER);
                foreach ($paramMatches as $p) {
                    $name = trim($p[3]);
                    $type = !empty($p[1]) ? trim($p[1]) : null;
                    $optional = !empty($p[4]);
                    $default = $optional ? trim($p[4]) : null;
                    $reference = !empty($p[2]);

                    $docScheme['params'][$name] = $docScheme['params'][$name] ?? ['name' => $name,  'description' => []];

                    $docScheme['params'][$name]['type'] = $docScheme['params'][$name]['type'] ?? $type ?? null;
                    $docScheme['params'][$name]['optional'] = $optional;
                    $docScheme['params'][$name]['default'] = $default;
                    $docScheme['params'][$name]['reference'] = $reference;
                }
            }
        }

        $docScheme['context'] = $docScheme['context'] ?? 'cli';

        return [
            'key' => "command:$command",
            'typeKey' => 'command',

            'name' => $command,

            'origin' => Path::origin($file),
            'file' => $file,
            'line' => substr_count(substr($content, 0, $pos), "\n") + 1,

            ...$docScheme,
        ];
    }

    /**
     * Retorna o esquema de um arquivo de middleware.
     * @param string $file Caminho do arquivo a ser analisado.
     * @return array
     */
    static function middlewareFile(string $file): array
    {
        $content = Import::content($file);

        preg_match('/(?:return|.*?=)\s*new\s+class/i', $content, $match, PREG_OFFSET_CAPTURE);

        if (!$match) return [];

        $pos = $match[0][1];

        $docBlock = self::docBlockBefore($content, $pos);
        $docScheme = self::parseDocBlock($docBlock, ['description', 'see', 'internal', 'context']);

        $docScheme['context'] = $docScheme['context'] ?? 'http';

        $middleware = explode('system/middleware/', $file);
        $middleware = array_pop($middleware);
        $middleware = substr($middleware, 0, -4);
        $middleware = str_replace(['/', '\\'], '.', $middleware);

        return [
            'key' => "middleware:$middleware",
            'typeKey' => 'middleware',

            'name' => $middleware,

            'origin' => Path::origin($file),
            'file' => $file,
            'line' => substr_count(substr($content, 0, $pos), "\n") + 1,

            ...$docScheme,
        ];
    }

    /**
     * Retorna os esquemas das rotas declaradas em um arquivo de router.
     * @param string $file Caminho do arquivo a ser analisado.
     * @return array[]
     */
    static function routerFile(string $file): array
    {
        $schemes = [];

        /** @var Router|mixed $interceptor */
        $interceptor = new class extends Router {
            function intercept(string $file): array
            {
                $ROUTE = self::$ROUTE;
                $CURRENT_MIDDLEWARE = self::$CURRENT_MIDDLEWARE;
                $CURRENT_PATH = self::$CURRENT_PATH;
                $SCANNED = self::$SCANNED;
                Import::only($file);
                $intercepted = self::$ROUTE;
                self::$ROUTE = $ROUTE;
                self::$CURRENT_MIDDLEWARE = $CURRENT_MIDDLEWARE;
                self::$CURRENT_PATH = $CURRENT_PATH;
                self::$SCANNED = $SCANNED;
                return $intercepted;
            }
        };

        foreach ($interceptor->intercept($file) as $method => $routes)
            foreach ($routes as $route)
                $schemes[] = [
                    'path' => $route[0],

                    'middlewares' => $route[3] ?? [],
                    'method' => $method,
                    'response' => self::extractRouteReponse($route[1]),

                    'origin' => Path::origin($file),
                    'file' => $file,
                    'line' => null,
                ];

        return $schemes;
    }

    /**
     * Retorna o esquema de um arquivo de recurso (class, interface ou trait).
     * @param string $file Caminho do arquivo a ser analisado.
     * @return array
     */
    static function sourceFile(string $file): array
    {
        $content = Import::content($file);

        preg_match('/namespace\s+([\w\\\\]+);/m', $content, $nsMatch);
        preg_match('/^\s*(?:abstract\s+|final\s+)?(?:class|trait|interface)\s+(\w+)/im', $content, $nameMatch);

        if (!$nameMatch) return [];

        $reflection = new ReflectionClass(trim($nsMatch[1] . '\\' . $nameMatch[1], '\\'));

        $sourceName = $reflection->getName();

        $type = 'class';
        if ($reflection->isInterface()) $type = 'interface';
        if ($reflection->isTrait()) $type = 'trait';

        $extends = $reflection->getParentClass() ? $reflection->getParentClass()->getName() : null;
        $interface = $reflection->getInterfaceNames();
        $traits = $reflection->getTraitNames();

        $docBlock = $reflection->getDocComment();
        $docScheme = self::parseDocBlock($docBlock, ['description', 'examples', 'methods', 'properties', 'see', 'internal', 'context']);

        $constants = self::extractConstantsReflection($reflection);

        foreach (self::extractPropertiesReflection($reflection) as $prop) {
            $key = $prop['name'];
            if (isset($docScheme['properties'][$key])) {
                $docProp = $docScheme['properties'][$key];
                $prop['type'] = $docProp['type'] ?? $prop['type'] ?? null;
                $prop['description'] = $docProp['description'] ?? $prop['description'] ?? [];
            }
            $docScheme['properties'][$key] = $prop;
        }

        foreach (self::extractMethodsReflection($reflection) as $method) {
            $key = $method['name'];

            if (isset($docScheme['methods'][$key])) {
                $docMethod = $docScheme['methods'][$key];

                $method['description'] = $docMethod['description'] ?? $method['description'] ?? [];

                $method['return'] = $docMethod['return'] ?? $method['return'] ?? null;

                foreach ($method['params'] as $pName => $param) {
                    if (isset($docMethod['params'][$pName])) {
                        $method['params'][$pName]['type'] = $docMethod['params'][$pName]['type'] ?? $param['type'] ?? null;
                        $method['params'][$pName]['description'] = $docMethod['params'][$pName]['description'] ?? null;
                    }
                }
            }

            $docScheme['methods'][$key] = $method;
        }

        return [
            'key' => "source:$sourceName",
            'typeKey' => $type,

            'name' => $sourceName,

            'origin' => Path::origin($file),
            'file' => $reflection->getFileName(),
            'line' => $reflection->getStartLine(),

            'abstract' => $reflection->isAbstract(),
            'final' => $reflection->isFinal(),
            'extends' => $extends,
            'interface' => $interface,
            'traits' => $traits,
            'constants' => $constants,

            ...$docScheme,
        ];
    }

    protected static function docBlockBefore(string $code, int $pos): string
    {
        $before = substr($code, 0, $pos);
        if (preg_match_all('/\/\*\*(?:[^*]|\*(?!\/))*?\*\//s', $before, $matches, PREG_OFFSET_CAPTURE)) {
            $lastMatch = end($matches[0]);
            $lastDoc = $lastMatch[0];
            $lastPos = $lastMatch[1] + strlen($lastDoc);
            $between = substr($before, $lastPos, $pos - $lastPos);
            if (preg_match('/^[\s\n\r\t\$\w=;]*$/', $between)) return $lastDoc;
        }
        return '';
    }

    protected static function parseDocBlock(?string $docBlock, array $keys = []): array
    {
        $data = [
            'description' => [],
            'params' => [],
            'return' => null,
            'context' => null,
            'examples' => [],
            'methods' => [],
            'properties' => [],
            'internal' => false,
            'see' => []
        ];

        if (!empty($docBlock) && str_starts_with(trim($docBlock), '/**')) {
            $clean = preg_replace(['/^\/\*\*/', '/\*\//', '/^\s*\*\s?/m'], '', $docBlock);
            $lines = explode("\n", trim($clean));
            $currentTag = null;

            foreach ($lines as $line) {
                $trimmedLine = trim($line);
                if (preg_match('/^@([a-zA-Z0-9_-]+)\b/', $trimmedLine, $m)) {
                    $tag = $m[1];
                    $content = trim(substr($trimmedLine, strlen($m[0])));
                    $currentTag = $tag;

                    if ($tag == 'internal')
                        $data['internal'] = true;

                    if ($tag == 'category' || $tag == 'context')
                        $data['context'] = $content !== '' ? $content : null;

                    if ($tag == 'see')
                        if ($content !== '') $data['see'][] = $content;

                    if ($tag == 'param')
                        if (preg_match('/^([^\s]+)\s+\$(\w+)\s*(.*)$/', $content, $pm))
                            $data['params'][$pm[2]] = ['type' => $pm[1], 'description' => trim($pm[3])];

                    if ($tag == 'return')
                        if (preg_match('/^([^\s]+)\s*(.*)$/', $content, $rm))
                            $data['return'] = $rm[1];

                    if ($tag == 'method')
                        if (preg_match('/^(static\s+)?([^\s]+)\s+(\w+)\((.*?)\)\s*(.*)$/', $content, $mm)) {
                            $params = [];
                            if (!empty($mm[4])) {
                                $argList = array_map('trim', explode(',', $mm[4]));
                                foreach ($argList as $arg) {
                                    if (preg_match('/^([^\s]+)\s+(&)?\$(\w+)(?:\s*=\s*(.+))?/', $arg, $pm)) {
                                        $hasDefault = isset($pm[4]);
                                        $params[$pm[3]] = [
                                            'name' => $pm[3],
                                            'type' => $pm[1],
                                            'optional' => $hasDefault,
                                            'default' => $hasDefault ? trim($pm[4]) : null,
                                            'reference' => !empty($pm[2]),
                                        ];
                                    }
                                }
                            }
                            $data['methods'][$prm[2]] = [
                                'name' => $mm[3],
                                'static' => !empty($mm[1]),
                                'return' => $mm[2],
                                'params' => $params,
                                'description' => trim($mm[5])
                            ];
                        }

                    if ($tag == 'property')
                        if (preg_match('/^([^\s]+)\s+\$(\w+)\s*(.*)$/', $content, $prm)) {
                            $data['properties'][$prm[2]] = [
                                'name' => $prm[2],
                                'type' => $prm[1],
                                'description' => trim($prm[3])
                            ];
                        }


                    if ($tag == 'example')
                        $data['examples'][] = [$content];
                } else {
                    if ($currentTag === 'example' && !empty($data['examples']))
                        $data['examples'][count($data['examples']) - 1][] = $line;

                    if ($currentTag === null && $trimmedLine !== '')
                        $data['description'][] = $trimmedLine;
                }
            }
        }

        if (count($keys)) $data = array_intersect_key($data, array_flip($keys));

        return $data;
    }

    protected static function extractConstantsReflection(ReflectionClass $reflection): array
    {
        $constants = [];

        foreach ($reflection->getReflectionConstants() as $const) {

            if ($const->getDeclaringClass()->getName() !== $reflection->getName()) continue;

            $declaringClass = $const->getDeclaringClass();
            $class = $declaringClass->getName();
            $name = $const->getName();
            $file = $declaringClass->getFileName();
            $visibility = $const->isPublic() ? 'public' : ($const->isProtected() ? 'protected' : 'private');

            $line = null;
            foreach (file($file) as $lineNumber => $lineContent)
                if (preg_match('/const\s+' . $const->getName() . '\s*[=;]/', $lineContent))
                    $line = $lineNumber + 1;

            $docBlock = $const->getDocComment();
            $docScheme = self::parseDocBlock($docBlock, ['description', 'examples', 'see', 'internal', 'context']);

            $constants[$name] = [
                'name' => $const->getName(),

                'visibility' => $visibility,

                'file' => $file,
                'line' => $line,

                ...$docScheme
            ];
        }
        return $constants;
    }

    protected static function extractPropertiesReflection(ReflectionClass $reflect): array
    {
        $props = [];
        foreach ($reflect->getProperties() as $prop) {

            if ($prop->getDeclaringClass()->getName() !== $reflect->getName()) continue;

            $declaringClass = $prop->getDeclaringClass();
            $class = $declaringClass->getName();
            $name = $prop->getName();
            $file = $declaringClass->getFileName();
            $visibility = $prop->isPublic() ? 'public' : ($prop->isProtected() ? 'protected' : 'private');
            $type = $prop->hasType() ? strval($prop->getType()) : null;

            $line = null;
            foreach (file($file) as $lineNumber => $lineContent)
                if (preg_match('/\$' . $prop->getName() . '\s*[;=]/', $lineContent))
                    $line = $lineNumber + 1;

            $docBlock = $prop->getDocComment();
            $docScheme = self::parseDocBlock($docBlock, ['description', 'examples', 'see', 'internal', 'context']);

            $props[$name] = [
                'name' => $prop->getName(),

                'type' => $type,

                'static' => $prop->isStatic(),
                'visibility' => $visibility,

                'file' => $file,
                'line' => $line,

                ...$docScheme
            ];
        }
        return $props;
    }

    protected static function extractMethodsReflection(ReflectionClass $reflect): array
    {
        $methods = [];

        foreach ($reflect->getMethods() as $method) {

            if ($method->getDeclaringClass()->getName() !== $reflect->getName()) continue;

            $declaringClass = $method->getDeclaringClass();
            $class = $declaringClass->getName();
            $name = $method->getName();
            $file = $declaringClass->getFileName();

            $visibility = $method->isPublic() ? 'public' : ($method->isProtected() ? 'protected' : 'private');

            $docBlock = $method->getDocComment();
            $docScheme = self::parseDocBlock($docBlock, ['description', 'params', 'return', 'examples', 'see', 'internal', 'context']);

            foreach ($method->getParameters() as $p) {
                $pName = $p->getName();
                $type = $p->hasType() ? strval($p->getType()) : null;

                $docScheme['params'][$pName] = $docScheme['params'][$pName] ?? ['name' => $pName, 'description' => []];

                $docScheme['params'][$pName]['type'] = $docScheme['params'][$pName]['type'] ?? $type ?? null;
                $docScheme['params'][$pName]['optional'] = $p->isOptional();
                $docScheme['params'][$pName]['default'] = $p->isDefaultValueAvailable() ? $p->getDefaultValue() : null;
                $docScheme['params'][$pName]['reference'] = $p->isPassedByReference();
            }

            $returnType = $method->hasReturnType() ? strval($method->getReturnType()) : null;
            $docScheme['return'] = $docScheme['return'] ?? $returnType ?? null;

            $methods[$name] = [
                'name' => $name,

                'visibility' => $visibility,
                'static' => $method->isStatic(),
                'abstract' => $method->isAbstract(),
                'final' => $method->isFinal(),

                'file' => $file,
                'line' => $method->getStartLine(),

                ...$docScheme,
            ];
        }

        return $methods;
    }

    protected static function extractRouteReponse($response): array
    {
        if (is_int($response))
            return ['type' => 'status', 'code' => $response, 'description' => ''];

        $parts = is_array($response) ? $response : [$response];
        $controller = array_shift($parts);
        $method = array_shift($parts) ?? '__invoke';

        $info = [
            'type' => 'class',
            'class' => $controller,
            'method' => $method,
            'callable' => false,
            'file' => null,
            'line' => null,
        ];

        if (class_exists($controller)) {
            if (method_exists($controller, $method)) {
                $refMethod = new ReflectionMethod($controller, $method);
                $info['file'] = path($refMethod->getFileName());
                $info['line'] = $refMethod->getStartLine();
                $info['callable'] = true;
            } else {
                $reflection = new ReflectionClass($controller);
                $info['file'] = path($reflection->getFileName());
                $info['line'] = $reflection->getStartLine();
            }
        }

        return $info;
    }
}
