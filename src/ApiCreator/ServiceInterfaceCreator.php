<?php
declare(strict_types=1);

namespace Timebug\ApiCtl\ApiCreator;


use Timebug\ApiCtl\ApiParse\ReqParser;
use Timebug\ApiCtl\ApiParse\ServiceInterfaceParser;
use Timebug\ApiCtl\ApiTemplate\Template;

class ServiceInterfaceCreator
{
    private ServiceInterfaceParser $parser;

    private ReqParser $reqParser;

    private string $template;

    private string $fullClsName;

    private array $classMethods;

    public function __construct(ServiceInterfaceParser $parser)
    {
        $this->parser = $parser;
        $this->fullClsName = $this->parser->getNamespace() . '\\' . $this->parser->getClassName();
        $this->classMethods = @class_exists($this->fullClsName) ? get_class_methods($this->fullClsName) : [];
    }

    public function handle(): void
    {
        $interfaceImports = $this->parser->getImports();
        $typeNamespace = $this->reqParser->getNamespace();
        if (@class_exists($this->fullClsName)) {
            $data = file_get_contents($this->parser->getFilename());
            $appendImports = [Template::SERVICE_INTERFACE_DOC];
            $appendMethods = [];
            foreach ($this->parser->getMethods() as $method => $body) {
                if (in_array($method, $this->classMethods)) {
                    continue;
                }
                $methodImport = $interfaceImports[$method];
                foreach ($methodImport as $item) {
                    $clsNamespace = "$typeNamespace\\$item";
                    if (str_contains($data, $clsNamespace)) continue;
                    $appendImports[] = "use $clsNamespace;";
                }
                $appendMethods[] = $body;
            }
            $data = str_replace(Template::SERVICE_INTERFACE_DOC, join("\n", $appendImports), $data);
            $data = substr(trim($data), 0, strlen(trim($data)) - 1) . join("\n", $appendMethods) . "\n}\n";
        } else {
            $imports = [];
            foreach ($interfaceImports as $import) {
                foreach ($import as $item) {
                    $imports[] = "use $typeNamespace\\$item;";
                }
            }

            $data = str_replace([
                '{{INCLUDE_REQ}}',
                '{{INCLUDE_RESP}}',
                '{{INTERFACE_DEFINE}}',
            ], [
                join("\n", $imports),
                '',
                join("\n", $this->parser->getMethods())
            ], $this->template);
        }

        is_dir($this->parser->getPath()) || mkdir($this->parser->getPath(), 0777, true);
        file_put_contents($this->parser->getFilename(), $data);
        echo "create {$this->parser->getFilename()} successfully.\n";
    }

    public function setReqParser(ReqParser $parser): static
    {
        $this->reqParser = $parser;
        return $this;
    }

    public function setTemplate(string $template): static
    {
        $this->template = $template;
        return $this;
    }
}