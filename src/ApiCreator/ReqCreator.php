<?php
declare(strict_types=1);

namespace Timebug\ApiCtl\ApiCreator;


use ReflectionClass;
use ReflectionException;
use Timebug\ApiCtl\ApiParse\ReqParser;
use Timebug\ApiCtl\ApiTemplate\Template;

class ReqCreator
{
    private ReqParser $parser;

    private string $template;

    public function __construct(ReqParser $parser)
    {
        $this->parser = $parser;
    }

    public function handle(): void
    {
        $path = $this->parser->getPath();
        is_dir($path) || mkdir($path, 0777, true);
        $props = $this->parser->getProps();
        $getters = $this->parser->getGetterItems();

        $action = "create";
        foreach ($props as $clsName => $prop) {
            $fullClsName = $this->parser->getNamespace() . '\\' . $clsName;
            $filename = rtrim($this->parser->getPath(), '/') . '/' . $clsName . '.php';
            $getter = $getters[$clsName];
            if (@class_exists($fullClsName)) {
                $action = "modify";
                $data = file_get_contents($filename);
                $clsProps = $this->getClassProps($fullClsName);
                $appendProps = [];
                $appendGetter = [];
                foreach ($prop as $name => $body) {
                    if (in_array($name, $clsProps)) continue;
                    $appendProps[] = $body;
                    $appendGetter[] = $getter[$name];
                }

                $appendProps && $data = str_replace(
                    Template::REQUEST_PROPERTY_DEFINE,
                    join("\n", $appendProps) . "\n" . Template::REQUEST_PROPERTY_DEFINE,
                    $data
                );
                $appendGetter && $data = substr(trim($data), 0, strlen(trim($data)) - 1) . join("\n", $appendGetter) . "\n}\n";
            } else {
                $data = str_replace([
                    '{{REQ_NAME}}',
                    '{{REQUEST_PARAMS}}',
                    '{{REQUEST_GETTER}}',
                ], [
                    $clsName,
                    join("\n", $prop),
                    join("\n", $getter),
                ], $this->template);
            }

            file_put_contents($filename, $data);
            echo "$action $filename successfully.\n";
        }
    }

    public function setTemplate(string $template): static
    {
        $this->template = $template;
        return $this;
    }

    public function getClassProps(string $className): array
    {
        try {
            $ref = new ReflectionClass($className);
        } catch (ReflectionException $e) {
            echo "create reqClass {$className} error: {$e->getMessage()}.\n";
            return [];
        }
        $props = [];
        foreach ($ref->getProperties() as $property) {
            $props[] = $property->getName();
        }
        return $props;
    }
}