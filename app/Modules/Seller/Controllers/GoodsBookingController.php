<?php

namespace App\Modules\Seller\Controllers;

use App\Repositories\Common\CommonRepository;

/**
 * 缺货处理管理程序
 */
class GoodsBookingController extends InitController
{
    protected $commonRepository;

    public function __construct(
        CommonRepository $commonRepository
    )
    {
        $this->commonRepository = $commonRepository;
    }

    public function index()
    {
        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('action_type', "order");
        admin_priv('booking');

        $this->smarty->assign('menu_select', ['action' => '04_order', 'current' => '06_undispose_booking']);
        /*------------------------------------------------------ */
        //-- 列出所有订购信息
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list_all') {
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['04_order']);
            $this->smarty->assign('current', '06_undispose_booking');
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['list_all']);
            $this->smarty->assign('full_page', 1);

            $list = $this->get_bookinglist();

            // 分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('booking_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('booking_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 翻页、排序
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'query') {
            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

            $list = $this->get_bookinglist();
            $this->smarty->assign('current', '06_undispose_booking');
            //ecmoban模板堂 --zhuo start
            $adminru = get_admin_ru_id();
            $ruCat = '';
            if ($adminru['ru_id'] == 0) {
                $this->smarty->assign('priv_ru', 1);
            } else {
                $this->smarty->assign('priv_ru', 0);
            }
            //ecmoban模板堂 --zhuo end
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('booking_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('booking_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 删除缺货登记
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('booking');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            $this->db->query("DELETE FROM " . $this->dsc->table('booking_goods') . " WHERE rec_id='$id'");

            $url = 'goods_booking.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 显示详情
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'detail') {
            $id = intval($_REQUEST['id']);
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['04_order']);
            $this->smarty->assign('send_fail', !empty($_REQUEST['send_ok']));
            $this->smarty->assign('booking', $this->get_booking_info($id));
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['detail']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['06_undispose_booking'], 'href' => 'goods_booking.php?act=list_all', 'class' => 'icon-reply']);
            return $this->smarty->display('booking_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 处理提交数据
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'update') {
            /* 权限判断 */
            admin_priv('booking');

            $rec_id = isset($_REQUEST['rec_id']) && !empty($_REQUEST['rec_id']) ? intval($_REQUEST['rec_id']) : 0;
            $dispose_note = !empty($_POST['dispose_note']) ? trim($_POST['dispose_note']) : '';

            $sql = "UPDATE  " . $this->dsc->table('booking_goods') .
                " SET is_dispose='1', dispose_note='$dispose_note', " .
                "dispose_time='" . gmtime() . "', dispose_user='" . session('seller_name') . "'" .
                " WHERE rec_id = '$rec_id'";
            $this->db->query($sql);

            $send_ok = 1;
            /* 邮件通知处理流程 */
            if (!empty($_POST['send_email_notice']) or isset($_POST['remail'])) {
                //获取邮件中的必要内容
                $sql = 'SELECT bg.email, bg.link_man, bg.goods_id, g.goods_name ' .
                    'FROM ' . $this->dsc->table('booking_goods') . ' AS bg, ' . $this->dsc->table('goods') . ' AS g ' .
                    "WHERE bg.goods_id = g.goods_id AND bg.rec_id = '$rec_id'";
                $booking_info = $this->db->getRow($sql);

                /* 设置缺货回复模板所需要的内容信息 */
                $template = get_mail_template('goods_booking');
                $goods_link = $this->dsc->seller_url() . 'goods.php?id=' . $booking_info['goods_id'];

                $this->smarty->assign('user_name', $booking_info['link_man']);
                $this->smarty->assign('goods_link', $goods_link);
                $this->smarty->assign('goods_name', $booking_info['goods_name']);
                $this->smarty->assign('dispose_note', $dispose_note);
                $this->smarty->assign('shop_name', "<a href='" . $this->dsc->seller_url() . "'>" . $GLOBALS['_CFG']['shop_name'] . '</a>');
                $this->smarty->assign('send_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));

                $content = $this->smarty->fetch('str:' . $template['template_content']);

                /* 发送邮件 */
                if ($this->commonRepository->sendEmail($booking_info['link_man'], $booking_info['email'], $template['template_subject'], $content, $template['is_html'])) {
                    $send_ok = 0;
                } else {
                    $send_ok = 1;
                }
            }

            return dsc_header("Location: ?act=detail&id=" . $rec_id . "&send_ok=$send_ok\n");
        }
    }

    /**
     * 获取订购信息
     *
     * @access  public
     *
     * @return array
     */
    private function get_bookinglist()
    {

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        $ruCat = '';
        if ($adminru['ru_id'] > 0) {
            $ruCat = " and g.user_id = '" . $adminru['ru_id'] . "'";
        }
        //ecmoban模板堂 --zhuo end

        /* 查询条件 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }
        $filter['dispose'] = empty($_REQUEST['dispose']) ? 0 : intval($_REQUEST['dispose']);
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'sort_order' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        //ecmoban模板堂 --zhuo start
        $sql = "select user_id from " . $this->dsc->table('merchants_shop_information') . " where shoprz_brandName LIKE '%" . mysql_like_quote($filter['keywords']) . "%' OR shopNameSuffix LIKE '%" . mysql_like_quote($filter['keywords']) . "%'";
        $user_id = $this->db->getOne($sql);

        if (empty($user_id)) {
            $user_id = 0;
        }

        $where_user = '';
        if ($user_id > 0) {
            $where_user = " OR (g.user_id in(" . $user_id . "))";
        }
        //ecmoban模板堂 --zhuo end

        $where = (!empty($_REQUEST['keywords'])) ? " AND (g.goods_name LIKE '%" . mysql_like_quote($filter['keywords']) . "%' " . $where_user . ")" : '';

        $where .= (!empty($_REQUEST['dispose'])) ? " AND bg.is_dispose = '$filter[dispose]' " : '';

        $where .= $ruCat;

        $sql = 'SELECT COUNT(*) FROM ' . $this->dsc->table('booking_goods') . ' AS bg, ' .
            $this->dsc->table('goods') . ' AS g ' .
            "WHERE bg.goods_id = g.goods_id $where";
        $filter['record_count'] = $this->db->getOne($sql);

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 获取活动数据 */
        $sql = 'SELECT bg.rec_id, bg.link_man, g.goods_id, g.goods_name, g.user_id, bg.goods_number, bg.booking_time, bg.is_dispose ' .
            'FROM ' . $this->dsc->table('booking_goods') . ' AS bg, ' . $this->dsc->table('goods') . ' AS g ' .
            "WHERE bg.goods_id = g.goods_id $where " .
            "ORDER BY $filter[sort_by] $filter[sort_order] " .
            "LIMIT " . $filter['start'] . ", $filter[page_size]";
        $row = $this->db->getAll($sql);

        foreach ($row as $key => $val) {
            $row[$key]['booking_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['booking_time']);

            $data = ['shoprz_brandName', 'shop_class_keyWords', 'shopNameSuffix'];
            $shop_info = get_table_date('merchants_shop_information', "user_id = '" . $val['user_id'] . "'", $data);
            $row[$key]['user_name'] = $shop_info['shoprz_brandName'] . $shop_info['shopNameSuffix'];
        }
        $filter['keywords'] = stripslashes($filter['keywords']);
        $arr = ['item' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 获得缺货登记的详细信息
     *
     * @param integer $id
     *
     * @return  array
     */
    private function get_booking_info($id)
    {
        $sql = "SELECT bg.rec_id, bg.user_id, IFNULL(u.user_name, '" . $GLOBALS['_LANG']['guest_user'] . "') AS user_name, " .
            "bg.link_man, g.goods_name, bg.goods_id, bg.goods_number, " .
            "bg.booking_time, bg.goods_desc,bg.dispose_user, bg.dispose_time, bg.email, " .
            "bg.tel, bg.dispose_note ,bg.dispose_user, bg.dispose_time,bg.is_dispose  " .
            "FROM " . $this->dsc->table('booking_goods') . " AS bg " .
            "LEFT JOIN " . $this->dsc->table('goods') . " AS g ON g.goods_id=bg.goods_id " .
            "LEFT JOIN " . $this->dsc->table('users') . " AS u ON u.user_id=bg.user_id " .
            "WHERE bg.rec_id ='$id'";

        $res = $this->db->GetRow($sql);

        /* 格式化时间 */
        $res['booking_time'] = local_date($GLOBALS['_CFG']['time_format'], $res['booking_time']);
        if (!empty($res['dispose_time'])) {
            $res['dispose_time'] = local_date($GLOBALS['_CFG']['time_format'], $res['dispose_time']);
        }

        return $res;
    }
}
