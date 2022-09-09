<?php
declare(strict_types=1);

namespace Timebug\ApiCtl\ApiParse;

use Timebug\ApiCtl\Config\ConfigFactory;

class DomainServiceParser
{
    private string $content;

    private string $className;

    private string $path;

    private array $fileNames;

    private string $classNamespace;

    private array $imports;


    public function init(string $module, string $content): static
    {
        $ctlConfig = ConfigFactory::getConfig();
        $this->content = $content;
        $this->classNamespace = str_replace('/', '\\', ucfirst(ltrim($ctlConfig->getDomainPath(), '/')))  . '\\'. ucfirst($module) . '\\Service';
        $this->path = BASE_PATH . $ctlConfig->getDomainPath() . '/' . ucfirst($module) . '/Service/';
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
            $routeItem = explode(" ", $route[0]);

            $req  = trim(str_replace(['(', ')', "\n"], ['', '', ''], $routeItem[2]));
            $domain = substr($req, 0, strlen($req) - 3);
            $domain = $this->getDomain($domain);
            $domainService = "{$domain}DomainService";
            $this->className = $domainService;
            $this->fileNames[$domainService] = $this->path . $this->className . '.php';

            $resp = trim(str_replace(['(', ')', "\n"], ['', '', ''], $routeItem[4]));
            $this->imports[$domainService]["req"] = $req;
            $this->imports[$domainService]["resp"] = $resp;
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

    public function getFilename(string $className): string
    {
        return $this->fileNames[$className] ?? '';
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getImports(): array
    {
        return $this->imports;
    }
}