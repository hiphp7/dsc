<?php

namespace App\Api\Foundation\Components;

use Exception;
use Firebase\JWT\JWT;
use Illuminate\Support\Carbon;

trait ApiResponse
{
    /**
     * 通过JWT加密用户数据
     * @param null $data
     * @return string
     */
    public function JWTEncode($data = null)
    {
        $key = config('app.key');

        $data = $this->getJWTToken($data);

        return JWT::encode($data, $key, 'HS256');
    }

    /**
     * 通过JWT解密用户数据
     * @param $token
     * @param string $value
     * @return mixed
     */
    public function JWTDecode($token, $value = 'user_id')
    {
        $key = config('app.key');

        try {
            $data = JWT::decode($token, $key, ['HS256']);

            return collect($data)->get($value);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * 返回用户数据的属性
     * @param null $token
     * @param string $header
     * @param string $value
     * @return mixed
     */
    public function authorization($token = null, $header = 'token', $value = 'user_id')
    {
        if (request()->hasHeader($header)) {
            $token = request()->header($header);
        } elseif (request()->has($header)) {
            $token = request()->get($header);
        }

        return is_null($token) ? 0 : $this->JWTDecode($token, $value);
    }

    /**
     * 设置JWT数据的有效期
     * @param null $data
     * @return array
     */
    protected function getJWTToken($data = null)
    {
        $token = config('jwt');

        // Add Token expires 过期时间
        $token['exp'] = Carbon::now()->addDays($token['exp'])->timestamp;

        return array_merge($token, $data);
    }
}
