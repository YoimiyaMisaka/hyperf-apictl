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
     * @param string $pool
     * @return ApiCtlConfig
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function getConfig(string $pool = 'default'): ApiCtlConfig
    {
        $config = ApplicationContext::getContainer()->get(ConfigInterface::class);
        !$pool && $pool = $config->get("apictl.pool", 'default');
        $key = sprintf("apictl.%s", $pool);
        $ctlConfig = $config->get($key, []);
        return new ApiCtlConfig($ctlConfig);
    }

    /**
     * 获取控制器命名空间
     *
     * @param string $pool
     * @return string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function getControllerNamespace(string $pool = 'default'): string
    {
        $config = self::getConfig($pool);
        return Helper::getNamespaceByPath($config->getControllerPath());
    }

    /**
     * 获取应用服务接口命名空间
     *
     * @param string $pool
     * @return string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function getServiceContractNamespace(string $pool = 'default'): string
    {
        $config = self::getConfig($pool);
        return Helper::getNamespaceByPath($config->getServiceContractPath());
    }

    /**
     * 获取应用服务命名空间
     *
     * @param string $pool
     * @return string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function getServiceNamespace(string $pool = 'default'): string
    {
        $config = self::getConfig($pool);
        return Helper::getNamespaceByPath($config->getServicePath());
    }

    /**
     * 获取领域服务命名空间
     *
     * @param string $module 领域模块
     * @param string $pool
     * @return string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function getDomainServiceNamespace(string $module, string $pool = 'default'): string
    {
        $config = self::getConfig($pool);
        return Helper::getNamespaceByPath(ltrim($config->getDomainPath(), '/') . '/' . ucfirst($module) . '/Service');
    }

    /**
     * 获取请求体和响应体的命名空间
     *
     * @param string $module
     * @param string $pool
     * @return string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function getTypesNamespace(string $module, string $pool = 'default'): string
    {
        $config = self::getConfig($pool);
        return Helper::getNamespaceByPath(ltrim($config->getTypesPath(), '/') . '/' . ucfirst($module));
    }
}