<?php
declare(strict_types=1);

return [
    'pool' => 'default',
    'default' => [
        // 应用服务路径
        'service_path' => env('APICTL_SERVICE_PATH', '/app/Application/Service/'),
        // 应用服务接口路径
        'service_contract_path' => env('APICTL_SERVICE_CONTRACT_PATH', '/app/Application/Service/Contract'),
        // 领域路径
        'domain_path' => env('APICTL_DOMAIN_PATH', '/app/Domain'),
        // 控制器路径
        'controller_path' => env('APICTL_CONTROLLER_PATH', '/app/Interfaces/Controller'),
        // 请求响应体路径
        'type_path' => env('APICTL_TYPE_PATH', '/app/Interfaces/Types'),
        // API路径
        'api_path' => env('APICTL_API_PATH', '/app/Interfaces/Desc'),
        // API文档路径
        'swagger_name' => env('APICTL_SWAAGER_NAME', 'api.swaager.json'),
        // API文档请求头参数, 没有可不填
        'api_common_headers' => [
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
        ],
    ],
];