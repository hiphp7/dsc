<?php

namespace App\Http\Controllers;

use App\Services\Activity\BonusService;

/**
 * 提交用户评论
 */
class BounsAvailableController extends InitController
{
    protected $bonusService;

    public function __construct(BonusService $bonusService)
    {
        $this->bonusService = $bonusService;
    }

    public function index()
    {
        if (!request()->exists('cmt') && !request()->exists('act')) {
            /* 只有在没有提交评论内容以及没有act的情况下才跳转 */
            return dsc_header("Location: ./\n");
        }
        $result = ['error' => 0, 'message' => '', 'content' => ''];

        $page = intval(request()->input('page', 1));

        $user_id = session('user_id', 0);

        if ($result['error'] == 0) {
            $size = 12;
            $bonus = $this->bonusService->getUserBounsNewList($user_id, $page, 0, 'bouns_available_gotoPage', 0, $size);
            $this->smarty->assign('bonus', $bonus);
            $this->smarty->assign('size', $size);

            $result['content'] = $this->smarty->fetch("library/bouns_available_list.lbi");
        }

        return response()->json($result);
    }
}
