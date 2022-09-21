<?php
declare(strict_types=1);

namespace Timebug\ApiCtl\ApiParse;

use Hyperf\Utils\Collection;
use Timebug\ApiCtl\Config\ConfigFactory;

class RespParser
{
    private string $content;

    private array $classNames;

    private string $path;

    private array $fileNames;

    private string $classNamespace;

    private array $props;

    private array $apiProps;

    private array $setterItems;


    public function init(string $module, string $content): static
    {
        $ctlConfig = ConfigFactory::getConfig();
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

        $respItems = [];
        foreach ($arr[0] as $item) {
            preg_match('/[g|p][e|o][t|s]t?.+/', $item, $route);
            $routeItem = explode(" ", $route[0]);

            $resp = trim(str_replace(['(', ')', "\n"], ['', '', ''], $routeItem[4]));
            if (!$resp) { continue; }
            $respItems[] = $resp;
            $this->classNames[$resp] = $resp;
            $this->fileNames[$resp]  = $this->path . $resp . '.php';
        }
        $this->parseRespItems($respItems);
    }

    private function parseRespItems(array $respItems): void
    {
        foreach ($respItems as $resp) {
            $pattern = '/' . $resp . " \{([\s\S]+?)\}/";
            preg_match($pattern, $this->content, $matches);
            if (empty($matches)) { continue; }
            $match = end($matches);
            $params = explode("\n", $match);

            foreach ($params as $param) {
                if (trim($param) == "") continue;
                $paramItems = explode(" ", $param);
                $paramItems = array_values(array_filter($paramItems));

                $propName = $paramItems[0];

                preg_match('/.*`(.*)`/', $param, $docText);
                $paramsDoc = $this->parseParamDoc($docText[1]);

                $json = $paramsDoc['json'];
                $varName = $paramItems[1];
                $typeName = '';
                if (str_contains($varName, "[]")) {
                    $typeName = ltrim($varName, "[]");
                    $varName = $typeName . '[]|array';
                }

                $type = $paramItems[1];
                $typeText = "";
                if (!in_array($paramItems[1], $this->baseType())) {
                    if (str_contains($paramItems[1], "[]")) {
                        $typeName = ltrim($paramItems[1], "[]");
                        $type = 'array';
                    } else {
                        $typeName = $paramItems[1];
                    }
                    $typeText = ", type: $typeName::class";
                    $this->parseRespItems([$typeName]);
                }
                $type = $this->typeChange($type);

                $this->props[$resp][$propName] = "
    /**
     * @var $varName
     */
    #[RespMapper(json: \"$json\"$typeText)]
    protected $type \${$propName};
";
                $setterMethod = 'set' . ucfirst($propName);
                $this->setterItems[$resp][$propName] = "
    /**
     * @param $type \$$propName
     * @return static
     */
     public function $setterMethod($type \$$propName): static
     {
         \$this->$propName = \$$propName;
         return \$this;
     }
";

                $showType = $typeName == "" ? $type : $typeName;
                $this->apiProps[$resp][$propName] = [
                    "type" => $type == "int" ? "integer" : $type,
                    'json' => $json,
                    "typeName" => $showType,
                    'propName' => $propName,
                    'desc' => $paramsDoc['desc'] ?? '',
                ];
            }
        }
    }

    private function parseParamDoc($doc): array
    {
        $docArray = explode(" ", $doc);
        try {
            return Collection::make($docArray)->reduce(function ($carry, $item) {
                if (!str_contains($item, ':')) {
                    throw new \Exception("api格式错误");
                }
                list($k, $v) = explode(':', trim($item, '`'));
                $carry[$k] = trim($v, '"');
                return $carry;
            }, []);
        } catch(\Exception $e) {
            echo "api格式错误, 在 {$doc} 可能包含无效空格或冒号，请检查(desc中使用中文冒号，不允许有空格)。\n";die;
        }
    }

    private function baseType(): array
    {
        return ["int", "int64", "int32", "string", "float64", "float", "float32", "bool"];
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

    public function getClassName(string $resp): string
    {
        return $this->classNames[$resp];
    }

    public function getNamespace(): string
    {
        return $this->classNamespace;
    }

    public function getFilename(string $resp): string
    {
        return $this->fileNames[$resp];
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

    public function getSetterItems(): array
    {
        return $this->setterItems;
    }
}