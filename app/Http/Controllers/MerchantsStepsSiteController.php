<?php

namespace App\Http\Controllers;

use App\Models\MerchantsStepsFields;

/**
 * 购物流程
 */
class MerchantsStepsSiteController extends InitController
{
    public function index()
    {
        $user_id = session('user_id', 0);

        if ($user_id <= 0) {
            return show_message($GLOBALS['_LANG']['steps_UserLogin'], $GLOBALS['_LANG']['UserLogin'], 'user.php');
        }

        $steps_site = MerchantsStepsFields::where('user_id', $user_id)->value('steps_site');

        if (empty($steps_site)) {
            $steps_site = 'merchants_steps.php';
        }

        return dsc_header("Location: " . $steps_site . "\n");
    }
}
