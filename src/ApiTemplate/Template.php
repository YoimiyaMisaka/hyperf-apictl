<?php
declare(strict_types=1);

namespace Timebug\ApiCtl\ApiTemplate;

use Timebug\ApiCtl\Config\ConfigFactory;

/**
 * API模板
 */
class Template
{
    private string $apiName;

    private string $module;

    const SERVICE_INTERFACE_DOC = '// service interface autoload libraries';

    const SERVICE_DOC = '// service autoload libraries';

    const REQUEST_PROPERTY_DEFINE = '/* request property define */';

    const RESPONSE_PROPERTY_DEFINE = '/* response property define */';

    public function __construct(string $apiName, string $module)
    {
        $this->apiName = $apiName;
        $this->module  = $module;
    }

    public function getModule(): string
    {
        $arr = explode("_", $this->apiName);
        $module = array_reduce($arr, function ($carry, $item) {
            return $carry . ucfirst($item);
        }, "");
        return rtrim($module, '.api');
    }

    public function controllerTemplate(): string
    {

        $contractNamespace = ConfigFactory::getServiceContractNamespace();
        $serviceInterface = $this->module . "ServiceInterface";
        $controllerNamespace = ConfigFactory::getControllerNamespace();
        return <<<EOF
<?php
declare(strict_types=1);

namespace $controllerNamespace;

use $contractNamespace\\$serviceInterface;
use App\Controller\AbstractController;
use Timebug\ApiCtl\Response\Resp;
{{INCLUDE_REQ}}
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Psr7ResponseInterface;

/**
 * @Controller() 
 */
#[Controller(prefix: "{{PREFIX}}")]
class {$this->module}Controller extends AbstractController
{
    #[Inject]
    private {$serviceInterface} \$service;
    
{{SERVICE_API}}
}

EOF;
    }

    public function serviceInterfaceTemplate(): string
    {
        $contractNamespace = ConfigFactory::getServiceContractNamespace();
        $serviceInterface = $this->module . "ServiceInterface";
        $doc = self::SERVICE_INTERFACE_DOC;
        return <<<EOF
<?php
declare(strict_types=1);

namespace $contractNamespace;

$doc
{{INCLUDE_REQ}}
{{INCLUDE_RESP}}

interface {$serviceInterface}
{
{{INTERFACE_DEFINE}}
}

EOF;
    }

    public function serviceTemplate(): string
    {
        $contractNamespace = ConfigFactory::getServiceContractNamespace();
        $serviceNamespace = ConfigFactory::getServiceNamespace();
        $serviceInterface = $this->module . "ServiceInterface";
        $service = $this->module . "Service";
        $doc = self::SERVICE_DOC;

        return <<<EOF
<?php
declare(strict_types=1);

namespace $serviceNamespace;

$doc
{{INCLUDE_TYPE_LIBRARIES}}
{{INCLUDE_DOMAIN_LIBRARIES}}
use $contractNamespace\\$serviceInterface;

class {$service} implements {$serviceInterface}
{
{{SERVICE_HANDLE}}
}

EOF;

    }

    public function domainServiceTemplate(): string
    {
        $domainNamespace = ConfigFactory::getDomainServiceNamespace($this->module);
        return <<<EOF
<?php
declare(strict_types=1);

namespace $domainNamespace;

{{INCLUDE_REQ_CLASS}}
{{INCLUDE_RESP_CLASS}}

class {{HANDLE_CLASS}}
{
    public function handle(Request \$req): Response
    {
        \$resp = new Response();
        return \$resp;
    }
}

EOF;

    }

    public function typesReqTemplate(): string
    {
        $typesNamespace = ConfigFactory::getTypesNamespace($this->module);
        $doc = self::REQUEST_PROPERTY_DEFINE;
        return <<<EOF
<?php
declare(strict_types=1);

namespace $typesNamespace;

use Timebug\ApiCtl\Annotation\Validator;
use Timebug\ApiCtl\Annotation\ReqMapper;
use Timebug\ApiCtl\BaseObject\BaseRequest;

class {{REQ_NAME}} extends BaseRequest
{
    {{REQUEST_PARAMS}}
    
    $doc
    
    {{REQUEST_GETTER}}
}

EOF;

    }

    public function typesRespTemplate(): string
    {
        $typesNamespace = ConfigFactory::getTypesNamespace($this->module);
        $doc = self::RESPONSE_PROPERTY_DEFINE;
        return <<<EOF
<?php
declare(strict_types=1);

namespace $typesNamespace;

use Timebug\ApiCtl\Annotation\RespMapper;
use Timebug\ApiCtl\BaseObject\BaseResponse;

class {{RESP_NAME}} extends BaseResponse
{
    {{RESPONSE_PARAMS}}
    
    $doc
    
    {{RESPONSE_SETTER}}
}

EOF;

    }
}