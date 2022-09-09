<?php
declare(strict_types=1);

namespace Timebug\ApiCtl\ApiCreator;


use Timebug\ApiCtl\ApiParse\DomainServiceParser;
use Timebug\ApiCtl\ApiParse\ReqParser;

class DomainServiceCreator
{
    private DomainServiceParser $parser;

    private ReqParser $reqParser;

    private string $template;

    public function __construct(DomainServiceParser $parser)
    {
        $this->parser = $parser;
    }

    public function handle(): void
    {
        $path = $this->parser->getPath();
        is_dir($path) || mkdir($path, 0777, true);

        $imports = $this->parser->getImports();
        $typeNamespace = $this->reqParser->getNamespace();
        foreach ($imports as $className => $import) {
            $fullClassName = $this->parser->getNamespace() . '\\' . $className;
            $filename  = $this->parser->getFilename($className);
            if (@class_exists($fullClassName)) {
                echo "file $filename has already exists.\n";
                continue;
            }

            $useImport = [];
            foreach ($import as $type => $item) {
                $useImport[] = $type == "req" ? "use {$typeNamespace}\\{$item} as Request;" : "use {$typeNamespace}\\{$item} as Response;";
            }

            $data = str_replace([
                '{{INCLUDE_REQ_CLASS}}',
                '{{INCLUDE_RESP_CLASS}}',
                '{{HANDLE_CLASS}}',
            ], [
                join("\n", $useImport),
                '',
                $className
            ], $this->template);
            file_exists($filename) || file_put_contents($filename, $data);
            echo "create $filename successfully.\n";
        }
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