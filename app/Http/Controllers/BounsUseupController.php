<?php

namespace App\Http\Controllers;

use App\Services\Activity\BonusService;

class BounsUseupController extends InitController
{
    protected $bonusService;

    public function __construct(
        BonusService $bonusService
    )
    {
        $this->bonusService = $bonusService;
    }

    public function index()
    {
        $result = ['error' => 0, 'message' => '', 'content' => ''];

        $page = intval(request()->input('page', 1));

        $user_id = session('user_id', 0);

        if ($result['error'] == 0) {
            $bonus = $this->bonusService->getUserBounsNewList($user_id, $page, 2, 'bouns_useup_gotoPage');
            $this->smarty->assign('bonus2', $bonus);

            $result['content'] = $this->smarty->fetch("library/bouns_useup_list.lbi");
        }

        return response()->json($result);
    }
}
