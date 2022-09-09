<?php

namespace Timebug\ApiCtl\Util;

use ReflectionAttribute;
use ReflectionProperty;

class Helper
{
    /**
     * 获取注解
     *
     * @param ReflectionProperty $property
     * @param string $name
     * @return ReflectionAttribute|null
     */
    public static function getReflectionAttribute(ReflectionProperty $property, string $name = ""): null|ReflectionAttribute
    {
        foreach ($property->getAttributes() as $attribute) {
            if ($attribute->getName() != $name) {
                continue;
            }
            return $attribute;
        }
        return null;
    }

    /**
     * 通过路径获取命名空间
     *
     * @param string $path
     * @return string
     */
    public static function getNamespaceByPath(string $path): string
    {
        return str_replace('/', '\\', ucfirst(trim($path, '/')));
    }
}