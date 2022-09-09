<?php
declare(strict_types=1);

namespace Timebug\ApiCtl\ApiCreator;


use Timebug\ApiCtl\ApiParse\DomainServiceParser;
use Timebug\ApiCtl\ApiParse\ReqParser;
use Timebug\ApiCtl\ApiParse\ServiceParser;
use Timebug\ApiCtl\ApiTemplate\Template;

class ServiceCreator
{
    private ServiceParser $parser;

    private ReqParser $reqParser;

    private DomainServiceParser $domainServiceParser;

    private string $template;

    private string $fullClsName;

    private array $clsMethods;

    public function __construct(ServiceParser $parser)
    {
        $this->parser = $parser;
        $this->fullClsName = $this->parser->getNamespace() . '\\' . $this->parser->getClassName();
        $this->clsMethods = @class_exists($this->fullClsName) ? get_class_methods($this->fullClsName) : [];
    }

    public function handle(): void
    {
        $action = "create";
        $serviceImports = $this->parser->getImports();
        $serviceMethods = $this->parser->getMethods();
        $typeNamespace  = $this->reqParser->getNamespace();
        $domainNamespace = $this->domainServiceParser->getNamespace();
        if (@class_exists($this->fullClsName)) {
            $action = "modify";
            $data = file_get_contents($this->parser->getFilename());
            $appendImport = [Template::SERVICE_DOC];
            $appendTypeImports = [];
            $appendDomainImports = [];
            $appendMethods = [];
            foreach ($serviceMethods as $method => $body) {
                if (in_array($method, $this->clsMethods)) {
                    continue;
                }

                $methodImport = $serviceImports[$method];
                foreach ($methodImport as $item) {
                    $fullClsName = str_contains($item, "DomainService") ? "$domainNamespace\\$item" : "$typeNamespace\\$item";
                    if (str_contains($data, $fullClsName)) continue;
                    str_contains($item, "DomainService")
                        ? $appendDomainImports[] = "use $fullClsName;"
                        : $appendTypeImports[] = "use $fullClsName;";
                }
                $appendMethods[] = $body;
            }
            $appendImport = array_merge($appendImport, $appendTypeImports, $appendDomainImports);

            $data = str_replace(Template::SERVICE_DOC, join("\n", $appendImport), $data);
            $data = substr(trim($data), 0, strlen(trim($data)) - 1) . join("\n", $appendMethods) . "\n}\n";
        } else {
            $typeImports = [];
            $domainImports = [];
            foreach ($serviceImports as $import) {
                foreach ($import as $item) {
                    str_contains($item, "DomainService")
                        ? $domainImports[] = "use $domainNamespace\\$item;"
                        : $typeImports[] = "use $typeNamespace\\$item;";
                }
            }

            $data = str_replace([
                '{{INCLUDE_TYPE_LIBRARIES}}',
                '{{INCLUDE_DOMAIN_LIBRARIES}}',
                '{{SERVICE_HANDLE}}'
            ], [
                join("\n", $typeImports),
                join("\n", $domainImports),
                join("\n", $serviceMethods)
            ], $this->template);
        }

        is_dir($this->parser->getPath()) || mkdir($this->parser->getPath(), 0777, true);
        file_put_contents($this->parser->getFilename(), $data);
        echo "$action {$this->parser->getFilename()} successfully.\n";
    }

    /**
     * @param ReqParser $reqParser
     * @return ServiceCreator
     */
    public function setReqParser(ReqParser $reqParser): ServiceCreator
    {
        $this->reqParser = $reqParser;
        return $this;
    }

    /**
     * @param DomainServiceParser $domainServiceParser
     * @return ServiceCreator
     */
    public function setDomainServiceParser(DomainServiceParser $domainServiceParser): ServiceCreator
    {
        $this->domainServiceParser = $domainServiceParser;
        return $this;
    }

    /**
     * @param string $template
     * @return ServiceCreator
     */
    public function setTemplate(string $template): ServiceCreator
    {
        $this->template = $template;
        return $this;
    }
}