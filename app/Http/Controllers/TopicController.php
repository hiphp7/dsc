<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Repositories\Common\DscRepository;
use App\Services\Article\ArticleCommonService;

/**
 * 专题前台
 */
class TopicController extends InitController
{
    protected $dscRepository;
    protected $config;
    protected $articleCommonService;

    public function __construct(
        DscRepository $dscRepository,
        ArticleCommonService $articleCommonService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->config = $this->config();
        $this->articleCommonService = $articleCommonService;
    }

    public function index()
    {
        load_helper('visual');

        /**
         * Start
         *
         * @param $warehouse_id 仓库ID
         * @param $area_id 省份ID
         * @param $area_city 城市ID
         */
        $warehouse_id = $this->warehouseId();
        $area_id = $this->areaId();
        $area_city = $this->areaCity();
        /* End */

        $topic_id = (int)request()->input('topic_id', 0);

        if ($topic_id) {
            $Loaction = dsc_url('/#/topicHome/' . $topic_id);
        } else {
            $Loaction = dsc_url('/#/topic');
        }

        /* 跳转H5 start */
        $uachar = $this->dscRepository->getReturnMobile($Loaction);

        if ($uachar) {
            return $uachar;
        }
        /* 跳转H5 end */

        $preview = (int)request()->input('preview', 0);

        $topic = Topic::where('topic_id', $topic_id);

        $now = gmtime();
        if ($preview != 1) {
            $topic = $topic->where('start_time', '<=', $now)
                ->where('end_time', '>=', $now)
                ->where('review_status', 3);
        }

        $topic = $topic->first();

        $topic = $topic ? $topic->toArray() : [];

        if (empty($topic)) {
            /* 如果没有找到任何记录则跳回到首页 */
            return dsc_header("Location: ./\n");
        }

        /**
         * 专题可视化
         * 下载OSS模板文件
         */
        get_down_topictemplates($topic['topic_id'], $topic['user_id']);

        //获取页面内        容
        $pc_page['tem'] = "topic_" . $topic_id;
        $filename = storage_public('data/topic' . '/topic_' . $topic['user_id'] . "/" . $pc_page['tem']);
        if ($preview == 1) {
            $preview_dir = storage_public('data/topic' . '/topic_' . $topic['user_id'] . "/" . $pc_page['tem'] . "/temp");
            if (is_dir($preview_dir)) {
                $filename = $preview_dir;
            }
        }
        $pc_page['out'] = get_html_file($filename . "/pc_html.php");
        $nav_page = get_html_file($filename . "/nav_html.php");
        /*重写图片链接*/
        $pc_page['out'] = str_replace('__TPL__/data/gallery_album/', "data/gallery_album/", $pc_page['out'], $i);
        $pc_page['out'] = str_replace('../data/seller_templates/', "data/seller_templates/", $pc_page['out'], $i);
        $pc_page['out'] = str_replace('../data/topic/', "data/topic/", $pc_page['out'], $i);

        if ($this->config['open_oss'] == 1) {
            $bucket_info = $this->dscRepository->getBucketInfo();
            $endpoint = $bucket_info['endpoint'];
        } else {
            $endpoint = url('/');
        }

        if ($pc_page['out']) {
            $desc_preg = get_goods_desc_images_preg($endpoint, $pc_page['out']);
            $pc_page['out'] = $desc_preg['goods_desc'];
        }

        /* 模板赋值 */
        assign_template();
        $position = assign_ur_here(0, $topic['title']);
        $this->smarty->assign('page_title', $position['title']);       // 页面标题
        $this->smarty->assign('ur_here', $position['ur_here']);     // 当前位置
        $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());              // 网店帮助

        $this->smarty->assign('show_marketprice', $this->config['show_marketprice']);
        $this->smarty->assign('topic', $topic);                   // 专题信息
        $this->smarty->assign('keywords', $topic['keywords']);       // 专题信息
        $this->smarty->assign('description', $topic['description']);    // 专题信息
        $this->smarty->assign('site_domain', url('/') . '/');  //网站域名


        $categories_pro = get_category_tree_leve_one();
        $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版

        $this->smarty->assign("pc_page", $pc_page);

        $this->smarty->assign('warehouse_id', $warehouse_id);
        $this->smarty->assign('area_id', $area_id);
        $this->smarty->assign('area_city', $area_city);
        $this->smarty->assign('nav_page', $nav_page);

        /* 显示模板 */
        return $this->smarty->display("topic.dwt");
    }
}
