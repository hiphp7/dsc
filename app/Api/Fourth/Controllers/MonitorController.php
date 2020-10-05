<?php

namespace App\Api\Fourth\Controllers;

/**
 * Class MonitorController
 * @package App\Api\Fourth\Controllers
 */
class MonitorController
{
    public function index()
    {
        return [
            'code' => 200,
            'message' => 'api server normal.',
            'time' => time(),
        ];
    }
}
