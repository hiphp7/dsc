<?php

namespace App\Modules\Admin\Controllers;

use App\Repositories\Common\DscRepository;

/**
 * DSCMALL 会员收货地址管理程序
 */
class UserAddressLogController extends InitController
{
    protected $dscRepository;

    public function __construct(
        DscRepository $dscRepository
    )
    {
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        /*------------------------------------------------------ */
        //-- 用户收货地址日志列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'list') {
            /* 检查权限 */
            admin_priv('users_manage');
            $sql = "SELECT rank_id, rank_name, min_points FROM " . $this->dsc->table('user_rank') . " ORDER BY min_points ASC ";
            $rs = $this->db->query($sql);

            $ranks = [];
            foreach ($rs as $row) {
                $ranks[$row['rank_id']] = $row['rank_name'];
            }

            $this->smarty->assign('user_ranks', $ranks);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['03_users_list']);

            $address_list = $this->user_address_list_log();

            $this->smarty->assign('address_list', $address_list['address_list']);
            $this->smarty->assign('filter', $address_list['filter']);
            $this->smarty->assign('record_count', $address_list['record_count']);
            $this->smarty->assign('page_count', $address_list['page_count']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('sort_user_id', '<img src="' . __TPL__ . '/images/sort_desc.gif">');

            return $this->smarty->display('user_address_list_log.dwt');
        }

        /*------------------------------------------------------ */
        //-- ajax返回用户列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $address_list = $this->user_address_list_log();

            $this->smarty->assign('address_list', $address_list['address_list']);
            $this->smarty->assign('filter', $address_list['filter']);
            $this->smarty->assign('record_count', $address_list['record_count']);
            $this->smarty->assign('page_count', $address_list['page_count']);

            $sort_flag = sort_flag($address_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('user_address_list_log.dwt'), '', ['filter' => $address_list['filter'], 'page_count' => $address_list['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 编辑用户帐号
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit') {
            /* 检查权限 */
            admin_priv('users_manage');

            /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
            $this->smarty->assign('country_list', get_regions());

            /* 获得用户收货人信息 */
            $consignee = $this->get_consignee_log($_REQUEST['address_id'], $_REQUEST['user_id']);

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0 && $consignee) {
                $consignee['mobile'] = $this->dscRepository->stringToStar($consignee['mobile']);
                $consignee['email'] = $this->dscRepository->stringToStar($consignee['email']);
            }

            $country_list = $this->get_regions_log(0, 0);
            $province_list = $this->get_regions_log(1, $consignee['country']);
            $city_list = $this->get_regions_log(2, $consignee['province']);
            $district_list = $this->get_regions_log(3, $consignee['city']);

            $sn = 0;
            $this->smarty->assign('country_list', $country_list);
            $this->smarty->assign('province_list', $province_list);
            $this->smarty->assign('city_list', $city_list);
            $this->smarty->assign('district_list', $district_list);
            $this->smarty->assign('sn', $sn);
            $this->smarty->assign('consignee', $consignee);
            $this->smarty->assign('address_id', $_REQUEST['address_id']);
            $this->smarty->assign('user_id', $_REQUEST['user_id']);


            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['users_edit']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['03_users_list'], 'href' => 'user_address_log.php?act=list']);
            $this->smarty->assign('form_action', 'update');

            return $this->smarty->display('user_address_log_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 更新用户帐号
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'update') {
            /* 检查权限 */
            admin_priv('users_manage');
            $time = gmtime();
            $consignee = empty($_POST['consignee']) ? '' : trim($_POST['consignee']);
            $country = isset($_POST['country']) ? $_POST['country'] : 0;
            $province = isset($_POST['province']) ? $_POST['province'] : 0;
            $city = isset($_POST['city']) ? $_POST['city'] : 0;
            $district = isset($_POST['district']) ? $_POST['district'] : 0;
            $address = empty($_POST['address']) ? '' : trim($_POST['address']);
            $tel = empty($_POST['tel']) ? '' : trim($_POST['tel']);
            $mobile = empty($_POST['mobile']) ? '' : trim($_POST['mobile']);
            $email = empty($_POST['email']) ? '' : trim($_POST['email']);
            $zipcode = empty($_POST['zipcode']) ? '' : trim($_POST['zipcode']);
            $sign_building = empty($_POST['sign_building']) ? '' : trim($_POST['sign_building']);
            $best_time = empty($_POST['best_time']) ? '' : trim($_POST['best_time']);
            $audit = isset($_POST['audit']) ? $_POST['audit'] : 0;

            $address_id = isset($_POST['address_id']) ? $_POST['address_id'] : 0;
            $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : 0;

            $other['consignee'] = $consignee;
            $other['country'] = $country;
            $other['province'] = $province;
            $other['city'] = $city;
            $other['district'] = $district;
            $other['address'] = $address;
            $other['tel'] = $tel;
            $other['mobile'] = $mobile;
            $other['email'] = $email;
            $other['zipcode'] = $zipcode;
            $other['sign_building'] = $sign_building;
            $other['best_time'] = $best_time;
            $other['audit'] = $audit;
            $other['update_time'] = $time;

            //更新到收货地址表中
            $this->db->autoExecute($this->dsc->table('user_address'), $other, 'UPDATE', "address_id = '$address_id' and user_id = '$user_id'");

            //更新收货地址日志表
            $this->db->autoExecute($this->dsc->table('user_address'), $other, 'UPDATE', "address_id = '$address_id' and user_id = '$user_id'");
            $address_log_up = $GLOBALS['_LANG']['update_success'];

            /* 提示信息 */
            $links[0]['text'] = $GLOBALS['_LANG']['goto_list'];
            $links[0]['href'] = 'user_address_log.php?act=list';
            $links[1]['text'] = $GLOBALS['_LANG']['go_back'];
            $links[1]['href'] = 'javascript:history.back()';

            return sys_msg($address_log_up, 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 批量删除会员帐号
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'batch_remove') {
            /* 检查权限 */
            admin_priv('users_drop');

            if (isset($_POST['checkboxes'])) {
                $this->get_delete_address_log($_POST['checkboxes']);

                $lnk[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'user_address_log.php?act=list'];

                $count = count($_POST['checkboxes']);
                return sys_msg(sprintf($GLOBALS['_LANG']['batch_remove_success'], $count), 0, $lnk);
            } else {
                $lnk[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'user_address_log.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['no_select_user'], 0, $lnk);
            }
        }

        /*------------------------------------------------------ */
        //-- 删除会员帐号
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'remove') {
            /* 检查权限 */
            admin_priv('users_drop');

            $address['address_id'] = $_GET['id'];

            $this->get_delete_address_log($address, 1);

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'user_address_log.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['remove_success'], 0, $link);
        }
    }

    /**
     *  返回用户收货地址列表数据
     *
     * @access  public
     * @param
     *
     * @return void
     */
    private function user_address_list_log()
    {
        $result = get_filter();
        if ($result === false) {
            /* 过滤条件 */
            $filter['consignee'] = empty($_REQUEST['consignee']) ? '' : trim($_REQUEST['consignee']);
            if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
                $filter['consignee'] = json_str_iconv($filter['consignee']);
            }
            $filter['user_name'] = empty($_REQUEST['user_name']) ? '' : trim($_REQUEST['user_name']);
            $filter['mobile'] = empty($_REQUEST['mobile']) ? '' : trim($_REQUEST['mobile']);

            $filter['sort_by'] = "a.address_id";
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $ex_where = ' WHERE 1 ';

            if ($filter['consignee']) {
                $ex_where .= " AND a.consignee = '" . $filter['consignee'] . "'";
            }
            if ($filter['user_name']) {
                $ex_where .= " AND u.user_name = '" . $filter['user_name'] . "'";
            }
            if ($filter['mobile']) {
                $ex_where .= " AND a.mobile = '" . $filter['mobile'] . "'";
            }

            $filter['record_count'] = $this->db->getOne("SELECT COUNT(*) FROM " . $this->dsc->table('user_address') . " as a " .
                "left join " . $this->dsc->table('users') . " as u on a.user_id = u.user_id " . $ex_where);

            /* 分页大小 */
            $filter = page_and_size($filter);
            $sql = "SELECT concat(IFNULL(c.region_name, ''), '  ', IFNULL(p.region_name, ''), " .
                "'  ', IFNULL(t.region_name, ''), '  ', IFNULL(d.region_name, '')) AS region, u.user_name, a.address_id, a.user_id, a.consignee, a.email, a.country, a.province, a.city, a.district, a.address, a.zipcode, a.tel, a.mobile, a.sign_building, a.best_time, a.audit, a.update_time " .
                " FROM " . $this->dsc->table('user_address') . " as a left join" .

                $this->dsc->table('users') . " as u on a.user_id = u.user_id " .

                "LEFT JOIN " . $this->dsc->table('region') . " AS c ON a.country = c.region_id " .
                "LEFT JOIN " . $this->dsc->table('region') . " AS p ON a.province = p.region_id " .
                "LEFT JOIN " . $this->dsc->table('region') . " AS t ON a.city = t.region_id " .
                "LEFT JOIN " . $this->dsc->table('region') . " AS d ON a.district = d.region_id " .

                $ex_where .
                " ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
                " LIMIT " . $filter['start'] . ',' . $filter['page_size'];

            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $address_list = $this->db->getAll($sql);

        $count = count($address_list);
        for ($i = 0; $i < $count; $i++) {

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                $address_list[$i]['user_name'] = $this->dscRepository->stringToStar($address_list[$i]['user_name']);
                $address_list[$i]['mobile'] = $this->dscRepository->stringToStar($address_list[$i]['mobile']);
                $address_list[$i]['email'] = $this->dscRepository->stringToStar($address_list[$i]['email']);
            }

            $address_list[$i]['update_time'] = local_date("Y-m-d H:i:s", $address_list[$i]['update_time']);
        }

        $arr = ['address_list' => $address_list, 'filter' => $filter,
            'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 取得收货人地址列表
     * @param int $user_id 用户编号
     * @return  array
     */
    private function get_consignee_log($address_id = 0, $user_id = 0)
    {
        $sql = "SELECT * FROM " . $this->dsc->table('user_address') .
            " WHERE user_id = '$user_id' and address_id = '$address_id'";

        return $this->db->getRow($sql);
    }

    /**
     * 获得指定国家的所有省份
     *
     * @access      public
     * @param int     country    国家的编号
     * @return      array
     */
    private function get_regions_log($type = 0, $parent = 0)
    {
        $sql = 'SELECT region_id, region_name FROM ' . $this->dsc->table('region') .
            " WHERE region_type = '$type' AND parent_id = '$parent'";

        return $this->db->GetAll($sql);
    }

    //批量删除会员
    private function get_delete_address_log($address_id = [], $open = 0)
    {
        if ($open == 1) {
            $sql = "delete from " . $this->dsc->table('user_address') . " where address_id = " . $address_id['address_id'];
            $this->db->query($sql);
        } else {
            if (count($address_id) > 0) {
                for ($i = 0; $i < count($address_id); $i++) {
                    $sql = "delete from " . $this->dsc->table('user_address') . " where address_id = " . $address_id[$i];
                    $this->db->query($sql);
                }
            }
        }

        if ($open == 1) {
            $sql = "delete from " . $this->dsc->table('user_address') . " where address_id = " . $address_id['address_id'];
            $this->db->query($sql);
        } else {
            if (count($address_id) > 0) {
                for ($i = 0; $i < count($address_id); $i++) {
                    $sql = "delete from " . $this->dsc->table('user_address') . " where address_id = " . $address_id[$i];
                    $this->db->query($sql);
                }
            }
        }
    }
}
