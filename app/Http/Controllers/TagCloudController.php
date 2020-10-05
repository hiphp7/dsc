<?php

namespace App\Http\Controllers;

use App\Services\Article\ArticleCommonService;

/**
 * 标签云
 */
class TagCloudController extends InitController
{
    protected $articleCommonService;

    public function __construct(
        ArticleCommonService $articleCommonService
    )
    {
        $this->articleCommonService = $articleCommonService;
    }

    public function index()
    {
        assign_template();
        $position = assign_ur_here(0, $GLOBALS['_LANG']['tag_cloud']);
        $this->smarty->assign('page_title', $position['title']);    // 页面标题
        $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置
        $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助

        /* 调查 */
        $vote = get_vote();
        if (!empty($vote)) {
            $this->smarty->assign('vote_id', $vote['id']);
            $this->smarty->assign('vote', $vote['content']);
        }

        assign_dynamic('tag_cloud');

        $tags = get_tags();

        if (!empty($tags)) {
            load_helper('clips');
            color_tag($tags);
        }

        $this->smarty->assign('tags', $tags);

        return $this->smarty->display('tag_cloud.dwt');
    }
}
