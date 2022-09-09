<?php
declare(strict_types=1);

namespace Timebug\ApiCtl\ApiParse;

use Timebug\ApiCtl\Config\ConfigFactory;

class ServiceParser
{
    private string $content;

    private string $className;

    private string $path;

    private string $fileName;

    private string $classNamespace;

    private array $imports;

    private array $methods;


    public function init(string $module, string $content): static
    {
        $ctlConfig = ConfigFactory::getConfig();
        $this->content = $content;
        $this->classNamespace = str_replace('/', '\\', ucfirst(trim($ctlConfig->getServicePath(), '/')));
        $this->className = ucfirst($module) . 'Service';
        $this->path = BASE_PATH . $ctlConfig->getServicePath();
        $this->fileName  = $this->path . $this->className . '.php';
        $this->parse();
        return $this;
    }

    private function parse(): void
    {
        preg_match('/service [a-zA-Z]+ {([^}]+)}/', $this->content, $matches);
        $match = end($matches);

        preg_match_all('/@doc (.+\n.+\n.+)/', $match, $arr);

        foreach($arr[0] as $item) {

            preg_match('/[g|p][e|o][t|s]t?.+/', $item, $route);
            preg_match('/@handler (.+)/', $item, $handles);

            $routeItem = explode(" ", $route[0]);
            $handle = end($handles);
            $handle = trim($handle);

            $req  = trim(str_replace(['(', ')', "\n"], ['', '', ''], $routeItem[2]));
            $this->imports[$handle][$req] = $req;
            $resp = trim(str_replace(['(', ')', "\n"], ['', '', ''], $routeItem[4]));
            $this->imports[$handle][$resp] = $resp;
            $domain = substr($req, 0, strlen($req) - 3);
            $domain = $this->getDomain($domain);
            $this->imports[$handle][$domain] = "{$domain}DomainService";

            $this->methods[$handle] = "
    public function {$handle}($req \$req): $resp
    {
        return (new {$domain}DomainService())->handle(\$req);
    }";
        }
    }

    private function getDomain(string $domain): string
    {
        if (isset($this->imports["domain"][$domain])) {
            return $this->getDomain($domain . '2');
        }
        return $domain;
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
}