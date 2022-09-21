<?php
declare(strict_types=1);

namespace Timebug\ApiCtl\ApiParse;

use Timebug\ApiCtl\Config\ApiCtlConfig;

class ControllerParser
{
    private string $content;

    private string $className;

    private string $path;

    private string $fileName;

    private string $classNamespace;

    private array $imports;

    private array $methods;

    private array $apis;

    public function __construct(protected ApiCtlConfig $config)
    {

    }

    public function init(string $module, string $content): static
    {
        $ctlConfig = $this->config;
        $this->content = $content;
        $this->classNamespace = str_replace('/', '\\', ucfirst(ltrim($ctlConfig->getControllerPath(), '/')));
        $this->className = ucfirst($module) . 'Controller';
        $this->path = BASE_PATH . $ctlConfig->getControllerPath() . '/';
        $this->fileName  = $this->path . $this->className . '.php';
        $this->parse();
        return $this;
    }

    private function parse(): void
    {
        preg_match('/service [a-zA-Z]+ {([^}]+)}/', $this->content, $matches);
        $match = end($matches);

        preg_match_all('/@doc (.+\n.+\n.+)/', $match, $arr);

        foreach ($arr[0] as $item) {
            preg_match('/[g|p][e|o][t|s]t?.+/', $item, $route);
            $routeItem = explode(" ", $route[0]);

            preg_match('/@handler (.+)/', $item, $handles);

            $handle = end($handles);
            $handle = trim($handle);

            $req  = trim(str_replace(['(', ')', "\n"], ['', '', ''], $routeItem[2]));
            $this->imports[$handle][$req] = $req;

            $handler = trim($routeItem[1], '/');
            $this->methods[$handle] = "
    #[RequestMapping(path: \"{$handler}\", methods: \"{$routeItem[0]}\")]
    public function {$handle}($req \$req, ResponseInterface \$resp): Psr7ResponseInterface
    {
        \$req->parse();
        \$data = \$this->service->{$handle}(\$req);
        return Resp::success(\$resp, \$data);
    }";
            $resp = trim(str_replace(['(', ')', "\n"], ['', '', ''], $routeItem[4]));
            preg_match('/@doc "(.+)"/', $item, $docs);
            $doc = end($docs);
            $this->apis[$handle] = [
                "api" => $handle,
                "path" => $handler,
                "method" => $routeItem[0],
                "req" => $req,
                "resp" => $resp,
                "doc" => $doc,
            ];
        }
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getNamespace(): string
    {
        return $this->classNamespace;
    }

    public function getFilename(): string
    {
        return $this->fileName;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getImports(): array
    {
        return $this->imports;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getApis(): array
    {
        return $this->apis;
    }
}