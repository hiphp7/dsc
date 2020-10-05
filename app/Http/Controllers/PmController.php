<?php

namespace App\Http\Controllers;

use Binaryoung\Ucenter\Facades\Ucenter;

class PmController extends InitController
{
    public function index()
    {
        if (empty(session('user_id')) || $GLOBALS['_CFG']['integrate_code'] == 'dscmall') {
            return dsc_header('Location:./');
        }

        Ucenter::uc_pm_location(session('user_id'));
    }
}
