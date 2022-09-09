<?php
declare(strict_types=1);

namespace Timebug\ApiCtl\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ReqMapper extends AbstractAnnotation
{
    /**
     * @param string $json 映射字段
     */
    public function __construct(public string $json)
    {

    }
}