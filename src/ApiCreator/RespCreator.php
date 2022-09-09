<?php
declare(strict_types=1);

namespace Timebug\ApiCtl\ApiCreator;


use ReflectionClass;
use ReflectionException;
use Timebug\ApiCtl\ApiParse\RespParser;
use Timebug\ApiCtl\ApiTemplate\Template;

class RespCreator
{
    private RespParser $parser;

    private string $template;

    public function __construct(RespParser $parser)
    {
        $this->parser = $parser;
    }

    public function handle(): void
    {
        $props = $this->parser->getProps();
        $setterItems = $this->parser->getSetterItems();
        foreach ($props as $clsName => $prop) {
            $setterItem = $setterItems[$clsName];
            $fullClsName = $this->parser->getNamespace() . '\\' . $clsName;
            $filename = rtrim($this->parser->getPath(), '/') . '/' . $clsName . '.php';
            if (@class_exists($fullClsName)) {
                $clsProps = $this->getClassProps($fullClsName);
                $data = file_get_contents($filename);
                $appendProps = [];
                $appendSetters = [];
                foreach ($prop as $name => $body) {
                    if (in_array($name, $clsProps)) continue;
                    $appendProps[] = $body;
                    $appendSetters[] = $setterItem[$name];
                }

                $appendProps && $data = str_replace(
                    Template::RESPONSE_PROPERTY_DEFINE,
                    join("\n", $appendProps) . "\n" . Template::RESPONSE_PROPERTY_DEFINE . "\n",
                    $data
                );
                $appendSetters && $data = substr(trim($data), 0, strlen(trim($data)) - 1) . join("\n", $appendSetters) . "\n}\n";
            } else {
                $data = str_replace([
                    '{{RESP_NAME}}',
                    '{{RESPONSE_PARAMS}}',
                    '{{RESPONSE_SETTER}}',
                ], [
                    $clsName,
                    join("\n", $prop),
                    join("\n", $setterItem),
                ], $this->template);
            }

            file_put_contents($filename, $data);
            echo "create $filename successfully.\n";
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