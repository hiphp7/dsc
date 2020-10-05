<?php

namespace App\Http\Controllers;

use App\Libraries\CaptchaVerify;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Users;
use App\Repositories\Common\DscRepository;
use App\Services\Article\ArticleCommonService;
use App\Services\Common\TemplateService;
use App\Services\User\UserMessageService;

/**
 * 留言板
 */
class MessageController extends InitController
{
    protected $articleCommonService;
    protected $userMessageService;
    protected $dscRepository;
    protected $templateService;

    public function __construct(
        ArticleCommonService $articleCommonService,
        UserMessageService $userMessageService,
        DscRepository $dscRepository,
        TemplateService $templateService
    )
    {
        $this->articleCommonService = $articleCommonService;
        $this->userMessageService = $userMessageService;
        $this->dscRepository = $dscRepository;
        $this->templateService = $templateService;
    }


    public function index()
    {
        if (empty($GLOBALS['_CFG']['message_board'])) {
            return show_message($GLOBALS['_LANG']['message_board_close']);
        }

        $this->smarty->assign('category', 9999999999999999999);

        $user_id = session('user_id', 0);

        /* 过滤 XSS 攻击和SQL注入 */
        get_request_filter();

        //判断是否有ajax请求

        $result = ['err_msg' => '', 'err_no' => 0, 'content' => ''];

        $action = addslashes(request()->input('act', 'default'));
        $action = $action ? $action : 'default';

        $cur_time = gmtime();
        if ($action == 'act_add_message') {
            /* 过滤 XSS 攻击和SQL注入 */
            $_POST = get_request_filter($_POST, 1);

            load_helper('clips');

            /* 验证码防止灌水刷屏 */
            if ((intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_MESSAGE) && gd_version() > 0) {
                $captcha_str = addslashes(trim(request()->input('captcha', '')));

                $verify = app(CaptchaVerify::class);
                $captcha_code = $verify->check($captcha_str, 'captcha_common');
                if (!$captcha_code) {
                    return show_message($GLOBALS['_LANG']['invalid_captcha']);
                }
            } else {
                /* 没有验证码时，用时间来限制机器人发帖或恶意发评论 */
                if (!session()->has('send_time')) {
                    session([
                        'send_time' => 0
                    ]);
                }

                if (($cur_time - session('send_time')) < 30) { // 小于30秒禁止发评论
                    return show_message($GLOBALS['_LANG']['cmt_spam_warning']);
                }
            }
            $get_user_name = request()->input('user_name', '');
            $anonymous = request()->input('anonymous', '');
            if (empty($anonymous) && !empty(session('user_name'))) {
                $user_name = session('user_name', '');
            } elseif (!empty($anonymous) && !request()->exists('user_name')) {
                $user_name = $GLOBALS['_LANG']['anonymous'];
            } elseif (empty($get_user_name)) {
                $user_name = $GLOBALS['_LANG']['anonymous'];
            } else {
                $user_name = htmlspecialchars(trim($get_user_name));
            }

            if (empty($user_id)) {
                return show_message($GLOBALS['_LANG']['login_please']);
            }

            $user_email = compile_str(addslashes_deep(request()->input('user_email', '')));
            $msg_title = compile_str(addslashes_deep(request()->input('msg_title', '')));
            $msg_content = compile_str(addslashes_deep(request()->input('msg_content', '')));

            $message = [
                'user_id' => $user_id,
                'user_name' => $user_name,
                'user_email' => htmlspecialchars(trim($user_email)),
                'msg_type' => (int)request()->input('msg_type', 0),
                'msg_title' => trim($msg_title),
                'msg_content' => trim($msg_content),
                'order_id' => 0,
                'msg_area' => 1,
                'upload' => []
            ];
            if (add_message($message)) {
                session([
                    'send_time' => $cur_time
                ]);
                $msg_info = $GLOBALS['_CFG']['message_check'] ? $GLOBALS['_LANG']['message_submit_wait'] : $GLOBALS['_LANG']['message_submit_done'];
                return show_message($msg_info, $GLOBALS['_LANG']['message_list_lnk'], 'message.php');
            } else {
                return $this->err->show($GLOBALS['_LANG']['message_list_lnk'], 'message.php');
            }
        }

        if ($action == 'default') {
            assign_template();
            $position = assign_ur_here(0, $GLOBALS['_LANG']['message_board']);
            $this->smarty->assign('page_title', $position['title']);    // 页面标题
            $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置
            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助

            $categories_pro = get_category_tree_leve_one();
            $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版

            $this->smarty->assign('brand_list', get_brand_list());
            $this->smarty->assign('enabled_mes_captcha', (intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_MESSAGE));

            $record_count = Comment::where('status', 1)->where('comment_type', 0)->where('id_value', 0)->count();

            /* 获取留言的数量 */
            $page = (int)request()->input('page', 1);

            $pagesize = $this->templateService->getLibraryNumber('message_list', 'message_board');
            $pager = get_pager('message.php', [], $record_count, $page, $pagesize);
            $msg_lists = $this->userMessageService->getMsgList($pagesize, $pager['start']);
            assign_dynamic('message_board');
            $this->smarty->assign('rand', mt_rand());
            $this->smarty->assign('msg_lists', $msg_lists);
            $this->smarty->assign('pager', $pager);
            $this->smarty->assign('user_id', $user_id);

            if (session('user_id')) {

                $user_info = Users::where('user_id', session('user_id'));
                $user_info = $user_info ? $user_info->first() : [];
                $user_info['user_picture'] = isset($user_info['user_picture']) && !empty($user_info['user_picture']) ? $this->dscRepository->getImagePath($user_info['user_picture']) : '';

                $this->smarty->assign('user_info', $user_info);
            }

            /* 验证码相关设置 */
            if ((intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_MESSAGE) && gd_version() > 0) {
                $this->smarty->assign('enabled_captcha', 1);
                $this->smarty->assign('rand', mt_rand());
            }

            return $this->smarty->display('message_board.dwt');
        } elseif ($action == 'cat_tree_two') {
            $cat_id = (int)request()->input('id', 0);

            $parent_id = Category::where('cat_id', $cat_id)->value('parent_id');
            $parentCat = Category::where('cat_id', $parent_id)->value('parent_id');

            $this->smarty->assign('category', $cat_id);
            $this->smarty->assign('parent_id', $parent_id);
            $this->smarty->assign('parentCat', $parentCat);

            $tree_arr = [0, 'goodsList'];
            $categories_pro2 = get_cache_site_file('category_tree2', $tree_arr);
            $this->smarty->assign('categories_pro2', $categories_pro2); // 分类树加强版/* 周改 */
            $result['content'] = $this->smarty->fetch("library/secondlevel_cat_tree2.lbi");

            return response()->json($result);
        }
    }
}
