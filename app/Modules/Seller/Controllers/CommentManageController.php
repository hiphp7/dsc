<?php

namespace App\Modules\Seller\Controllers;

use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;

/**
 * 用户评论管理程序
 */
class CommentManageController extends InitController
{
    protected $merchantCommonService;
    protected $dscRepository;
    protected $commonRepository;

    public function __construct(
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService,
        CommonRepository $commonRepository
    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
        $this->commonRepository = $commonRepository;
    }

    public function index()
    {

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }
        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('action_type', "goods");
        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        $user_action_list = get_user_action_list(session('seller_id'));

        $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => '05_comment_manage']);

        /*------------------------------------------------------ */
        //-- 获取没有回复的评论列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 检查权限 */
            admin_priv('comment_priv');

            //商家单个权限 ecmoban模板堂 start
            $comment_edit_delete = get_merchants_permissions($user_action_list, 'comment_edit_delete');
            $this->smarty->assign('comment_edit_delete', $comment_edit_delete); //退换货权限
            //商家单个权限 ecmoban模板堂 end
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['05_comment_manage']);
            $this->smarty->assign('full_page', 1);

            $list = $this->get_comment_list($adminru['ru_id']);

            $this->smarty->assign('comment_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('comment_list.dwt');
        }

        //@author guan start
        /*------------------------------------------------------ */
        //-- 用户晒单列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'single_list') {
            /* 检查权限 */
            admin_priv('single_manage');

            load_helper('order');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['single_manage']);
            $this->smarty->assign('full_page', 1);

            //商家单个权限 ecmoban模板堂 start
            $single_edit_delete = get_merchants_permissions($user_action_list, 'single_edit_delete');
            $this->smarty->assign('single_edit_delete', $single_edit_delete); //退换货权限
            //商家单个权限 ecmoban模板堂 end

            $list = $this->get_single_list($adminru['ru_id']);

            $this->smarty->assign('single_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('single_list.htm');
        }
        //@author guan end

        /*------------------------------------------------------ */
        //-- 翻页、搜索、排序
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'query') {
            /* 检查权限 */
            admin_priv('comment_priv');

            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

            $list = $this->get_comment_list($adminru['ru_id']);

            //分页
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('comment_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            //商家单个权限 ecmoban模板堂 start
            $comment_edit_delete = get_merchants_permissions($user_action_list, 'comment_edit_delete');
            $this->smarty->assign('comment_edit_delete', $comment_edit_delete); //退换货权限
            //商家单个权限 ecmoban模板堂 end

            return make_json_result(
                $this->smarty->fetch('comment_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        } //@author guan start ajax请求晒单 start

        elseif ($_REQUEST['act'] == 'single_query') {
            /* 检查权限 */
            admin_priv('single_manage');

            $list = $this->get_single_list($adminru['ru_id']);

            $this->smarty->assign('single_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            //商家单个权限 ecmoban模板堂 start
            $single_edit_delete = get_merchants_permissions($user_action_list, 'single_edit_delete');
            $this->smarty->assign('single_edit_delete', $single_edit_delete); //退换货权限
            //商家单个权限 ecmoban模板堂 end

            return make_json_result(
                $this->smarty->fetch('single_list.htm'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 回复用户晒单(同时查看晒单详情        )
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'single_reply') {
            /* 检查权限 */
            admin_priv('single_manage');

            $single_info = [];
            $reply_info = [];
            $id_value = [];
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
            /* 获取评论详细信息并进行字符处理 */
            $sql = $sql = "SELECT * FROM " . $this->dsc->table('single') . " WHERE single_id = '$_REQUEST[id]'";
            $single_info = $this->db->getRow($sql);
            $single_info['addtime'] = local_date($GLOBALS['_CFG']['time_format'], $single_info['addtime']);

            $id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $send_ok = isset($_REQUEST['send_ok']) && !empty($_REQUEST['send_ok']) ? addslashes($_REQUEST['send_ok']) : '';

            /* 获得图片 */
            $sql = $sql = "SELECT id, img_file, cont_desc FROM " . $this->dsc->table('single_sun_images') . " WHERE single_id = '$id' order by id DESC";
            $single_img = $this->db->getAll($sql);
            $img_list = [];
            foreach ($single_img as $key => $gallery_img) {
                $img_list[$key]['id'] = $gallery_img['id'];
                $img_list[$key]['img_file'] = $gallery_img['img_file'];
                $img_list[$key]['cont_desc'] = $gallery_img['cont_desc'];
            }
            /* 获取管理员的用户名和Email地址 */
            $sql = "SELECT user_name, email FROM " . $this->dsc->table('admin_user') .
                " WHERE user_id = '" . session('seller_id') . "'";
            $admin_info = $this->db->getRow($sql);

            /* 模板赋值 */
            $this->smarty->assign('msg', $single_info); //评论信息
            $this->smarty->assign('single_img', $img_list); //评论信息
            $this->smarty->assign('admin_info', $admin_info);   //管理员信息

            $this->smarty->assign('send_fail', !empty($send_ok));

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['single_info']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['single_manage'],
                'href' => 'comment_manage.php?act=single_list']);

            //商家单个权限 ecmoban模板堂 start
            $single_edit_delete = get_merchants_permissions($user_action_list, 'single_edit_delete');
            $this->smarty->assign('single_edit_delete', $single_edit_delete); //退换货权限
            //商家单个权限 ecmoban模板堂 end

            /* 页面显示 */

            return $this->smarty->display('single_info.htm');
        }

        /*------------------------------------------------------ */
        //-- 删除图片 by guan
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_single_image') {
            $check_auth = check_authz_json('single_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $img_id = empty($_REQUEST['img_id']) ? 0 : intval($_REQUEST['img_id']);

            /* 删除图片文件 */
            $sql = "SELECT img_file  " . " FROM " . $this->dsc->table('single_sun_images') .
                " WHERE id = '$img_id'";
            $row = $this->db->getRow($sql);

            if ($row['img_file'] != '' && is_file('../' . $row['img_file'])) {
                @unlink('../' . $row['img_file']);
            }

            /* 删除数据 */
            $sql = "DELETE FROM " . $this->dsc->table('single_sun_images') . " WHERE id = '$img_id'";
            $this->db->query($sql);

            clear_cache_files();
            return make_json_result($img_id);
        }


        /*------------------------------------------------------ */
        //-- 晒单状态为显示或者        禁止
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'single_check') {
            /* 检查权限 */
            admin_priv('single_manage');

            $order_id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $goods_id = isset($_REQUEST['goods_id']) && !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $integ = isset($_REQUEST['integ']) && !empty($_REQUEST['integ']) ? floatval($_REQUEST['integ']) : 0;
            $user_id = isset($_REQUEST['user_id']) && !empty($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;

            if ($_REQUEST['check'] == 'allow') {
                $sql = "UPDATE " . $this->dsc->table('order_goods') . " SET is_single = 2 WHERE order_id = '$order_id' AND goods_id = '$goods_id'";
                $this->db->query($sql);
                $sql = "UPDATE " . $this->dsc->table('single') . " SET is_audit = 1, integ='$integ' WHERE order_id = '$order_id'";
                $this->db->query($sql);


                if ($integ) {
                    log_account_change($user_id, $user_money = 0, $frozen_money = 0, $rank_points = 0, $pay_points = $integ, $GLOBALS['_LANG']['show_img_reward']);
                }

                /* 清除缓存 */
                clear_cache_files();

                return dsc_header("Location: comment_manage.php?act=single_list\n");
            } else {
                $sql = "UPDATE " . $this->dsc->table('order_goods') . " SET is_single = 3 WHERE order_id = '$order_id' AND goods_id = '$goods_id'";
                $this->db->query($sql);
                $sql = "UPDATE " . $this->dsc->table('single') . " SET is_audit = 0, integ='-$integ' WHERE order_id = '$order_id'";
                $this->db->query($sql);

                if (!empty($_REQUEST['integ'])) {
                    log_account_change($user_id, $user_money = 0, $frozen_money = 0, $rank_points = 0, $pay_points = -$integ, $GLOBALS['_LANG']['show_img_no_reduce_intergral']);
                }

                /* 清除缓存 */
                clear_cache_files();

                return dsc_header("Location: comment_manage.php?act=single_list\n");
            }
        }
        //@author guan end

        /*------------------------------------------------------ */
        //-- 回复用户评论(同时查看评论详情        )
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'reply') {
            /* 检查权限 */
            admin_priv('comment_priv');

            $comment_id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $send_ok = isset($_REQUEST['send_ok']) && !empty($_REQUEST['send_ok']) ? addslashes($_REQUEST['send_ok']) : '';

            $comment_info = [];
            $reply_info = [];
            $id_value = [];

            /* 获取评论详细信息并进行字符处理 */
            $sql = "SELECT * FROM " . $this->dsc->table('comment') . " WHERE comment_id = '$comment_id'";
            $comment_info = $this->db->getRow($sql);
            $comment_info['content'] = str_replace('\r\n', '<br />', htmlspecialchars($comment_info['content']));
            $comment_info['content'] = nl2br(str_replace('\n', '<br />', $comment_info['content']));
            $comment_info['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $comment_info['add_time']);
            //晒单图片
            $sql = "SELECT comment_img, img_thumb FROM " . $this->dsc->table('comment_img') . " WHERE comment_id = '$comment_id'";
            $img_list = $this->db->getAll($sql);
            if ($img_list) {
                foreach ($img_list as $key => $row) {
                    $img_list[$key]['comment_img'] = get_image_path($row['comment_img']);
                    $img_list[$key]['img_thumb'] = get_image_path($row['img_thumb']);
                }
            }
            $comment_info['img_list'] = $img_list;

            /* 获取管理员的用户名和Email地址 */
            $sql = "SELECT user_name, email FROM " . $this->dsc->table('admin_user') .
                " WHERE user_id = '" . session('seller_id') . "'";
            $admin_info = $this->db->getRow($sql);

            /* 获得评论回复内容 */
            $sql = "SELECT * FROM " . $this->dsc->table('comment') . " WHERE parent_id = '$comment_id'" .
                " AND single_id = 0 AND dis_id = 0 AND user_id = '" . session('seller_id') . "' AND user_name = '" . $admin_info['user_name'] . "' AND ru_id = '" . $adminru['ru_id'] . "' ";
            $reply_info = $this->db->getRow($sql);

            if (empty($reply_info)) {
                $reply_info['content'] = '';
                $reply_info['add_time'] = '';
            } else {
                $reply_info['content'] = nl2br(htmlspecialchars($reply_info['content']));
                $reply_info['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $reply_info['add_time']);
            }

            /* 取得评论的对象(文章或者商品) */
            if ($comment_info['comment_type'] == 0) {
                $sql = "SELECT goods_name FROM " . $this->dsc->table('goods') .
                    " WHERE goods_id = '$comment_info[id_value]'";
                $id_value = $this->db->getOne($sql);
            } else {
                $sql = "SELECT title FROM " . $this->dsc->table('article') .
                    " WHERE article_id='$comment_info[id_value]'";
                $id_value = $this->db->getOne($sql);
            }

            /* 模板赋值 */
            $this->smarty->assign('msg', $comment_info); //评论信息
            $this->smarty->assign('admin_info', $admin_info);   //管理员信息
            $this->smarty->assign('reply_info', $reply_info);   //回复的内容
            $this->smarty->assign('id_value', $id_value);  //评论的对象
            $this->smarty->assign('send_fail', !empty($send_ok));
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['comment_info']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['05_comment_manage'],
                'href' => 'comment_manage.php?act=list', 'class' => 'icon-reply']);

            /* 页面显示 */

            return $this->smarty->display('comment_info.dwt');
        }
        /*------------------------------------------------------ */
        //-- 处理 回复用户评论
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'action') {
            /* 检查权限 */
            admin_priv('comment_priv');

            $email = isset($_REQUEST['email']) && !empty($_REQUEST['email']) ? addslashes($_REQUEST['email']) : '';
            $remail = isset($_REQUEST['remail']) && !empty($_REQUEST['remail']) ? addslashes($_REQUEST['remail']) : '';
            $user_name = isset($_REQUEST['user_name']) && !empty($_REQUEST['user_name']) ? addslashes($_REQUEST['user_name']) : '';
            $content = isset($_REQUEST['content']) && !empty($_REQUEST['content']) ? addslashes($_REQUEST['content']) : '';
            $send_email_notice = isset($_REQUEST['send_email_notice']) && !empty($_REQUEST['send_email_notice']) ? addslashes($_REQUEST['send_email_notice']) : '';
            $comment_type = isset($_REQUEST['comment_type']) && !empty($_REQUEST['comment_type']) ? intval($_REQUEST['comment_type']) : 3;//评论类型3代表管理员回复
            $id_value = isset($_REQUEST['id_value']) && !empty($_REQUEST['id_value']) ? intval($_REQUEST['id_value']) : 0;

            /* 获取管理员的用户名和Email地址 */
            $sql = "SELECT user_id, ru_id FROM " . $this->dsc->table('admin_user') .
                " WHERE user_id = '" . session('seller_id') . "'";
            $admin_info = $this->db->getRow($sql);

            /* 获取IP地址 */
            $ip = $this->dscRepository->dscIp();

            $comment_id = isset($_REQUEST['comment_id']) && !empty($_REQUEST['comment_id']) ? intval($_REQUEST['comment_id']) : 0;
            $comment_info = $this->db->getRow("SELECT comment_id,ru_id FROM " . $this->dsc->table('comment') . " WHERE comment_id = '$comment_id' AND ru_id='" . $adminru['ru_id'] . "'");

            /* 获得评论是否有回复 */
            $sql = "SELECT comment_id,content,parent_id,ru_id FROM " . $this->dsc->table('comment') .
                " WHERE parent_id = '$comment_info[comment_id]' AND single_id = 0 AND dis_id = 0 AND user_id = '" . $admin_info['user_id'] . "' AND ru_id ='" . $comment_info['ru_id'] . "'";
            $reply_info = $this->db->getRow($sql);

            if (!empty($reply_info['content']) && $adminru['ru_id'] == $comment_info['ru_id']) {
                /* 更新回复的内容 */
                $sql = "UPDATE " . $this->dsc->table('comment') . " SET " .
                    "email     = '$email', " .
                    "user_name = '$user_name', " .
                    "content   = '$content', " .
                    "add_time  =  '" . gmtime() . "', " .
                    "ip_address= '$ip', " .
                    "status    = 0" .
                    " WHERE comment_id = '" . $reply_info['comment_id'] . "'";
            } elseif ($adminru['ru_id'] == $comment_info['ru_id']) {
                /* 插入回复的评论内容 评论类型3为管理员评论 by kong*/
                $sql = "INSERT INTO " . $this->dsc->table('comment') . " (comment_type, id_value, email, user_name , " .
                    "content, add_time, ip_address, status, parent_id, user_id, ru_id) " .
                    "VALUES('3', '$id_value','$email', " .
                    "'" . session('seller_name') . "','$content','" . gmtime() . "', "
                    . "'$ip', '0', '$comment_id', '$admin_info[user_id]', '$adminru[ru_id]')";
            } else {
                return sys_msg($GLOBALS['_LANG']['priv_error']);
            }
            $this->db->query($sql);

            /* 更新当前的评论状态为已回复并且可以显示此条评论 */
            $sql = "UPDATE " . $this->dsc->table('comment') . " SET status = 1 WHERE comment_id = '$comment_id'";
            $this->db->query($sql);

            $send_ok = 1;
            /* 邮件通知处理流程 */
            if (!empty($send_email_notice) || (isset($remail) && !empty($remail))) {
                //获取邮件中的必要内容
                $sql = 'SELECT user_name, email, content ' .
                    'FROM ' . $this->dsc->table('comment') .
                    " WHERE comment_id ='$comment_id' LIMIT 1";
                $comment_info = $this->db->getRow($sql);

                /* 设置留言回复模板所需要的内容信息 */
                $template = get_mail_template('recomment');

                $this->smarty->assign('user_name', $comment_info['user_name']);
                $this->smarty->assign('recomment', $content);
                $this->smarty->assign('comment', $comment_info['content']);
                $this->smarty->assign('shop_name', "<a href='" . $this->dsc->seller_url() . "'>" . $GLOBALS['_CFG']['shop_name'] . '</a>');
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
        elseif ($_REQUEST['act'] == 'check') {
            /* 检查权限 */
            admin_priv('comment_priv');

            $comment_id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            if ($_REQUEST['check'] == 'allow') {
                /* 允许评论显示 */
                $sql = "UPDATE " . $this->dsc->table('comment') . " SET status = 1 WHERE comment_id = '$comment_id'";
                $this->db->query($sql);

                $sql = 'SELECT id_value FROM ' . $this->dsc->table('comment') . " WHERE comment_id = '$comment_id'";
                $goods_id = $this->db->getOne($sql);

                $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('comment') . " WHERE id_value = '$goods_id' AND comment_type = 0 AND status = 1 AND parent_id = 0 ";
                $count = $this->db->getOne($sql);


                $sql = "UPDATE " . $this->dsc->table('goods') . " SET comments_number = '$count' WHERE goods_id = '$goods_id'";
                $this->db->query($sql);

                /* 清除缓存 */
                clear_cache_files();

                return dsc_header("Location: comment_manage.php?act=reply&id=$comment_id\n");
            } else {
                /* 禁止评论显示 */
                $sql = "UPDATE " . $this->dsc->table('comment') . " SET status = 0 WHERE comment_id = '$comment_id'";
                $this->db->query($sql);

                $sql = 'SELECT id_value FROM ' . $this->dsc->table('comment') . " WHERE comment_id = '$comment_id'";
                $goods_id = $this->db->getOne($sql);

                $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('comment') . " WHERE id_value = '$goods_id' AND comment_type = 0 AND status = 1 AND parent_id = 0 ";
                $count = $this->db->getOne($sql);


                $sql = "UPDATE " . $this->dsc->table('goods') . " SET comments_number = '$count' WHERE goods_id = '$goods_id'";

                $this->db->query($sql);

                /* 清除缓存 */
                clear_cache_files();

                return dsc_header("Location: comment_manage.php?act=reply&id=$comment_id\n");
            }
        }

        /*------------------------------------------------------ */
        //-- 删除某一条晒单 @author guan
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'single_remove') {
            $check_auth = check_authz_json('single_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $single_id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $sql = "SELECT order_id FROM " . $this->dsc->table('single') . " WHERE single_id = '$single_id'";
            $res = $this->db->getRow($sql);
            $order_id = $res['order_id'];
            $this->db->query("UPDATE " . $this->dsc->table('order_info') . " SET is_single='4'" . " WHERE order_id = '$order_id'");
            $sql = "DELETE FROM " . $this->dsc->table('single') . " WHERE single_id = '$single_id'";
            $res = $this->db->query($sql);
            if ($res) {
                $this->db->query("DELETE FROM " . $this->dsc->table('goods_gallery') . " WHERE single_id = '$single_id'");
            }

            admin_log('', 'single_remove', 'ads');

            $url = 'comment_manage.php?act=single_query&' . str_replace('act=single_remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 删除某一条评论
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('comment_priv');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $comment_id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            /* 删除该品牌的图标 */
            $sql = "SELECT comment_img, img_thumb FROM " . $this->dsc->table('comment_img') . " WHERE comment_id = '$comment_id'";
            $img = $this->db->getAll($sql);

            if ($img) {
                for ($i = 0; $i < count($img); $i++) {
                    @unlink(storage_public($img[$i]['comment_img']));
                    @unlink(storage_public($img[$i]['img_thumb']));
                    $this->dscRepository->getOssDelFile([$img[$i]['comment_img'], $img[$i]['img_thumb']]);
                }
            }

            $sql = "DELETE FROM " . $this->dsc->table('comment_img') . " WHERE comment_id = '$comment_id'";
            $res = $this->db->query($sql);

            $sql = "DELETE FROM " . $this->dsc->table('comment') . " WHERE comment_id = '$comment_id'";
            $res = $this->db->query($sql);
            if ($res) {
                $this->db->query("DELETE FROM " . $this->dsc->table('comment') . " WHERE parent_id = '$comment_id'");
            }

            admin_log('', 'remove', 'ads');

            $url = 'comment_manage.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 批量删除用户评论
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch') {
            /* 检查权限 */
            admin_priv('comment_priv');

            $action = isset($_POST['sel_action']) ? trim($_POST['sel_action']) : 'deny';

            if (isset($_POST['checkboxes'])) {
                switch ($action) {
                    case 'remove':

                        $sql = "SELECT comment_img, img_thumb FROM " . $this->dsc->table('comment_img') . " WHERE " . db_create_in($_POST['checkboxes'], 'comment_id');
                        $img = $this->db->getAll($sql);

                        if ($img) {
                            for ($i = 0; $i < count($img); $i++) {
                                @unlink(storage_public($img[$i]['comment_img']));
                                @unlink(storage_public($img[$i]['img_thumb']));
                                $this->dscRepository->getOssDelFile([$img[$i]['comment_img'], $img[$i]['img_thumb']]);
                            }
                        }

                        $this->db->query("DELETE FROM " . $this->dsc->table('comment_img') . " WHERE " . db_create_in($_POST['checkboxes'], 'comment_id'));
                        $this->db->query("DELETE FROM " . $this->dsc->table('comment') . " WHERE " . db_create_in($_POST['checkboxes'], 'comment_id'));
                        $this->db->query("DELETE FROM " . $this->dsc->table('comment') . " WHERE " . db_create_in($_POST['checkboxes'], 'parent_id'));
                        break;

                    case 'allow':
                        $this->db->query("UPDATE " . $this->dsc->table('comment') . " SET status = 1  WHERE " . db_create_in($_POST['checkboxes'], 'comment_id'));
                        break;

                    case 'deny':
                        $this->db->query("UPDATE " . $this->dsc->table('comment') . " SET status = 0  WHERE " . db_create_in($_POST['checkboxes'], 'comment_id'));
                        break;

                    default:
                        break;
                }

                clear_cache_files();
                $action = ($action == 'remove') ? 'remove' : 'edit';
                admin_log('', $action, 'adminlog');

                $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'comment_manage.php?act=list'];
                return sys_msg(sprintf($GLOBALS['_LANG']['batch_drop_success'], count($_POST['checkboxes'])), 0, $link);
            } else {
                /* 提示信息 */
                $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'comment_manage.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['no_select_comment'], 0, $link);
            }
        }
    }

    /**
     * 获取评论列表
     * @access  public
     * @return  array
     */
    private function get_comment_list($ru_id)
    {
        /* 查询条件 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? 0 : addslashes(trim($_REQUEST['keywords']));
        $filter['reply'] = empty($_REQUEST['reply']) ? 0 : intval($_REQUEST['reply']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'add_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        //ecmoban模板堂 --zhuo start
        $sql = "select user_id from " . $this->dsc->table('merchants_shop_information') . " where shoprz_brandName LIKE '%" . mysql_like_quote($filter['keywords']) . "%' OR shopNameSuffix LIKE '%" . mysql_like_quote($filter['keywords']) . "%'";
        $user_id = $this->db->getOne($sql);

        if (empty($user_id)) {
            $user_id = 0;
        }

        $where_user = '';
        if ($user_id > 0) {
            $where_user = " OR c1.ru_id in(" . $user_id . ")";
        }

        $where = "1";
        $where .= (!empty($filter['keywords'])) ? " AND (c1.content LIKE '%" . mysql_like_quote($filter['keywords']) . "%' " . $where_user . ") " : '';
        //ecmoban模板堂 --zhuo end

        //ecmoban模板堂 --zhuo start
        if ($ru_id > 0) {
            $where .= " and c1.ru_id = '$ru_id' ";
        }
        //ecmoban模板堂 --zhuo end
        if ($filter['reply']) {
            $where .= " AND c1.order_id > 0 AND c1.comment_type = 0 AND (SELECT count(*) FROM " . $this->dsc->table('comment') . " AS c2 WHERE c2.parent_id = c1.comment_id LIMIT 1) < 1";
        }
        $where .= " AND (c1.parent_id = 0 OR (c1.parent_id > 0 AND c1.user_id > 0))";

        $sql = "SELECT count(*) FROM " . $this->dsc->table('comment') . " AS c1 WHERE $where";
        $filter['record_count'] = $this->db->getOne($sql);

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 获取评论数据 */
        $arr = [];
        $sql = "SELECT * FROM " . $this->dsc->table('comment') . " AS c1 WHERE $where " .
            " ORDER BY $filter[sort_by] $filter[sort_order] " .
            " LIMIT " . $filter['start'] . ", $filter[page_size]";

        $res = $this->db->query($sql);

        foreach ($res as $row) {
            if ($row['comment_type'] == 2) {
                $sql = "SELECT goods_name FROM " . $this->dsc->table('goods') . " WHERE goods_id='$row[id_value]'";
                $goods_name = $this->db->getOne($sql);

                $row['title'] = $goods_name . "<br/><font style='color:#1b9ad5;'>(" . $GLOBALS['_LANG']['goods_user_reply'] . ")</font>";
            } elseif ($row['comment_type'] == 3) {
                $sql = "SELECT goods_name FROM " . $this->dsc->table('goods') . " WHERE goods_id='$row[id_value]'";
                $row['title'] = $this->db->getOne($sql);
            } else {
                $sql = ($row['comment_type'] == 0) ?
                    "SELECT goods_name FROM " . $this->dsc->table('goods') . " WHERE goods_id='$row[id_value]'" :
                    "SELECT title FROM " . $this->dsc->table('article') . " WHERE article_id='$row[id_value]'";
                $row['title'] = $this->db->getOne($sql);
            }

            $row['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
            $row['ru_name'] = $this->merchantCommonService->getShopName($row['ru_id'], 1); //ecmoban模板堂 --zhuo

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                $row['email'] = $this->dscRepository->stringToStar($row['email']);
                $row['user_name'] = $this->dscRepository->stringToStar($row['user_name']);
            }

            $arr[] = $row;
        }

        $filter['keywords'] = stripslashes($filter['keywords']);
        $arr = ['item' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }


    /**
     * 获取晒单列表
     * @access  public
     * @return  array
     *
     * @author by guan 晒单评价 start
     */
    private function get_single_list($ru_id)
    {
        /* 查询条件 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? 0 : addslashes(trim($_REQUEST['keywords']));
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 's.addtime' : addslashes(trim($_REQUEST['sort_by']));
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : addslashes(trim($_REQUEST['sort_order']));

        $where = (!empty($filter['keywords'])) ? " AND s.order_sn LIKE '%" . mysql_like_quote($filter['keywords']) . "%' " : '';

        if ($ru_id > 0) {
            $where .= " AND g.user_id = '$ru_id'";
        }

        $sql = "SELECT s.* FROM " . $this->dsc->table('single') . " as s, " . $this->dsc->table('goods') . " as g " . " WHERE s.goods_id = g.goods_id AND 1=1 $where ";

        $filter['record_count'] = $this->db->getOne($sql);

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 获取晒单列表 */
        $arr = [];
        $sql = "SELECT s.*, g.user_id as ru_id FROM " . $this->dsc->table('single') . " as s, " . $this->dsc->table('goods') . " as g " . " WHERE s.goods_id = g.goods_id AND 1=1 $where " .
            " ORDER BY $filter[sort_by] $filter[sort_order] " .
            " LIMIT " . $filter['start'] . ", $filter[page_size]";
        $res = $this->db->query($sql);


        foreach ($res as $row) {
            $sql = "SELECT goods_name FROM " . $this->dsc->table('goods') . " WHERE goods_id='$row[goods_id]'";
            $row['goods_name'] = $this->db->getOne($sql);


            $row['addtime'] = local_date($GLOBALS['_CFG']['time_format'], $row['addtime']);
            $row['order_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['order_time']);
            $row['shop_name'] = $this->merchantCommonService->getShopName($row['ru_id'], 1);

            $arr[] = $row;
        }
        $filter['keywords'] = stripslashes($filter['keywords']);
        $arr = ['item' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }
}
