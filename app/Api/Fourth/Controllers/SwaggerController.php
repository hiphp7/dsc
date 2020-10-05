<?php

namespace App\Api\Fourth\Controllers;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;

/**
 * @OA\Info(
 *     version="1.0",
 *     title="API文档"
 * )
 * Class SwaggerController
 * @package App\Api\Fourth\Controllers
 */
class SwaggerController
{
    /**
     * @return ResponseFactory|Response
     */
    public function index()
    {
        $path = app_path('Api/Fourth/Controllers');

        $oa = \OpenApi\scan($path);

        $data = config('app.debug') ? $oa->toJson() : '';

        return response($data);
    }
}
