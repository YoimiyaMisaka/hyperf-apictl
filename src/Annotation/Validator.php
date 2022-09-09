<?php
declare(strict_types=1);

namespace Timebug\ApiCtl\Annotation;


use Attribute;

/**
 * Class Validator
 * @package App\Annotation
 *
 * @Annotation
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Validator
{
    /**
     * 校验规则
     *
     * @var string
     */
    protected string $rule = "";

    /**
     * 提示信息
     *
     * @var string
     */
    protected string $message = "";

    /**
     * 属性映射
     *
     * @var string
     */
    protected string $prop = "";

    /**
     * Validator constructor.
     * @param string $rule    校验规则
     * @param string $message 提示信息
     * @param string $prop    属性映射
     */
    public function __construct(string $rule, string $message = "", string $prop = "")
    {
        $this->rule = $rule;
        $this->prop = $prop;
        $this->message = $message;
    }
}