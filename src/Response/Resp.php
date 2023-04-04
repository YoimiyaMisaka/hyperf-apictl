<?php
declare(strict_types=1);

namespace Timebug\ApiCtl\Response;

use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Psr7ResponseInterface;
use Timebug\ApiCtl\BaseObject\BaseResponse;

class Resp
{
    /**
     * 成功响应
     *
     * @param ResponseInterface $response
     * @param $data
     * @return Psr7ResponseInterface
     */
    public static function success(ResponseInterface $response, $data): Psr7ResponseInterface
    {
        $resp = [
            'status'  => 0,
            'code'    => 200,
            "data"    => $data,
            "message" => "success",
        ];
        if ($data instanceof BaseResponse) {
            $resp["data"] = $data->toArray();
        }
        return $response->json($resp);
    }

    /**
     * 失败响应
     *
     * @param ResponseInterface $response
     * @param string $message
     * @param int $code
     * @return Psr7ResponseInterface
     */
    public static function error(ResponseInterface $response, string $message = "", int $code = 400): Psr7ResponseInterface
    {
        $resp = [
            'status'   => -1,
            'code'     => $code,
            'data'     => [],
            'message'  => $message,
        ];
        return $response->json($resp);
    }
}