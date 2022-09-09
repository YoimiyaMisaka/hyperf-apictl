<?php
declare(strict_types=1);

namespace Timebug\ApiCtl\Annotation;

use Attribute;

/**
 * Class RespMapper
 * @package App\Annotation
 *
 * @Annotation
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class RespMapper
{
    /**
     * 输出字段名
     *
     * @var string
     */
    protected string $json;

    /**
     * 类型
     *
     * @var string
     */
    protected string $type;

    /**
     * RespMapper constructor.
     * @param string $json 输出字段名
     * @param string $type 类型
     */
    public function __construct(string $json, string $type = "")
    {
        $this->json = $json;
        $this->type = $type;
    }
}