<?php
declare(strict_types=1);

namespace Timebug\ApiCtl\OpenApiDoc;

class Schemas
{
    private string $name = "";

    private array $properties = [];

    private array $orderProps = [];

    private string $folder = "";

    public function setName(string $name = ""): static
    {
        $this->name = $name;
        return $this;
    }

    public function addProperty(string $type, string $prop, array $items = [], string $desc = ""): static
    {
        $this->properties[$prop] = [
            "type" => $type,
            "description" => $desc
        ];
        if ($type == "array" || $type == "object") {
            $this->properties[$prop] = $items;
        }
        $this->orderProps[] = $prop;
        return $this;
    }

    public function setFolder(string $folder): static
    {
        $this->folder = $folder;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @return array
     */
    public function getOrderProps(): array
    {
        return $this->orderProps;
    }

    public function format(): array
    {
        return [
            "type" => "object",
            "properties" => $this->properties,
            "x-apifox-orders" => $this->orderProps,
            "required" => $this->orderProps,
            "x-apifox-ignore-properties" => [],
            "x-apifox-folder" => $this->folder,
        ];
    }
}