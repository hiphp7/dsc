<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Image;
use App\Models\AdminUser;
use App\Models\Comment;
use App\Models\DiscussCircle;
use App\Models\Goods;
use App\Models\GoodsGallery;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\DiscussCircle\DiscussCircleManageService;
use App\Services\Merchant\MerchantCommonService;

/**
 * 用户评论管理程序
 */
class DiscussCircleController extends InitController
{
    protected $merchantCommonService;
    protected $baseRepository;
    protected $discussCircleManageService;
    protected $dscRepository;
    protected $commonRepository;

    public function __construct(
        MerchantCommonService $merchantCommonService,
        BaseRepository $baseRepository,
        DiscussCircleManageService $discussCircleManageService,
        DscRepository $dscRepository,
        CommonRepository $commonRepository
    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->baseRepository = $baseRepository;
        $this->discussCircleManageService = $discussCircleManageService;
        $this->dscRepository = $dscRepository;
        $this->commonRepository = $commonRepository;
    }

    public function index()
    {
        load_helper('goods', 'admin');

        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        /*------------------------------------------------------ */
        //-- 获取没有回复的评论列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 检查权限 */
            admin_priv('discuss_circle');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['discuss_circle']);
            $this->smarty->assign('full_page', 1);

            $list = $this->discussCircleManageService->getDiscussList($adminru['ru_id']);

            $this->smarty->assign('discuss_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['discuss_add'], 'href' => 'discuss_circle.php?act=add']);

            return $this->smarty->display('discuss_list.dwt');
        }


        /*------------------------------------------------------ */
        //-- 主题添加页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add') {
            admin_priv('discuss_circle');

            /* 创建 html editor */
            create_html_editor('content');
            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['discuss_add']);
            $this->smarty->assign('action_link', ['href' => 'discuss_circle.php?act=list', 'text' => $GLOBALS['_LANG']['discuss_circle']]);
            $this->smarty->assign('action', 'add');

            $this->smarty->assign('act', 'insert');
            $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang']);

            return $this->smarty->display('discuss_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 主题添加的处理
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'insert') {
            $data['goods_id'] = !empty($_POST['goods_id']) ? trim($_POST['goods_id']) : 0;
            $data['dis_title'] = !empty($_POST['dis_title']) ? trim($_POST['dis_title']) : '';
            $data['dis_text'] = !empty($_POST['content']) ? addslashes(trim($_POST['content'])) : '';
            $data['user_name'] = !empty($_POST['user_name']) ? trim($_POST['user_name']) : '';
            $data['dis_type'] = !empty($_POST['discuss_type']) ? intval($_POST['discuss_type']) : 0;
            $img_desc = !empty($_POST['img_desc']) ? trim($_POST['img_desc']) : '';
            $img_file = !empty($_POST['img_file']) ? trim($_POST['img_file']) : '';

            $res = Users::where('user_name', $data['user_name'])->select('user_id', 'user_name');
            $user = $this->baseRepository->getToArrayFirst($res);

            if (count($user) <= 0) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['type_name_exist'], 0, $link);
            }

            $data['add_time'] = gmtime();
            $data['user_id'] = $user['user_id'];

            $_FILES['img_url'] = isset($_FILES['img_url']) ? $_FILES['img_url'] : '';

            if ($_FILES['img_url']) {
                foreach ($_FILES['img_url']['error'] as $key => $value) {
                    if ($value == 0) {
                        if (!$image->check_img_type($_FILES['img_url']['type'][$key])) {
                            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                            return sys_msg($GLOBALS['_LANG']['invalid_img_url'], 0, $link);
                        }
                    } elseif ($value == 1) {
                        $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                        return sys_msg($GLOBALS['_LANG']['img_url_too_big'], 0, $link);
                    } elseif ($_FILES['img_url']['error'] == 2) {
                        $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                        return sys_msg($GLOBALS['_LANG']['img_url_too_big'], 0, $link);
                    }
                }

                // 相册图片
                foreach ($_FILES['img_url']['tmp_name'] as $key => $value) {
                    if ($value != 'none') {
                        if (!$image->check_img_type($_FILES['img_url']['type'][$key])) {
                            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                            return sys_msg($GLOBALS['_LANG']['invalid_img_url'], 0, $link);
                        }
                    }
                }
            }

            /* 插入数据库。 */
            $dis_id = DiscussCircle::insertGetId($data);

            /* 处理相册图片 */
            if ($_FILES['img_url']) {
                if (!empty($dis_id)) {
                    handle_gallery_image(0, $_FILES['img_url'], $img_desc, $img_file, 0, $dis_id, 'true');
                } else {
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                    return sys_msg($GLOBALS['_LANG']['dis_error'], 0, $link);
                }
            }

            /* 记录管理员操作 */
            admin_log($data['dis_title'], 'add', 'discussinsert');

            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            $link[0]['text'] = $GLOBALS['_LANG']['discuss_add'];
            $link[0]['href'] = 'discuss_circle.php?act=add';

            $link[1]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[1]['href'] = 'discuss_circle.php?act=list';

            return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . $data['dis_title'] . "&nbsp;" . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 主题修改的处理
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'update') {
            $dis_id = !empty($_POST['dis_id']) ? trim($_POST['dis_id']) : 0;

            if (empty($dis_id)) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['discuss_exits'], 0, $link);
            }

            $data['dis_title'] = !empty($_POST['dis_title']) ? trim($_POST['dis_title']) : '';
            $data['dis_text'] = !empty($_POST['content']) ? addslashes(trim($_POST['content'])) : '';

            $data['dis_type'] = !empty($_POST['discuss_type']) ? $_POST['discuss_type'] : 1;

            $data['review_status'] = !empty($_REQUEST['review_status']) ? intval($_REQUEST['review_status']) : 1;
            $data['review_content'] = !empty($_REQUEST['review_content']) ? trim($_REQUEST['review_content']) : '';

            $data['add_time'] = gmtime();

            /* 插入数据库。 */
            DiscussCircle::where('dis_id', $dis_id)->update($data);
            /* 记录管理员操作 */
            admin_log($data['dis_title'], 'add', 'discussinsert');

            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            $link[0]['text'] = $GLOBALS['_LANG']['discuss_edit'];
            $link[0]['href'] = "discuss_circle.php?act=reply&id=$dis_id";

            $link[1]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[1]['href'] = 'discuss_circle.php?act=list';

            return sys_msg($GLOBALS['_LANG']['edit'] . "&nbsp;" . $data['dis_title'] . "&nbsp;" . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
        }


        /*------------------------------------------------------ */
        //-- 翻页、搜索、排序
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'query') {
            $list = $this->discussCircleManageService->getDiscussList($adminru['ru_id']);

            $this->smarty->assign('discuss_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('discuss_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 回复用户评论(同时查看评论详情        )
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'reply') {
            /* 检查权限 */
            admin_priv('discuss_circle');

            $id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $discuss_info = [];
            $id_value = [];

            /* 获取评论详细信息并进行字符处理 */
            $res = DiscussCircle::where('dis_id', $id);
            $discuss_info = $this->baseRepository->getToArrayFirst($res);

            if (empty($discuss_info)) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['discuss_exits'], 0, $link);
            }

            $discuss_info['dis_title'] = str_replace('\r\n', '<br />', htmlspecialchars($discuss_info['dis_title']));
            $discuss_info['dis_title'] = nl2br(str_replace('\n', '<br />', $discuss_info['dis_title']));
            $discuss_info['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $discuss_info['add_time']);

            //取得商品名称
            $goods = Goods::where('goods_id', $discuss_info['goods_id'])->select('goods_name', 'original_img');
            $goods = $this->baseRepository->getToArrayFirst($goods);

            $discuss_info['original_img'] = isset($goods['original_img']) ? get_image_path($goods['original_img']) : '';
            $discuss_info['goods_name'] = $goods['goods_name'];

            //取得图片地址
            $imgs = GoodsGallery::where('dis_id', $discuss_info['dis_id']);
            $imgs = $this->baseRepository->getToArrayFirst($imgs);

            /* 获取管理员的用户名和Email地址 */
            $admin_info = AdminUser::where('user_id', session('admin_id'))->select('user_name', 'email');
            $admin_info = $this->baseRepository->getToArrayFirst($admin_info);

            /* 取得评论的对象(文章或者商品) */
            $id_value = Goods::where('goods_id', $discuss_info['goods_id'])->value('goods_name');

            /* 创建 html editor */
            $content = isset($discuss_info['dis_text']) ? $discuss_info['dis_text'] : '';
            create_html_editor('content', $content);


            $this->smarty->assign('imgs', $imgs);
            $this->smarty->assign('msg', $discuss_info); //评论信息
            $this->smarty->assign('admin_info', $admin_info);   //管理员信息
            $this->smarty->assign('act', 'update');  //评论的对象
            $this->smarty->assign('action', 'relpy');  // 仅查看

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['discuss_info']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['discuss_circle'],
                'href' => 'discuss_circle.php?act=list']);

            /* 页面显示 */

            return $this->smarty->display('discuss_info.dwt');
        }
        /*------------------------------------------------------ */
        //-- 处理 回复用户评论
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'action') {
            admin_priv('discuss_circle');

            /* 获取IP地址 */
            $ip = $this->dscRepository->dscIp();

            $comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
            $data['email'] = isset($_POST['email']) ? trim($_POST['email']) : '';
            $data['content'] = isset($_POST['content']) ? trim($_POST['content']) : '';
            $data['add_time'] = gmtime();
            $data['ip_address'] = $ip;
            $data['status'] = 0;

            $send_email_notice = isset($_POST['send_email_notice']) ? trim($_POST['send_email_notice']) : '';
            $remail = isset($_POST['remail']) ? trim($_POST['remail']) : '';

            /* 获得评论是否有回复 */
            $res = Comment::where('parent_id', $comment_id)->select('comment_id', 'content', 'parent_id');
            $reply_info = $this->baseRepository->getToArrayFirst($res);

            if (!empty($reply_info['content'])) {
                $data['user_name'] = isset($_POST['user_name']) ? trim($_POST['user_name']) : '';
                /* 更新回复的内容 */
                Comment::where('comment_id', $reply_info['comment_id'])->update($data);
            } else {
                $data['comment_type'] = isset($_POST['comment_type']) ? intval($_POST['comment_type']) : 0;
                $data['id_value'] = isset($_POST['id_value']) ? intval($_POST['id_value']) : 0;
                $data['user_name'] = session()->has('admin_name') ? trim(session('admin_name')) : '';
                $data['parent_id'] = $comment_id;

                /* 插入回复的评论内容 */
                Comment::insert($data);
            }

            /* 更新当前的评论状态为已回复并且可以显示此条评论 */
            $update_data = ['status' => 1];
            Comment::where('comment_id', $comment_id)->update($update_data);

            /* 邮件通知处理流程 */
            if (!empty($send_email_notice) or isset($remail)) {
                //获取邮件中的必要内容
                $res = Comment::where('comment_id', $comment_id)->select('user_name', 'email', 'content');
                $comment_info = $this->baseRepository->getToArrayFirst($res);

                /* 设置留言回复模板所需要的内容信息 */
                $template = get_mail_template('recomment');

                $this->smarty->assign('user_name', $comment_info['user_name']);
                $this->smarty->assign('recomment', $data['content']);
                $this->smarty->assign('comment', $comment_info['content']);
                $this->smarty->assign('shop_name', "<a href='" . $this->dsc->url() . "'>" . $GLOBALS['_CFG']['shop_name'] . '</a>');
                $this->smarty->assign('send_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));

                $content = $this->smarty->fetch('str:' . $template['template_content']);

                /* 发送邮件 */
                if ($this->commonRepository->sendEmail($comment_info['user_name'], $comment_info['email'], $template['template_subject'], $content, $template['is_html'])) {
                    $send_ok = 0;
                } else {
                    $send_ok = 1;
                }
            }

            /* 清除缓存 */
            clear_cache_files();

            /* 记录管理员操作 */
            admin_log(addslashes($GLOBALS['_LANG']['reply']), 'edit', 'users_comment');

            return dsc_header("Location: comment_manage.php?act=reply&id=$comment_id&send_ok=$send_ok\n");
        }
        /*------------------------------------------------------ */
        //-- 更新评论的状态为显示或者        禁止
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'check') {
            $comment_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $check = isset($_REQUEST['check']) ? trim($_REQUEST['check']) : '';
            if ($check == 'allow') {
                /* 允许评论显示 */
                $data = ['status' => 1];
                Comment::where('comment_id', $comment_id)->update($data);
                //add_feed($_REQUEST['id'], COMMENT_GOODS);

                /* 清除缓存 */
                clear_cache_files();

                return dsc_header("Location: comment_manage.php?act=reply&id=$comment_id\n");
            } else {
                /* 禁止评论显示 */
                $data = ['status' => 0];
                Comment::where('comment_id', $comment_id)->update($data);
                /* 清除缓存 */
                clear_cache_files();

                return dsc_header("Location: comment_manage.php?act=reply&id=$comment_id\n");
            }
        }

        /*------------------------------------------------------ */
        //-- 删除某一条评论
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('discuss_circle');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $dis_id = isset($_GET['dis_id']) ? intval($_GET['dis_id']) : 0;

            DiscussCircle::where('dis_id', $id)->delete();
            admin_log('', 'remove', 'ads');

            if ($dis_id) {
                $query = "discuss_reply_query";
            } else {
                $query = "query";
            }
            $url = 'discuss_circle.php?act=' . $query . '&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 批量删除
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'batch') {
            admin_priv('discuss_circle');

            $dis_id = isset($_POST['dis_id']) ? trim($_POST['dis_id']) : 0;

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['not_select_date'], 1);
            }

            $checkboxes = $this->baseRepository->getExplode($_POST['checkboxes']);

            if (isset($_POST['type']) && !empty($_POST['type'])) {
                // 删除
                if ($_POST['type'] == 'batch_remove') {
                    DiscussCircle::whereIn('dis_id', $checkboxes)->delete();
                    clear_cache_files();

                    $action = ($_POST['type'] == 'batch_remove') ? 'batch_remove' : 'edit';
                    admin_log('', $action, 'adminlog');

                    if ($dis_id > 0) {
                        $href = "discuss_circle.php?act=user_reply&id=" . $dis_id;
                        $back_list = $GLOBALS['_LANG']['discuss_user_reply'];
                    } else {
                        $href = "discuss_circle.php?act=list";
                        $back_list = $GLOBALS['_LANG']['back_list'];
                    }

                    $link[] = ['text' => $back_list, 'href' => $href];
                    return sys_msg(sprintf($GLOBALS['_LANG']['batch_drop_success'], count($_POST['checkboxes'])), 0, $link);
                } // 审核
                elseif ($_POST['type'] == 'review_to') {

                    // review_status = 2审核未通过 3审核通过
                    $review_status = $_POST['review_status'];

                    $data = ['review_status' => $review_status];
                    $res = DiscussCircle::whereIn('dis_id', $checkboxes)->update($data);
                    if ($res) {
                        if ($dis_id > 0) {
                            $href = "discuss_circle.php?act=user_reply&id=" . $dis_id;
                            $back_list = $GLOBALS['_LANG']['discuss_user_reply'];
                        } else {
                            $href = "discuss_circle.php?act=list";
                            $back_list = $GLOBALS['_LANG']['back_list'];
                        }

                        $link[] = ['text' => $back_list, 'href' => $href];
                        return sys_msg($GLOBALS['_LANG']['adopt_status_set_success'], 0, $link);
                    }
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 获取没有回复的评论列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'user_reply') {
            /* 检查权限 */
            admin_priv('discuss_circle');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['discuss_user_reply']);
            $this->smarty->assign('full_page', 1);

            $list = $this->discussCircleManageService->getDiscussUserReplyList();

            $this->smarty->assign('reply_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);
            $this->smarty->assign('dis_id', $list['dis_id']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('discuss_user_reply.dwt');
        }

        /*------------------------------------------------------ */
        //-- 翻页、搜索、排序
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'discuss_reply_query') {
            $list = $this->discussCircleManageService->getDiscussUserReplyList();

            $this->smarty->assign('reply_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);
            $this->smarty->assign('dis_id', $list['dis_id']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('discuss_user_reply.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }
    }
}
