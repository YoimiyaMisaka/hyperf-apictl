<?php
declare(strict_types=1);

namespace Timebug\ApiCtl\ApiParse;


use Timebug\ApiCtl\ApiCreator\ControllerCreator;
use Timebug\ApiCtl\ApiCreator\DomainServiceCreator;
use Timebug\ApiCtl\ApiCreator\ReqCreator;
use Timebug\ApiCtl\ApiCreator\RespCreator;
use Timebug\ApiCtl\ApiCreator\ServiceCreator;
use Timebug\ApiCtl\ApiCreator\ServiceInterfaceCreator;
use Timebug\ApiCtl\ApiTemplate\Template;
use Timebug\ApiCtl\Config\ApiCtlConfig;
use Timebug\ApiCtl\Config\ConfigFactory;
use Timebug\ApiCtl\OpenApiDoc\Api;
use Timebug\ApiCtl\OpenApiDoc\Schemas;

class ApiParse
{
    /**
     * api内容
     *
     * @var string
     */
    private string $apiContent;

    /**
     * 路由前缀
     * @var string
     */
    private string $prefix;

    /**
     * 模块
     *
     * @var string
     */
    private string $module;

    /**
     * 模块名
     *
     * @var string
     */
    private string $moduleName = '';

    private array $typeMap = [];

    private ApiCtlConfig $ctlConfig;

    /**
     * API 模板
     *
     * @var Template
     */
    private Template $template;

    /**
     * @var string
     */
    private string $pool;

    public function __construct(string $filename, string $pool = 'default')
    {
        $this->pool = $pool;
        $this->ctlConfig = ConfigFactory::getConfig($pool);
        $apiPath = BASE_PATH . $this->ctlConfig->getApiPath() . '/' . $filename;
        $this->apiContent = file_get_contents($apiPath);
        $this->parseServer();
        $this->parseInfo();
        $this->template = new Template($filename, $this->module, $pool);
    }

    /**
     * 解析Server
     *
     * @return void
     */
    public function parseServer(): void
    {
        preg_match('/@server\s*\(([^)]+)/', $this->apiContent, $matches);
        $match = end($matches);

        $array = explode("\n", $match);
        foreach ($array as $item) {
            if (trim($item) == "") {
                continue;
            }

            if (str_contains($item, "prefix:")) {
                $this->prefix = trim(str_replace("prefix:", "", $item));
            }
            if (str_contains($item, "group:")) {
                $this->module = ucfirst(trim(str_replace("group:", "", $item)));
            }
        }
    }

    /**
     * 解析Info
     *
     * @return void
     */
    public function parseInfo(): void
    {
        preg_match('/info\s*\(([^)]+)/', $this->apiContent, $matches);
        $match = end($matches);
        $array = explode("\n", $match);
        foreach ($array as $item) {
            if (trim($item) == "") {
                continue;
            }
            if (str_contains($item, "title:")) {
                $this->moduleName = trim(str_replace("title:", "", $item));
                $this->moduleName = trim(str_replace("接口", "", $this->moduleName), '"');
                break;
            }
        }
        if ($this->moduleName == '') $this->moduleName = $this->module;
    }

    public function parse(): void
    {
        $this->parseServer();
        $controllerParser = (new ControllerParser($this->ctlConfig))->init($this->module, $this->apiContent);
        $domainParser = (new DomainServiceParser($this->ctlConfig))->init($this->module, $this->apiContent);
        $reqParser = (new ReqParser($this->ctlConfig))->init($this->module, $this->apiContent);
        $respParser = (new RespParser($this->ctlConfig))->init($this->module, $this->apiContent);
        $facadeParser = (new ServiceInterfaceParser($this->ctlConfig))->init($this->module, $this->apiContent);
        $svcParser = (new ServiceParser($this->ctlConfig))->init($this->module, $this->apiContent);

        (new ControllerCreator($controllerParser))->setReqParser($reqParser)->setRespParser($respParser)->setTemplate($this->template->controllerTemplate())->setPrefix($this->prefix)->handle();
        (new DomainServiceCreator($domainParser))->setTemplate($this->template->domainServiceTemplate())->setReqParser($reqParser)->handle();
        (new ReqCreator($reqParser))->setTemplate($this->template->typesReqTemplate())->handle();
        (new RespCreator($respParser))->setTemplate($this->template->typesRespTemplate())->handle();
        (new ServiceCreator($svcParser))->setReqParser($reqParser)->setDomainServiceParser($domainParser)->setTemplate($this->template->serviceTemplate())->handle();
        (new ServiceInterfaceCreator($facadeParser))->setReqParser($reqParser)->setTemplate($this->template->serviceInterfaceTemplate())->handle();
    }

    public function parseDoc(): array
    {
        $this->parseServer();
        $controllerParser = (new ControllerParser($this->ctlConfig))->init($this->module, $this->apiContent);
        $reqParser = (new ReqParser($this->ctlConfig))->init($this->module, $this->apiContent);
        $respParser = (new RespParser($this->ctlConfig))->init($this->module, $this->apiContent);

        $apiReqProps = $reqParser->getApiProps();
        $apiRespProps = $respParser->getApiProps();

        $resp = [];
        foreach ($controllerParser->getApis() as $apiName => $item) {
            $api = new Api();
            $path = '/' . trim($this->prefix, "/") . '/' . $item["path"];
            $schemaName = $this->module . $item["resp"];
            $api->setPath($path)
                ->setSummary($item["doc"])
                ->setTag($this->module)
                ->setFolder($this->moduleName)
                ->setMethod($item['method'])
                ->setRef($schemaName);

            $reqProps = $apiReqProps[$item["req"]] ?? [];
            foreach ($reqProps as $propName => $reqProp) {
                $api->addParam($reqProp["propName"], $reqProp["type"], $reqProp["required"], $reqProp["desc"], $reqProp["default"]);
            }

            $resp[$path]["api"] = $api->format($this->pool);

            $respProps = $apiRespProps[$item["resp"]];
            $schema = $this->parseApiResp($apiRespProps, $respProps);
            $schema->setName($schemaName)
                ->setFolder($this->moduleName);
            $resp[$path]["tag"] = $this->module;
            $resp[$path]["schemaName"] = $this->module . $item["resp"];
            $resp[$path]["schema"] = $schema->format();
        }
        return $resp;
    }

    public function parseApiResp(array $apiRespProps, array $respProps): Schemas
    {
        $schema = new Schemas();
        foreach ($respProps as $prop) {
            if (isset($apiRespProps[$prop["typeName"]])) {
                if (isset($this->typeMap[$prop['typeName']])) continue;
                $this->typeMap[$prop['typeName']] = $prop['typeName'];
                $subSchema = $this->parseApiResp($apiRespProps, $apiRespProps[$prop["typeName"]]);
                $type = $prop["type"] == "array" ? "array" : "object";
                match ($type) {
                    "array" => $schema->addProperty($type, $prop["json"], [
                        "type" => "array", "items" => ["type" => "object", "properties" => $subSchema->getProperties()]
                    ]),
                    "object" => $schema->addProperty($type, $prop["json"], [
                        "type" => "object", "properties" => $subSchema->getProperties()
                    ])
                };
            } else {
                $schema->addProperty($prop["type"], $prop["json"], [], $prop["desc"]);
            }
        }
        return $schema;
    }
}