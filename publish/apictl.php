<?php
declare(strict_types=1);

return [
    'pool' => 'default',
    'default' => [
        'service_path' => env('APICTL_SERVICE_PATH', '/app/Application/Service/'),
        'service_contract_path' => env('APICTL_SERVICE_CONTRACT_PATH', '/app/Application/Service/Contract'),
        'domain_path' => env('APICTL_DOMAIN_PATH', '/app/Domain'),
        'controller_path' => env('APICTL_CONTROLLER_PATH', '/app/Interfaces/Controller'),
        'type_path' => env('APICTL_TYPE_PATH', '/app/Interfaces/Types'),
        'api_path' => env('APICTL_API_PATH', '/app/Interfaces/Desc'),
        'swagger_name' => env('APICTL_SWAAGER_NAME', 'api.swaager.json'),
    ],
];