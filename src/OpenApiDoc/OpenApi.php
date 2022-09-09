<?php
declare(strict_types=1);

namespace Timebug\ApiCtl\OpenApiDoc;

class OpenApi
{
    /**
     * @var string
     */
    private string $openApi = "3.0.1";

    /**
     * @var array|string[]
     */
    private array $info = [
        "title" => "API文档",
        "description" => "",
        "version" => "1.0.0",
    ];

    /**
     * @var array
     */
    private array $tags = [];

    /**
     * @var array
     */
    private array $paths = [];

    /**
     * @var array
     */
    private array $components = [];

    public function addTag(string $name = ''): static
    {
        if (isset($this->tags[$name])) {
            return $this;
        }
        $this->tags[$name] = ['name' => $name];
        return $this;
    }

    public function addPath(string $path, array $data = []): static
    {
        $this->paths[$path] = $data;
        return $this;
    }

    public function addSchema(string $name, array $schema): static
    {
        $this->components["schemas"][$name] = $schema;
        return $this;
    }

    public function format(): array
    {
        return [
            "openapi" => $this->openApi,
            "info" => $this->info,
            "tags" => array_values($this->tags),
            "paths" => $this->paths,
            "components" => $this->components,
        ];
    }
}