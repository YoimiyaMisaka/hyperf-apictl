<?php

namespace Timebug\ApiCtl\Config;

class ApiCtlConfig
{
    /**
     * 应用服务路径
     * @var string
     */
    private string $servicePath;

    /**
     * 应用服务接口路径
     *
     * @var string
     */
    private string $serviceContractPath;

    /**
     * 领域路径
     *
     * @var string
     */
    private string $domainPath;

    /**
     * 接口路径
     *
     * @var string
     */
    private string $controllerPath;

    /**
     * API 路径
     *
     * @var string
     */
    private string $apiPath;

    /**
     * 生成API文档文件名
     *
     * @var string
     */
    private string $swaggerName;

    /**
     *
     *
     * @var array
     */
    private array $commonHeaders;

    /**
     * 请求响应体路径
     *
     * @var string
     */
    private string $typesPath;

    public function __construct(array $config = [])
    {
        $this->servicePath = $config['service_path'] ?? '';
        $this->serviceContractPath = $config['service_contract_path'] ?? '';
        $this->domainPath = $config['domain_path'] ?? '';
        $this->controllerPath = $config['controller_path'] ?? '';
        $this->typesPath = $config['type_path'] ?? '';
        $this->apiPath = $config['api_path'] ?? '';
        $this->swaggerName = $config['swagger_name'] ?? '';
        $this->commonHeaders = $config['api_common_headers'] ?? [];
    }

    /**
     * @return string
     */
    public function getServicePath(): string
    {
        return $this->servicePath;
    }

    /**
     * @return string
     */
    public function getServiceContractPath(): string
    {
        return $this->serviceContractPath;
    }

    /**
     * @return string
     */
    public function getDomainPath(): string
    {
        return $this->domainPath;
    }

    /**
     * @return string
     */
    public function getControllerPath(): string
    {
        return $this->controllerPath;
    }

    /**
     * @return string
     */
    public function getTypesPath(): string
    {
        return $this->typesPath;
    }

    /**
     * @return string
     */
    public function getApiPath(): mixed
    {
        return $this->apiPath;
    }

    /**
     * @return string
     */
    public function getSwaggerName(): mixed
    {
        return $this->swaggerName;
    }

    /**
     * @return array
     */
    public function getCommonHeaders(): array
    {
        return $this->commonHeaders;
    }
}