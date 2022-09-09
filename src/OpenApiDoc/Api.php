<?php
declare(strict_types=1);

namespace Timebug\ApiCtl\OpenApiDoc;

class Api
{
    private string $path;

    private string $method;

    private string $tag;

    private string $summary;

    private string $folder;

    private string $status = "developing";

    private string $ref = "";

    private array $params = [];

    private array $formProps = [];

    private array $formRequired = [];

    /**
     * @param string $path
     * @return Api
     */
    public function setPath(string $path): Api
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @param string $method
     * @return Api
     */
    public function setMethod(string $method): Api
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @param string $tag
     * @return Api
     */
    public function setTag(string $tag): Api
    {
        $this->tag = $tag;
        return $this;
    }

    /**
     * @param string $summary
     * @return Api
     */
    public function setSummary(string $summary): Api
    {
        $this->summary = $summary;
        return $this;
    }

    /**
     * @param string $folder
     * @return Api
     */
    public function setFolder(string $folder): Api
    {
        $this->folder = $folder;
        return $this;
    }

    /**
     * @param string $status
     * @return Api
     */
    public function setStatus(string $status): Api
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @param string $ref
     * @return Api
     */
    public function setRef(string $ref): Api
    {
        $this->ref = $ref;
        return $this;
    }

    private function addHeaderParam(): void
    {
        $commonParams = [
            [
                "name" => "appid",
                "in" => "header",
                "description" => "应用ID",
                "required" => true,
                "example" => "{{appid}}",
                "schema" => ["type" => "string"]
            ],
            [
                "name" => "nonce",
                "in" => "header",
                "description" => "随机字符串",
                "required" => true,
                "example" => "{{nonce}}",
                "schema" => ["type" => "string"]
            ],
            [
                "name" => "timestamp",
                "in" => "header",
                "description" => "当时时间戳",
                "required" => true,
                "example" => "{{timestamp}}",
                "schema" => ["type" => "integer"]
            ],
            [
                "name" => "signature",
                "in" => "header",
                "description" => "请求签名",
                "required" => true,
                "example" => "{{signature}}",
                "schema" => ["type" => "string"]
            ]
        ];
        $this->params = array_merge($this->params, $commonParams);
    }

    public function addParam(string $param, string $type, bool $required = false, string $desc = "", $default = ""): static
    {
        if ($this->method == "get") {
            $this->params[] = [
                "name" => $param,
                "in" => "query",
                "description" => $desc,
                "required" => $required,
                "example" => $default,
                "schema" => ["type" => $type]
            ];
        } else {
            $this->formProps[$param] = [
                "type" => $type,
                "description" => $desc,
                "example" => $default,
            ];
            $required && $this->formRequired[] = $param;
        }
        return $this;
    }

    public function format(): array
    {
        $this->addHeaderParam();
        $resp = [
            $this->method => [
                "summary" => $this->summary,
                "x-apifox-folder" => $this->folder,
                "x-apifox-status" => $this->status,
                "deprecated" => false,
                "description" => "",
                "tags" => [
                    $this->tag,
                ],
                "parameters" => $this->params,
                "responses" => [
                    "200" => [
                        "description" => "成功",
                        "content" => [
                            "application/json" => [
                                "schema" => [
                                    "\$ref" => "#/components/schemas/{$this->ref}",
                                    "x-apifox-overrides" => [],
                                ],
                                "example" => [],
                            ]
                        ],
                    ],
                ],
            ]
        ];
        $this->method == "post" && $resp[$this->method]["requestBody"] = $this->getRequestBody();
        return $resp;
    }

    public function getRequestBody(): array
    {
        return [
            "content" => [
                "multipart/form-data" => [
                    "schema" => [
                        "type" => "object",
                        "properties" => $this->formProps,
                        "required" => $this->formRequired,
                    ]
                ],
            ],
        ];
    }
}