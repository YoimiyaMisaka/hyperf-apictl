<?php

namespace Timebug\ApiCtl\Config;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Timebug\ApiCtl\Util\Helper;

class ConfigFactory
{
    /**
     * 获取配置
     *
     * @return ApiCtlConfig
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function getConfig(): ApiCtlConfig
    {
        $config = ApplicationContext::getContainer()->get(ConfigInterface::class);
        $pool = $config->get("apictl.pool", 'default');
        $key = sprintf("apictl.%s", $pool);
        $ctlConfig = $config->get($key, []);
        return new ApiCtlConfig($ctlConfig);
    }

    /**
     * 获取控制器命名空间
     *
     * @return string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function getControllerNamespace(): string
    {
        $config = self::getConfig();
        return Helper::getNamespaceByPath($config->getControllerPath());
    }

    /**
     * 获取应用服务接口命名空间
     *
     * @return string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function getServiceContractNamespace(): string
    {
        $config = self::getConfig();
        return Helper::getNamespaceByPath($config->getServiceContractPath());
    }

    /**
     * 获取应用服务命名空间
     *
     * @return string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function getServiceNamespace(): string
    {
        $config = self::getConfig();
        return Helper::getNamespaceByPath($config->getServicePath());
    }

    /**
     * 获取领域服务命名空间
     *
     * @param string $module 领域模块
     * @return string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function getDomainServiceNamespace(string $module): string
    {
        $config = self::getConfig();
        return Helper::getNamespaceByPath(ltrim($config->getDomainPath(), '/') . '/' . ucfirst($module) . '/Service');
    }

    /**
     * 获取请求体和响应体的命名空间
     *
     * @param string $module
     * @return string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function getTypesNamespace(string $module): string
    {
        $config = self::getConfig();
        return Helper::getNamespaceByPath(ltrim($config->getTypesPath(), '/') . '/' . ucfirst($module));
    }
}