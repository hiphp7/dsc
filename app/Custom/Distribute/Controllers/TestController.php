<?php

namespace App\Custom\Distribute\Controllers;

use App\Custom\Controller as FrontController;
use Exception;

/**
 * Class TestController
 * @package App\Custom\Distribute\Controllers
 */
class TestController extends FrontController
{
    /**
     * 测试
     * @return array
     * @throws Exception
     */
    public function test()
    {
        $_lang = $this->load_lang(['common']);
        $this->assign('lang', $_lang);

        $res = [];

        return $res;
    }


}
