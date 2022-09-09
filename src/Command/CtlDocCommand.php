<?php

namespace Timebug\ApiCtl\Command;

use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Timebug\ApiCtl\ApiParse\ApiParse;
use Timebug\ApiCtl\Config\ConfigFactory;
use Timebug\ApiCtl\OpenApiDoc\OpenApi;

/**
 * @Command()
 */
#[Command]
class CtlDocCommand extends HyperfCommand
{

    protected $name = "apictl:doc";

    public function handle()
    {
        $ctlConfig = ConfigFactory::getConfig();
        $docPath = BASE_PATH . $ctlConfig->getApiPath();

        $apis = scandir($docPath);
        $apis = array_values(array_filter($apis, fn($item) => !in_array($item, [".", ".."])));

        $openapi = new OpenApi();
        foreach ($apis as $api) {
            $parse = new ApiParse($api);
            $paths = $parse->parseDoc();
            foreach ($paths as $path => $item) {
                $openapi->addPath($path, $item["api"]);
                $openapi->addTag($item["tag"]);
                $openapi->addSchema($item["schemaName"], $item["schema"]);
            }
        }

        $doc = $openapi->format();
        $jsonDoc = json_encode($doc, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

        $docDir = BASE_PATH . '/doc';
        is_dir($docDir) || mkdir($docDir, 0777, true);
        $filename = $docDir . '/' . $ctlConfig->getSwaggerName();

        if (file_put_contents($filename, $jsonDoc)) {
            $this->info("generate {$ctlConfig->getSwaggerName()} successfully.");
        } else {
            $this->error("generate {$ctlConfig->getSwaggerName()} failed.");
        }
    }
}