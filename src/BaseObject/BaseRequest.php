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
        $validated = parent::all();
        foreach ($validated as $prop => $value) {
            if (!isset($this->propMap[$prop])) continue;
            $propName = $this->propMap[$prop];
            property_exists($this, $propName) && $this->{$propName} = $value;
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