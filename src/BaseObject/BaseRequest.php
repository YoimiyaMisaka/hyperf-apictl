<?php
declare(strict_types=1);

namespace Timebug\ApiCtl\BaseObject;


use Hyperf\Validation\Request\FormRequest;
use Hyperf\Validation\ValidationException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Timebug\ApiCtl\Annotation\ReqMapper;
use Timebug\ApiCtl\Annotation\Validator;

class BaseRequest extends FormRequest
{
    /**
     * @var array
     */
    protected array $rules = [];

    /**
     * @var array
     */
    protected array $messages = [];

    /**
     * @var array
     */
    protected array $propMap = [];

    /**
     * @var array
     */
    protected array $attributeProps = [];

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->init();
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return $this->messages;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return $this->rules;
    }

    private function init()
    {
        $ref = new ReflectionClass($this);
        foreach ($ref->getProperties() as $property) {
            if ($property->getAttributes()) {
                $reqMapperAttrs = $property->getAttributes(ReqMapper::class);
                $prop = $property->getName();
                if ($reqMapperAttrs) {
                    $attribute = $reqMapperAttrs[0];
                    $attrArgs = $attribute->getArguments();
                    $prop = $attrArgs['json'];
                }

                if (!$property->getAttributes(Validator::class)) {
                    $this->propMap[$prop] = $property->getName();
                    continue;
                }
                $this->attributeProps[] = $property->getName();

                foreach ($property->getAttributes() as $attribute) {
                    if ($attribute->getName() == Validator::class) {
                        $attrArgs = $attribute->getArguments();

                        if (str_contains($attrArgs["rule"], ":")) {
                            $rule = current(explode(":", $attrArgs["rule"]));
                        } else {
                            $rule = $attrArgs["rule"];
                        }

                        $this->rules[$prop][] = $attrArgs["rule"];
                        $attrArgs["message"] && $this->messages["{$prop}.{$rule}"] = $attrArgs["message"];
                    }
                    $this->propMap[$prop] = $property->getName();
                }
            } else {
                $this->propMap[$property->getName()] = $property->getName();
            }
        }
    }

    public function all(): array
    {
        return $this->initPropertyValue();
    }

    /**
     * 初始化请求属性
     *
     * @return array
     */
    private function initPropertyValue(): array
    {
        $ref = new ReflectionClass($this);
        $validated = parent::all();
        foreach ($ref->getProperties() as $property) {
            if (!in_array($property->getName(), $this->attributeProps)) {
                continue;
            }

            $property->setAccessible(true);
            $prop = array_search($property->getName(), $this->propMap);
            if (!$prop) {
                $property->setValue($this, null);
                continue;
            }
            $value = $validated[$prop] ?? null;
            switch ($property->getType()) {
                case 'int': $property->setValue($this, (int)$value); break;
                case 'string': $property->setValue($this, (string)$value); break;
                case 'float': $property->setValue($this, (float)$value); break;
                case 'array': $property->setValue($this, (array)$value); break;
                default: $property->setValue($this, $value);
            }
        }
        return $validated;
    }

    /**
     * @return array
     * @throws ValidationException
     */
    public function parse(): array
    {
        return $this->validated();
    }
}