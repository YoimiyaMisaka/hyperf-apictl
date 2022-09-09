<?php
declare(strict_types=1);

namespace Timebug\ApiCtl\BaseObject;

use ReflectionClass;
use Timebug\ApiCtl\Annotation\RespMapper;
use Timebug\ApiCtl\Util\Helper;

class BaseResponse
{
    public function toArray(): array
    {
        $data = [];
        $ref = new ReflectionClass($this);
        foreach ($ref->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($this);
            $attribute = Helper::getReflectionAttribute($property, RespMapper::class);
            if (!$attribute) {
                $propName = $property->getName();
            } else {
                $attrArgs = $attribute->getArguments();
                $propName = $attrArgs["json"] ?? $property->getName();
            }
            if (gettype($value) === "array") {
                $items = [];
                foreach ($value as $item) {
                    $items[] = $item instanceof BaseResponse ? $item->toArray() : $item;
                }
                $data[$propName] = $items;
            } else {
                $data[$propName] = $value instanceof BaseResponse ? $value->toArray() : $value;
            }
        }
        return $data;
    }
}