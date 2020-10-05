<?php

namespace App\Modules\Seller\Controllers;

use App\Models\Topic;
use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;

/**
 * 专题管理
 */
class TopicController extends InitController
{
    protected $dscRepository;
    protected $merchantCommonService;

    public function __construct(
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
    }

    public function index()
    {
        load_helper('visual');
        $adminru = get_admin_ru_id();
        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('action_type', "bonus");
        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        $this->smarty->assign('controller', basename(PHP_SELF, '.php'));

        /* 配置风格颜色选项 */
        $topic_style_color = [
            '0' => '008080',
            '1' => '008000',
            '2' => 'ffa500',
            '3' => 'ff0000',
            '4' => 'ffff00',
            '5' => '9acd32',
            '6' => 'ffd700'
        ];
        $allow_suffix = ['gif', 'jpg', 'png', 'jpeg', 'bmp', 'swf'];
        $this->smarty->assign('menu_select', ['action' => '02_promotion', 'current' => '09_topic']);
        /* ------------------------------------------------------ */
        //-- 专题列表页面
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            admin_priv('topic_manage');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['09_topic']);
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);
            $this->smarty->assign('full_page', 1);
            $list = $this->get_topic_list();
            //页面分菜单 by wu start
            $tab_menu = [];
            $tab_menu[] = ['curr' => 1, 'text' => $GLOBALS['_LANG']['09_topic'], 'href' => 'topic.php?act=list'];
            // $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['mobile_topic'], 'href' => 'touch_topic.php?act=list'];
            $this->smarty->assign('tab_menu', $tab_menu);
            //页面分菜单 by wu end
            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('topic_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['topic_add'], 'href' => 'topic.php?act=add', 'class' => 'icon-plus']);
            return $this->smarty->display('topic_list.dwt');
        }
        /* 添加,编辑 */
        if ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            admin_priv('topic_manage');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);

            $isadd = $_REQUEST['act'] == 'add';
            $this->smarty->assign('isadd', $isadd);
            $topic_id = empty($_REQUEST['topic_id']) ? 0 : intval($_REQUEST['topic_id']);


            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['09_topic']);
            $this->smarty->assign('action_link', $this->list_link($isadd));

            set_default_filter(0, 0, $adminru['ru_id']); //by wu
            $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang']);
            $this->smarty->assign('topic_style_color', $topic_style_color);

            $width_height = $this->get_toppic_width_height();
            if (isset($width_height['pic']['width']) && isset($width_height['pic']['height'])) {
                $this->smarty->assign('width_height', sprintf($GLOBALS['_LANG']['tips_width_height'], $width_height['pic']['width'] . 'px', $width_height['pic']['height'] . 'px'));
            }
            if (isset($width_height['title_pic']['width']) && isset($width_height['title_pic']['height'])) {
                $this->smarty->assign('title_width_height', sprintf($GLOBALS['_LANG']['tips_title_width_height'], $width_height['title_pic']['width'] . 'px', $width_height['title_pic']['height'] . 'px'));
            }

            if (!$isadd) {
                $sql = "SELECT * FROM " . $this->dsc->table('topic') . " WHERE topic_id = '$topic_id' LIMIT 1";
                $topic = $this->db->getRow($sql);
                $topic['start_time'] = local_date('Y-m-d H:i:s', $topic['start_time']);
                $topic['end_time'] = local_date('Y-m-d H:i:s', $topic['end_time']);

                $this->smarty->assign('topic', $topic);
                $this->smarty->assign('act', "update");

                if ($topic['user_id'] != $adminru['ru_id']) {
                    $Loaction = "topic.php?act=list";
                    return dsc_header("Location: $Loaction\n");
                }
            } else {
                $topic = ['title' => '', 'topic_type' => 0, 'url' => 'http://'];
                $topic['start_time'] = local_date('Y-m-d H:i:s', time() + 86400);
                $topic['end_time'] = local_date('Y-m-d H:i:s', time() + 4 * 86400);
                $this->smarty->assign('topic', $topic);

                $this->smarty->assign('act', "insert");
            }
            return $this->smarty->display('topic_edit.dwt');
        } elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            admin_priv('topic_manage');

            $is_insert = $_REQUEST['act'] == 'insert';
            $topic_id = isset($_POST['topic_id']) && !empty($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
            $topic_type = isset($_POST['topic_type']) && !empty($_POST['topic_type']) ? intval($_POST['topic_type']) : 0;
            $topic_name = isset($_POST['topic_name']) && !empty($_POST['topic_name']) ? trim($_POST['topic_name']) : '';

            $start_time = local_strtotime($_POST['start_time']);
            $end_time = local_strtotime($_POST['end_time']);

            $keywords = $_POST['keywords'];
            $description = $_POST['description'];

            /* 插入数据 */
            $record = [
                'title' => $topic_name,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'keywords' => $keywords,
                'description' => $description
            ];

            if ($is_insert) {
                $record['user_id'] = $adminru['ru_id'];
                $this->db->AutoExecute($this->dsc->table('topic'), $record, 'INSERT');
            } else {
                $record['review_status'] = 1;

                $this->db->AutoExecute($this->dsc->table('topic'), $record, 'UPDATE', "topic_id = '$topic_id'");
            }

            clear_cache_files();

            $links[] = ['href' => 'topic.php', 'text' => $GLOBALS['_LANG']['back_list']];
            return sys_msg($GLOBALS['_LANG']['succed'], 0, $links);
        }
        /*------------------------------------------------------ */
        //-- 专题可视化 by kong
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'visual') {
            $topic_id = !isset($_REQUEST['topic_id']) && empty($_REQUEST['topic_id']) ? 0 : intval($_REQUEST['topic_id']);

            /**
             * 专题可视化
             * 下载OSS模板文件
             */
            get_down_topictemplates($topic_id, $adminru['ru_id']);

            $arr['tem'] = "topic_" . $topic_id;
            //如果存在缓存文件  ，调用缓存文件
            $des = storage_public('data/topic' . '/topic_' . $adminru['ru_id'] . "/" . $arr['tem']);
            if (file_exists($des . "/temp/pc_page.php")) {
                $filename = $des . "/temp/pc_page.php";
                $is_temp = 1;
            } else {
                $filename = $des . '/pc_page.php';
            }
            $arr['out'] = get_html_file($filename);

            $sql = "SELECT user_id FROM " . $this->dsc->table('topic') . " WHERE topic_id = '$topic_id' LIMIT 1";
            $topic = $this->db->getRow($sql);

            if ($topic['user_id'] != $adminru['ru_id']) {
                $Loaction = "topic.php?act=list";
                return dsc_header("Location: $Loaction\n");
            }

            if ($GLOBALS['_CFG']['open_oss'] == 1) {
                $bucket_info = $this->dscRepository->getBucketInfo();
                $endpoint = $bucket_info['endpoint'];
            } else {
                $endpoint = url('/');
            }

            if ($arr['out']) {
                $desc_preg = get_goods_desc_images_preg($endpoint, $arr['out']);
                $arr['out'] = $desc_preg['goods_desc'];
            }

            //判断是否是新模板
            $this->smarty->assign('theme_extension', 1);
            $domain = $this->dsc->seller_url();
            /*获取左侧储存值*/
            $head = getleft_attr("head", $adminru['ru_id'], $arr['tem']);
            $content = getleft_attr("content", $adminru['ru_id'], $arr['tem']);
            $this->smarty->assign('head', $head);
            $this->smarty->assign('content', $content);
            $this->smarty->assign('pc_page', $arr);
            $this->smarty->assign('domain', $domain);
            $this->smarty->assign('topic_id', $topic_id);
            $this->smarty->assign('topic_type', "topic_type");
            $this->smarty->assign('vis_section', "vis_seller_topic");
            //更新状态审核状态
            $record['review_status'] = 1;
            $this->db->AutoExecute($this->dsc->table('topic'), $record, 'UPDATE', "topic_id = '$topic_id'");

            return $this->smarty->display("visual_editing.dwt");
        } elseif ($_REQUEST["act"] == "delete") {
            admin_priv('topic_manage');

            //删除图片
            $this->dscRepository->getDelBatch($_POST['checkboxes'], intval($_GET['id']), ['topic_img', 'title_pic'], 'topic_id', Topic::whereRaw(1), 1);

            $sql = "DELETE FROM " . $this->dsc->table('topic') . " WHERE ";
            if (!empty($_POST['checkboxes'])) {
                $is_use = 0;
                foreach ($_POST['checkboxes'] as $v) {
                    $sql_v = "SELECT * FROM " . $this->dsc->table('topic') . " WHERE topic_id = '$v' LIMIT 1";
                    $topic = $this->db->getRow($sql_v);

                    if ($topic['user_id'] != $adminru['ru_id']) {
                        $is_use = 1;
                        break;
                    }
                }

                if ($is_use == 0) {
                    $sql .= db_create_in($_POST['checkboxes'], 'topic_id');
                }

                //删除对应模板  by kong
                foreach ($_POST['checkboxes'] as $v) {
                    if ($v > 0) {
                        $suffix = "topic_" . $v;
                        $dir = storage_public('data/topic/topic_' . $adminru['ru_id'] . "/" . $suffix);
                        $rmdir = getDelDirAndFile($dir);
                    }
                }
            } elseif (!empty($_GET['id'])) {
                $_GET['id'] = intval($_GET['id']);

                $sql_v = "SELECT * FROM " . $this->dsc->table('topic') . " WHERE topic_id = '" . $_GET['id'] . "' LIMIT 1";
                $topic = $this->db->getRow($sql_v);
                if ($topic['user_id'] != $adminru['ru_id']) {
                }

                $sql .= "topic_id = '$_GET[id]'";
                //删除对应模板  by kong
                $suffix = "topic_" . $_GET['id'];
                $dir = storage_public('data/topic/topic_' . $adminru['ru_id'] . "/" . $suffix);
                $rmdir = getDelDirAndFile($dir);
            } else {
            }

            $this->db->query($sql);

            clear_cache_files();

            if (!empty($_REQUEST['is_ajax'])) {
                $url = 'topic.php?act=query&' . str_replace('act=delete', '', request()->server('QUERY_STRING'));
                return dsc_header("Location: $url\n");
            }

            $links[] = ['href' => 'topic.php', 'text' => $GLOBALS['_LANG']['back_list']];
            return sys_msg($GLOBALS['_LANG']['succed'], 0, $links);
        } elseif ($_REQUEST["act"] == "query") {
            $topic_list = $this->get_topic_list();

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($topic_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('topic_list', $topic_list['item']);
            $this->smarty->assign('filter', $topic_list['filter']);
            $this->smarty->assign('record_count', $topic_list['record_count']);
            $this->smarty->assign('page_count', $topic_list['page_count']);
            $this->smarty->assign('use_storage', empty($GLOBALS['_CFG']['use_storage']) ? 0 : 1);

            /* 排序标记 */
            $sort_flag = sort_flag($topic_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            $tpl = 'topic_list.dwt';
            return make_json_result($this->smarty->fetch($tpl), '', ['filter' => $topic_list['filter'], 'page_count' => $topic_list['page_count']]);
        } //获取可视化头部文件
        elseif ($_REQUEST['act'] == 'get_hearder_body') {
            $result = ['error' => '', 'message' => ''];

            $this->smarty->assign("topic_type", 'topic_type');
            $this->smarty->assign("hearder_body", 1);
            $result['content'] = $GLOBALS['smarty']->fetch('library/pc_page.lbi');
            return response()->json($result);
        } //还原
        elseif ($_REQUEST['act'] == 'backmodal') {
            $result = ['error' => '', 'message' => ''];
            $code = isset($_REQUEST['suffix']) ? trim($_REQUEST['suffix']) : '';
            $topic_type = isset($_REQUEST['topic_type']) ? trim($_REQUEST['topic_type']) : '';
            if ($topic_type == 'topic_type') {
                $dir = storage_public("data/topic/topic_" . $adminru['ru_id'] . "/" . $code . "/temp");//原目录
            } else {
                $dir = storage_public('data/seller_templates/seller_tem_' . $adminru['ru_id'] . '/' . $code . "/temp");//原模板目录
            }
            if (!empty($code)) {
                getDelDirAndFile($dir);//删除缓存文件
                $result['error'] = 0;
            }
            return response()->json($result);
        }
    }

    /**
     * 获取专题列表
     * @access  public
     * @return void
     */
    private function get_topic_list()
    {
        $adminru = get_admin_ru_id();

        $result = get_filter();
        if ($result === false) {

            /* 查询条件 */
            $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
            if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
                $filter['keywords'] = json_str_iconv($filter['keywords']);
            }

            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 't.topic_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $filter['review_status'] = empty($_REQUEST['review_status']) ? 0 : intval($_REQUEST['review_status']);

            $where = "1";
            $where .= (!empty($filter['keywords'])) ? " AND t.title like '%" . mysql_like_quote($filter['keywords']) . "%'" : '';

            if ($adminru['ru_id'] > 0) {
                $where .= " AND t.user_id = '" . $adminru['ru_id'] . "' ";
            }

            if ($filter['review_status']) {
                $where .= " AND t.review_status = '" . $filter['review_status'] . "' ";
            }

            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('topic') . " AS t " . " WHERE $where";
            $filter['record_count'] = $this->db->getOne($sql);

            /* 分页大小 */
            $filter = page_and_size($filter);

            $sql = "SELECT t.* FROM " . $this->dsc->table('topic') . " AS t " . " WHERE $where ORDER BY $filter[sort_by] $filter[sort_order]";

            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $query = $this->db->selectLimit($sql, $filter['page_size'], $filter['start']);

        $res = [];

        foreach ($query as $topic) {
            $topic['start_time'] = local_date('Y-m-d H:i:s', $topic['start_time']);
            $topic['end_time'] = local_date('Y-m-d H:i:s', $topic['end_time']);
            $topic['url'] = $this->dsc->seller_url() . 'topic.php?topic_id=' . $topic['topic_id'];
            $topic['ru_name'] = $this->merchantCommonService->getShopName($topic['user_id'], 1);
            $res[] = $topic;
        }

        $arr = ['item' => $res, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 列表链接
     * @param bool $is_add 是否添加（插入）
     * @param string $text 文字
     * @return  array('href' => $href, 'text' => $text)
     */
    private function list_link($is_add = true, $text = '')
    {
        $href = 'topic.php?act=list';
        if (!$is_add) {
            $href .= '&' . list_link_postfix();
        }
        if ($text == '') {
            $text = $GLOBALS['_LANG']['topic_list'];
        }

        return ['href' => $href, 'text' => $text, 'class' => 'icon-reply'];
    }

    private function get_toppic_width_height()
    {
        $width_height = [];

        $file_path = app_path('Modules/Seller/Views/topic.dwt');
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return $width_height;
        }

        $string = file_get_contents($file_path);

        $pattern_width = '/var\s*topic_width\s*=\s*"(\d+)";/';
        $pattern_height = '/var\s*topic_height\s*=\s*"(\d+)";/';
        preg_match($pattern_width, $string, $width);
        preg_match($pattern_height, $string, $height);
        if (isset($width[1])) {
            $width_height['pic']['width'] = $width[1];
        }
        if (isset($height[1])) {
            $width_height['pic']['height'] = $height[1];
        }
        unset($width, $height);

        $pattern_width = '/TitlePicWidth:\s{1}(\d+)/';
        $pattern_height = '/TitlePicHeight:\s{1}(\d+)/';
        preg_match($pattern_width, $string, $width);
        preg_match($pattern_height, $string, $height);
        if (isset($width[1])) {
            $width_height['title_pic']['width'] = $width[1];
        }
        if (isset($height[1])) {
            $width_height['title_pic']['height'] = $height[1];
        }

        return $width_height;
    }
}
