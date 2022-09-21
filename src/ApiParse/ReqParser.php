<?php
declare(strict_types=1);

namespace Timebug\ApiCtl\ApiParse;

use Hyperf\Utils\Collection;
use Timebug\ApiCtl\Config\ApiCtlConfig;

class ReqParser
{
    private string $content;

    private array $classNames;

    private string $path;

    private array $fileNames;

    private string $classNamespace;

    private array $props;

    private array $apiProps;

    private array $getterItems;


    public function __construct(protected ApiCtlConfig $config)
    {

    }


    public function init(string $module, string $content): static
    {
        $ctlConfig = $this->config;
        $this->content = $content;
        $this->classNamespace = str_replace('/', '\\', ucfirst(ltrim($ctlConfig->getTypesPath(), '/'))) . '\\' . ucfirst($module);
        $this->path = BASE_PATH . $ctlConfig->getTypesPath() . '/' . ucfirst($module) . '/';
        $this->parse();
        return $this;
    }

    private function parse(): void
    {
        preg_match('/service [a-zA-Z]+ {([^}]+)}/', $this->content, $matches);
        $match = end($matches);

        preg_match_all('/@doc (.+\n.+\n.+)/', $match, $arr);

        $reqItems = [];
        foreach ($arr[0] as $item) {
            preg_match('/[g|p][e|o][t|s]t?.+/', $item, $route);
            $routeItem = explode(" ", $route[0]);

            $req  = trim(str_replace(['(', ')', "\n"], ['', '', ''], $routeItem[2]));
            $reqItems[] = $req;
            $this->classNames[$req] = $req;
            $this->fileNames[$req]  = $this->path . $req . '.php';
        }

        foreach ($reqItems as $req) {
            $pattern = '/' . $req . " \{([\s\S]+?)\}/";
            preg_match($pattern, $this->content, $matches);
            $match = end($matches);
            $params = explode("\n", $match);

            foreach ($params as $param) {
                if (trim($param) == "") continue;
                $paramItems = explode(" ", $param);
                $paramItems = array_values(array_filter($paramItems));
                $propName = $paramItems[0];

                $typeName = $paramItems[1];
                $type = str_contains($typeName, "[]") ? 'array' : $typeName;
                $type = $this->typeChange($type);

                if (str_contains($typeName, "[]")) {
                    $typeName = ltrim($paramItems[1], "[]") . '[]|array';
                }
                preg_match('/.*`(.*)`/', $param, $docText);
                $paramsDoc = $this->parseParamDoc($docText[1]);

                $json = $paramsDoc['json'];
                $json = str_replace(",optional", "", $json);
                $isRequired = str_contains($paramsDoc['json'], ",optional") === false;
                $requiredRule = $isRequired ? "#[Validator(rule: \"required\", message: \"{$propName} 不能为空\")]" : "";
                $integerRule = $type == "int" ? "#[Validator(rule: \"integer\", message: \"{$propName} 必须为数值\")]" : "";
                $reqMapper = "#[ReqMapper(json: \"$json\")]";

                $rule = $requiredRule && $integerRule ? "$requiredRule
    $integerRule" : "{$requiredRule}{$integerRule}";

                $desc = $paramsDoc["desc"] ?? "";
                $defaultValue = "";
                if (isset($paramsDoc["default"])) {
                    if ($type == "string") {
                        $defaultValue = '"' . $paramsDoc["default"] . '"';
                    } else {
                        $defaultValue = $paramsDoc["default"];
                    }
                }
                $paramDefault = $defaultValue != '' ? "?$type \$default = $defaultValue" : "?$type \$default = null";
                $propDefault = $defaultValue != '' ? " = $defaultValue" : "";
                $this->props[$req][$propName] = "
    /**
     * $desc
     * @var $typeName
     */
    $rule
    $reqMapper
    protected $type \${$propName}{$propDefault};";

                $getterMethod = 'get' . ucfirst($propName);
                $this->getterItems[$req][$propName] = "
    /**
     * @param ?$type \$default
     * @return ?$type
     */
     public function $getterMethod($paramDefault): ?$type
     {
         return \$this->{$propName} ?? \$default;
     }
";

                $this->apiProps[$req][$propName] = [
                    'prop' => $propName,
                    'type' => $type == "int" ? "integer" : $type,
                    'propName' => $json,
                    'typeName' => $typeName,
                    "required" => $isRequired,
                    "desc" => $desc,
                    "default" => $paramsDoc["default"] ?? null,
                ];

            }
        }
    }

    private function parseParamDoc($doc): array
    {
        $docArray = explode(" ", $doc);
        return Collection::make($docArray)->reduce(function($carry, $item) {
            list($k, $v) = explode(':', trim($item, '`'));
            $carry[$k] = trim($v, '"');
            return $carry;
        }, []);
    }

    private function baseType(): array
    {
        return ["int", "int64", "int32", "string", "float64", "float", "float32"];
    }

    private function typeChange(string $type): string
    {
        $map = [
            'int64'   => 'int',
            'int32'   => 'int',
            'int16'   => 'int',
            'int8'    => 'int',
            'uint64'  => 'int',
            'uint32'  => 'int',
            'uint16'  => 'int',
            'uint8'   => 'int',
            'float64' => 'float',
            'float32' => 'float',
        ];
        return $map[$type] ?? $type;
    }

    public function getClassName(string $req): string
    {
        return $this->classNames[$req] ?? "";
    }

    public function getNamespace(): string
    {
        return $this->classNamespace;
    }

    public function getFilename(string $req): string
    {
        return $this->fileNames[$req] ?? "";
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getProps(): array
    {
        return $this->props;
    }

    public function getApiProps(): array
    {
        return $this->apiProps;
    }

    public function getGetterItems(): array
    {
        return $this->getterItems;
    }
}