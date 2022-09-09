<?php
declare(strict_types=1);

namespace Timebug\ApiCtl\ApiCreator;

use Timebug\ApiCtl\ApiParse\ControllerParser;
use Timebug\ApiCtl\ApiParse\ReqParser;
use Timebug\ApiCtl\ApiParse\RespParser;

class ControllerCreator
{
    private ControllerParser $parser;

    private ReqParser $reqParser;

    private RespParser $respParser;

    private string $template;

    private string $fullClassName;

    private string $prefix;

    private array $classMethods;

    private array $appendMethods;

    public function __construct(ControllerParser $parser)
    {

        $this->parser = $parser;
        $this->fullClassName = $this->parser->getNamespace() . '\\' . $this->parser->getClassName();
        $this->classMethods  = $this->existsClass() ? get_class_methods($this->fullClassName) : [];
    }

    public function setReqParser(ReqParser $parser): static
    {
        $this->reqParser = $parser;
        return $this;
    }

    public function setRespParser(RespParser $parser): static
    {
        $this->respParser = $parser;
        return $this;
    }

    public function setTemplate(string $template):static
    {
        $this->template = $template;
        return $this;
    }

    public function setPrefix(string $prefix): static
    {
        $this->prefix = $prefix;
        return $this;
    }


    public function handle(): void
    {
        return;
        $this->mkDirIfNotExists();

        $parserImports = $this->parser->getImports();
        $imports = [];
        foreach ($this->parser->getMethods() as $method => $body) {
            if ($this->existsClassMethod($method)) {
                continue;
            }
            $this->appendMethods[$method] = $body;
            sort($parserImports[$method]);
            foreach ($parserImports[$method] as $parserImport) {
                $namespace = $this->reqParser->getNamespace();
                $imports[$parserImport] = "use {$namespace}\\{$parserImport};";
            }
        }

        if ($this->existsClass() && empty($this->appendMethods)) {
            return;
        }

        if (!$this->existsClass()) {
            $data = str_replace([
                '{{INCLUDE_REQ}}',
                '{{PREFIX}}',
                '{{SERVICE_API}}'
            ], [
                join("\n", $imports),
                $this->prefix,
                join("\n", $this->parser->getMethods()),
            ], $this->template);
        } else {
            $data = file_get_contents($this->parser->getFilename());
            $injectImport = "use Hyperf\\Di\\Annotation\\Inject;";
            $imports[$injectImport] = $injectImport;
            $data = str_replace($injectImport, join("\n", $imports), $data);
            $data = substr(trim($data), 0, strlen(trim($data)) - 1) . join("\n", $this->appendMethods) . "\n}\n";
        }

        file_put_contents($this->parser->getFilename(), $data);
        echo "create {$this->parser->getFilename()} successfully.\n";
    }

    private function mkDirIfNotExists(): void
    {
        is_dir($this->parser->getPath()) || mkdir($this->parser->getPath(), 0777, true);
    }

    private function existsClass(): bool
    {
        $cls = $this->parser->getNamespace() . '\\' . $this->parser->getClassName();
        return @class_exists($cls);
    }

    private function existsClassMethod(string $method): bool
    {
        if (!$this->existsClass()) return false;
        return in_array($method, $this->classMethods);
    }
}