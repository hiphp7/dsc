<?php

namespace App\Modules\Admin\Controllers;

/**
 * 虚拟卡商品管理程序
 */
class VirtualCardController extends InitController
{
    public function index()
    {
        load_helper('code');

        /*------------------------------------------------------ */
        //-- 补货处理
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'replenish') {


            /* 检查权限 */
            admin_priv('virualcard');
            /* 验证goods_id是否合法 */
            if (empty($_REQUEST['goods_id'])) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'virtual_card.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['replenish_no_goods_id'], 1, $link);
            } else {
                $goods_name = $this->db->GetOne("SELECT goods_name From " . $this->dsc->table('goods') . " WHERE goods_id='" . $_REQUEST['goods_id'] . "' AND is_real = 0 AND extension_code='virtual_card' ");
                if (empty($goods_name)) {
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'virtual_card.php?act=list'];
                    return sys_msg($GLOBALS['_LANG']['replenish_no_get_goods_name'], 1, $link);
                }
            }

            /*输出日期 by wu start*/
            $year = local_date('Y');
            $month = local_date('m');
            $day = local_date('d');
            $data_year = [];
            for ($i = 0; $i < 10; $i++) {
                $data_year[] = $year + $i;
            }
            for ($i = 1; $i <= 12; $i++) {
                $data_month[] = sprintf('%02d', $i);
            }
            for ($i = 1; $i <= 31; $i++) {
                $data_day[] = sprintf('%02d', $i);
            }
            $data_time = ['year' => $year + 1, 'month' => $month, 'day' => $day];
            $this->smarty->assign('data_year', $data_year);
            $this->smarty->assign('data_month', $data_month);
            $this->smarty->assign('data_day', $data_day);
            $this->smarty->assign('data_time', $data_time);
            /*输出日期 by wu end*/

            $card = ['goods_id' => $_REQUEST['goods_id'], 'goods_name' => $goods_name, 'end_date' => date('Y-m-d', strtotime('+1 year'))];
            $this->smarty->assign('card', $card);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['replenish']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['go_list'], 'href' => 'virtual_card.php?act=card&goods_id=' . $card['goods_id']]);
            return $this->smarty->display('replenish_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 编辑补货信息
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_replenish') {
            /* 检查权限 */
            admin_priv('virualcard');
            /* 获取卡片信息 */
            $sql = "SELECT T1.card_id, T1.goods_id, T2.goods_name,T1.card_sn, T1.card_password, T1.end_date, T1.crc32 FROM " .
                $this->dsc->table('virtual_card') . " AS T1, " . $this->dsc->table('goods') . " AS T2 " .
                "WHERE T1.goods_id = T2.goods_id AND T1.card_id = '$_REQUEST[card_id]'";
            $card = $this->db->GetRow($sql);
            if ($card['crc32'] == 0 || $card['crc32'] == crc32(AUTH_KEY)) {
                $card['card_sn'] = dsc_decrypt($card['card_sn']);
                $card['card_password'] = dsc_decrypt($card['card_password']);
            } elseif ($card['crc32'] == crc32(OLD_AUTH_KEY)) {
                $card['card_sn'] = dsc_decrypt($card['card_sn'], OLD_AUTH_KEY);
                $card['card_password'] = dsc_decrypt($card['card_password'], OLD_AUTH_KEY);
            } else {
                $card['card_sn'] = '***';
                $card['card_password'] = '***';
            }

            /*输出日期 by wu start*/
            $year = local_date('Y');
            $month = local_date('m');
            $day = local_date('d');
            $data_year = [];
            for ($i = 0; $i < 10; $i++) {
                $data_year[] = $year + $i;
            }
            for ($i = 1; $i <= 12; $i++) {
                $data_month[] = sprintf('%02d', $i);
            }
            for ($i = 1; $i <= 31; $i++) {
                $data_day[] = sprintf('%02d', $i);
            }
            $data_time = ['year' => local_date('Y', $card['end_date']), 'month' => local_date('m', $card['end_date']), 'day' => local_date('d', $card['end_date'])];
            $this->smarty->assign('data_year', $data_year);
            $this->smarty->assign('data_month', $data_month);
            $this->smarty->assign('data_day', $data_day);
            $this->smarty->assign('data_time', $data_time);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['replenish']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['go_list'], 'href' => 'virtual_card.php?act=card&goods_id=' . $card['goods_id']]);
            $this->smarty->assign('card', $card);
            return $this->smarty->display('replenish_info.dwt');
        } elseif ($_REQUEST['act'] == 'action') {
            /* 检查权限 */
            admin_priv('virualcard');

            $_POST['card_sn'] = trim($_POST['card_sn']);

            /* 加密后的 */
            $coded_card_sn = dsc_encrypt($_POST['card_sn']);
            $coded_card_password = dsc_encrypt($_POST['card_password']);

            /* 在前后两次card_sn不一致时，检查是否有重复记录,一致时直接更新数据 */
            if ($_POST['card_sn'] != $_POST['old_card_sn']) {
                $sql = "SELECT count(*) FROM " . $this->dsc->table('virtual_card') . " WHERE goods_id='" . intval($_POST['goods_id']) . "' AND card_sn='$coded_card_sn'";

                if ($this->db->GetOne($sql) > 0) {
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'virtual_card.php?act=replenish&goods_id=' . $_POST['goods_id']];
                    return sys_msg(sprintf($GLOBALS['_LANG']['card_sn_exist'], $_POST['card_sn']), 1, $link);
                }
            }

            /* 如果old_card_sn不存在则新加一条记录 */
            if (empty($_POST['old_card_sn'])) {
                /* 插入一条新记录 */
                $end_date = strtotime($_POST['end_dateYear'] . "-" . $_POST['end_dateMonth'] . "-" . $_POST['end_dateDay']);
                $add_date = gmtime();
                $sql = "INSERT INTO " . $this->dsc->table('virtual_card') . " (goods_id, card_sn, card_password, end_date, add_date, crc32) " .
                    "VALUES ('$_POST[goods_id]', '$coded_card_sn', '$coded_card_password', '$end_date', '$add_date', '" . crc32(AUTH_KEY) . "')";
                $this->db->query($sql);

                /* 如果添加成功且原卡号为空时商品库存加1 */
                if (empty($_POST['old_card_sn'])) {
                    $sql = "UPDATE " . $this->dsc->table('goods') . " SET goods_number= goods_number+1 WHERE goods_id='$_POST[goods_id]'";
                    $this->db->query($sql);
                }

                $link[] = ['text' => $GLOBALS['_LANG']['go_list'], 'href' => 'virtual_card.php?act=card&goods_id=' . $_POST['goods_id']];
                $link[] = ['text' => $GLOBALS['_LANG']['continue_add'], 'href' => 'virtual_card.php?act=replenish&goods_id=' . $_POST['goods_id']];
                return sys_msg($GLOBALS['_LANG']['action_success'], 0, $link);
            } else {
                /* 更新数据 */
                $end_date = strtotime($_POST['end_dateYear'] . "-" . $_POST['end_dateMonth'] . "-" . $_POST['end_dateDay']);
                $sql = "UPDATE " . $this->dsc->table('virtual_card') . " SET card_sn='$coded_card_sn', card_password='$coded_card_password', end_date='$end_date' " .
                    "WHERE card_id='$_POST[card_id]'";
                $this->db->query($sql);

                $link[] = ['text' => $GLOBALS['_LANG']['go_list'], 'href' => 'virtual_card.php?act=card&goods_id=' . $_POST['goods_id']];
                $link[] = ['text' => $GLOBALS['_LANG']['continue_add'], 'href' => 'virtual_card.php?act=replenish&goods_id=' . $_POST['goods_id']];
                return sys_msg($GLOBALS['_LANG']['action_success'], 0, $link);
            }
        }
        /*------------------------------------------------------ */
        //-- 补货列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'card') {
            /* 检查权限 */
            admin_priv('virualcard');

            /* 验证goods_id是否合法 */
            if (empty($_REQUEST['goods_id'])) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'virtual_card.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['replenish_no_goods_id'], 1, $link);
            } else {
                $goods_name = $this->db->GetOne("SELECT goods_name From " . $this->dsc->table('goods') . " WHERE goods_id='" . $_REQUEST['goods_id'] . "' AND is_real = 0 AND extension_code='virtual_card' ");
                if (empty($goods_name)) {
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'virtual_card.php?act=list'];
                    return sys_msg($GLOBALS['_LANG']['replenish_no_get_goods_name'], 1, $link);
                }
            }

            if (empty($_REQUEST['order_sn'])) {
                $_REQUEST['order_sn'] = '';
            }

            $this->smarty->assign('goods_id', $_REQUEST['goods_id']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('ur_here', $goods_name);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['replenish'],
                'href' => 'virtual_card.php?act=replenish&goods_id=' . $_REQUEST['goods_id']]);
            $this->smarty->assign('goods_id', $_REQUEST['goods_id']);

            $list = $this->get_replenish_list();

            $this->smarty->assign('card_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('replenish_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 虚拟卡列表，用于排序、翻页
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'query_card') {
            $list = $this->get_replenish_list();

            $this->smarty->assign('card_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('replenish_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        } /* 批量删除card */
        elseif ($_REQUEST['act'] == 'batch_drop_card') {
            /* 检查权限 */
            admin_priv('virualcard');

            $num = count($_POST['checkboxes']);
            $sql = "DELETE FROM " . $this->dsc->table('virtual_card') . " WHERE card_id " . db_create_in(implode(',', $_POST['checkboxes']));
            if ($this->db->query($sql)) {
                /* 商品数量减$num */
                $this->update_goods_number(intval($_REQUEST['goods_id']));
                $link[] = ['text' => $GLOBALS['_LANG']['go_list'], 'href' => 'virtual_card.php?act=card&goods_id=' . $_REQUEST['goods_id']];
                return sys_msg($GLOBALS['_LANG']['action_success'], 0, $link);
            }
        } /* 批量上传页面 */

        elseif ($_REQUEST['act'] == 'batch_card_add') {
            /* 检查权限 */
            admin_priv('virualcard');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['batch_card_add']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['virtual_card_list'], 'href' => 'goods.php?act=list&extension_code=virtual_card']);
            $this->smarty->assign('goods_id', $_REQUEST['goods_id']);
            return $this->smarty->display('batch_card_info.dwt');
        } elseif ($_REQUEST['act'] == 'batch_confirm') {
            /* 检查上传是否成功 */
            if ($_FILES['uploadfile']['tmp_name'] == '' || $_FILES['uploadfile']['tmp_name'] == 'none') {
                return sys_msg($GLOBALS['_LANG']['uploadfile_fail'], 1);
            }

            $data = file($_FILES['uploadfile']['tmp_name']);
            $rec = []; //数据数组
            $i = 0;
            $separator = trim($_POST['separator']);
            foreach ($data as $line) {
                $row = explode($separator, $line);
                switch (count($row)) {
                    case '3':
                        $rec[$i]['end_date'] = $row[2];
                    // no break
                    case '2':
                        $rec[$i]['card_password'] = $row[1];
                    // no break
                    case '1':
                        $rec[$i]['card_sn'] = $row[0];
                        break;
                    default:
                        $rec[$i]['card_sn'] = $row[0];
                        $rec[$i]['card_password'] = $row[1];
                        $rec[$i]['end_date'] = $row[2];
                        break;
                }
                $i++;
            }

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['batch_card_add']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['batch_card_add'], 'href' => 'virtual_card.php?act=batch_card_add&goods_id=' . $_REQUEST['goods_id']]);
            $this->smarty->assign('list', $rec);
            return $this->smarty->display('batch_card_confirm.dwt');
        } /* 批量上传处理 */
        elseif ($_REQUEST['act'] == 'batch_insert') {
            /* 检查权限 */
            admin_priv('virualcard');

            $add_time = gmtime();
            $i = 0;
            foreach ($_POST['checked'] as $key) {
                $rec['card_sn'] = dsc_encrypt($_POST['card_sn'][$key]);
                $rec['card_password'] = dsc_encrypt($_POST['card_password'][$key]);
                $rec['crc32'] = crc32(AUTH_KEY);
                $rec['end_date'] = empty($_POST['end_date'][$key]) ? 0 : strtotime($_POST['end_date'][$key]);
                $rec['goods_id'] = $_POST['goods_id'];
                $rec['add_date'] = $add_time;
                $this->db->AutoExecute($this->dsc->table('virtual_card'), $rec, 'INSERT');
                $i++;
            }

            /* 更新商品库存 */
            $this->update_goods_number(intval($_REQUEST['goods_id']));
            $link[] = ['text' => $GLOBALS['_LANG']['card'], 'href' => 'virtual_card.php?act=card&goods_id=' . $_POST['goods_id']];
            return sys_msg(sprintf($GLOBALS['_LANG']['batch_card_add_ok'], $i), 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 更改加密串
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'change') {
            /* 检查权限 */
            admin_priv('virualcard');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['virtual_card_change']);


            return $this->smarty->display('virtual_card_change.dwt');
        }

        /*------------------------------------------------------ */
        //-- 提交更改
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'submit_change') {
            /* 检查权限 */
            admin_priv('virualcard');

            if (isset($_POST['old_string']) && isset($_POST['new_string'])) {
                // 检查原加密串是否正确
                if ($_POST['old_string'] != OLD_AUTH_KEY) {
                    return sys_msg($GLOBALS['_LANG']['invalid_old_string'], 1);
                }

                // 检查新加密串是否正确
                if ($_POST['new_string'] != AUTH_KEY) {
                    return sys_msg($GLOBALS['_LANG']['invalid_new_string'], 1);
                }

                // 检查原加密串和新加密串是否相同
                if ($_POST['old_string'] == $_POST['new_string'] || crc32($_POST['old_string']) == crc32($_POST['new_string'])) {
                    return sys_msg($GLOBALS['_LANG']['same_string'], 1);
                }


                // 重新加密卡号和密码
                $old_crc32 = crc32($_POST['old_string']);
                $new_crc32 = crc32($_POST['new_string']);
                $sql = "SELECT card_id, card_sn, card_password FROM " . $this->dsc->table('virtual_card') . " WHERE crc32 = '$old_crc32'";
                $res = $this->db->query($sql);
                foreach ($res as $row) {
                    $row['card_sn'] = dsc_encrypt(dsc_decrypt($row['card_sn'], $_POST['old_string']), $_POST['new_string']);
                    $row['card_password'] = dsc_encrypt(dsc_decrypt($row['card_password'], $_POST['old_string']), $_POST['new_string']);
                    $row['crc32'] = $new_crc32;
                    $this->db->autoExecute($this->dsc->table('virtual_card'), $row, 'UPDATE', 'card_id = ' . $row['card_id']);
                }

                // 记录日志
                //admin_log();

                // 返回
                return sys_msg($GLOBALS['_LANG']['change_key_ok'], 0, [['href' => 'virtual_card.php?act=list', 'text' => $GLOBALS['_LANG']['virtual_card_list']]]);
            }
        }

        /*------------------------------------------------------ */
        //-- 切换是否已出售状态
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'toggle_sold') {
            $check_auth = check_authz_json('virualcard');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $sql = "UPDATE " . $this->dsc->table('virtual_card') . " SET is_saled= '$val' WHERE card_id='$id'";

            if ($this->db->query($sql, 'SILENT')) {
                /* 修改商品库存 */
                $sql = "SELECT goods_id FROM " . $this->dsc->table('virtual_card') . " WHERE card_id = '$id' LIMIT 1";
                $goods_id = $this->db->getOne($sql);

                $this->update_goods_number($goods_id);
                return make_json_result($val);
            } else {
                return make_json_error($GLOBALS['_LANG']['action_fail'] . "\n" . $this->db->error());
            }
        }

        /*------------------------------------------------------ */
        //-- 删除卡片
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove_card') {
            $check_auth = check_authz_json('virualcard');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            $row = $this->db->GetRow('SELECT card_sn, goods_id FROM ' . $this->dsc->table('virtual_card') . " WHERE card_id = '$id'");

            $sql = 'DELETE FROM ' . $this->dsc->table('virtual_card') . " WHERE card_id = '$id'";
            if ($this->db->query($sql, 'SILENT')) {
                /* 修改商品数量 */
                $this->update_goods_number($row['goods_id']);

                $url = 'virtual_card.php?act=query_card&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

                return dsc_header("Location: $url\n");
            } else {
                return make_json_error($this->db->error());
            }
        }

        /*------------------------------------------------------ */
        //-- 开始更改加密串：�        �检查原串和新串
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'start_change') {
            $old_key = json_str_iconv(trim($_GET['old_key']));
            $new_key = json_str_iconv(trim($_GET['new_key']));
            // 检查原加密串和新加密串是否相同
            if ($old_key == $new_key || crc32($old_key) == crc32($new_key)) {
                return make_json_error($GLOBALS['_LANG']['same_string']);
            }
            if ($old_key != AUTH_KEY) {
                return make_json_error($GLOBALS['_LANG']['invalid_old_string']);
            } else {
                $f = storage_public(DATA_DIR . '/config.php');
                file_put_contents($f, str_replace("'AUTH_KEY', '" . AUTH_KEY . "'", "'AUTH_KEY', '" . $new_key . "'", file_get_contents($f)));
                file_put_contents($f, str_replace("'OLD_AUTH_KEY', '" . OLD_AUTH_KEY . "'", "'OLD_AUTH_KEY', '" . $old_key . "'", file_get_contents($f)));
                @fclose($fp);
            }

            // 查询统计信息：总记录，使用原串的记录，使用新串的记录，使用未知串的记录
            $stat = ['all' => 0, 'new' => 0, 'old' => 0, 'unknown' => 0];
            $sql = "SELECT crc32, count(*) AS cnt FROM " . $this->dsc->table('virtual_card') . " GROUP BY crc32";
            $res = $this->db->query($sql);
            foreach ($res as $row) {
                $stat['all'] += $row['cnt'];
                if (crc32($new_key) == $row['crc32']) {
                    $stat['new'] += $row['cnt'];
                } elseif (crc32($old_key) == $row['crc32']) {
                    $stat['old'] += $row['cnt'];
                } else {
                    $stat['unknown'] += $row['cnt'];
                }
            }

            return make_json_result(sprintf($GLOBALS['_LANG']['old_stat'], $stat['all'], $stat['new'], $stat['old'], $stat['unknown']));
        }

        /*------------------------------------------------------ */
        //-- 更新加密串
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'on_change') {
            // 重新加密卡号和密码
            $each_num = 1;
            $old_crc32 = crc32(OLD_AUTH_KEY);
            $new_crc32 = crc32(AUTH_KEY);
            $updated = intval($_GET['updated']);

            $sql = "SELECT card_id, card_sn, card_password " .
                " FROM " . $this->dsc->table('virtual_card') .
                " WHERE crc32 = '$old_crc32' LIMIT $each_num";
            $res = $this->db->query($sql);

            foreach ($res as $row) {
                $row['card_sn'] = dsc_encrypt(dsc_decrypt($row['card_sn'], OLD_AUTH_KEY));
                $row['card_password'] = dsc_encrypt(dsc_decrypt($row['card_password'], OLD_AUTH_KEY));
                $row['crc32'] = $new_crc32;

                if (!$this->db->autoExecute($this->dsc->table('virtual_card'), $row, 'UPDATE', 'card_id = ' . $row['card_id'])) {
                    return make_json_error($updated, 0, $GLOBALS['_LANG']['update_error'] . "\n" . $this->db->error());
                }

                $updated++;
            }

            // 查询是否还有未更新的
            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('virtual_card') . " WHERE crc32 = '$old_crc32' ";
            $left_num = $this->db->getOne($sql);

            if ($left_num > 0) {
                return make_json_result($updated);
            } else {
                // 查询统计信息
                $stat = ['new' => 0, 'unknown' => 0];
                $sql = "SELECT crc32, count(*) AS cnt FROM " . $this->dsc->table('virtual_card') . " GROUP BY crc32";
                $res = $this->db->query($sql);
                foreach ($res as $row) {
                    if ($new_crc32 == $row['crc32']) {
                        $stat['new'] += $row['cnt'];
                    } else {
                        $stat['unknown'] += $row['cnt'];
                    }
                }

                return make_json_result($updated, sprintf($GLOBALS['_LANG']['new_stat'], $stat['new'], $stat['unknown']));
            }
        }
    }

    /**
     * 返回补货列表
     *
     * @return array
     */
    private function get_replenish_list()
    {
        /* 查询条件 */
        $filter['goods_id'] = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
        $filter['search_type'] = empty($_REQUEST['search_type']) ? 0 : trim($_REQUEST['search_type']);
        $filter['order_sn'] = empty($_REQUEST['order_sn']) ? 0 : trim($_REQUEST['order_sn']);
        $filter['keyword'] = empty($_REQUEST['keyword']) ? 0 : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'card_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = (!empty($filter['goods_id'])) ? " AND goods_id = '" . $filter['goods_id'] . "' " : '';
        $where .= (!empty($filter['order_sn'])) ? " AND order_sn LIKE '%" . mysql_like_quote($filter['order_sn']) . "%' " : '';

        if (!empty($filter['keyword'])) {
            if ($filter['search_type'] == 'card_sn') {
                $where .= " AND card_sn = '" . dsc_encrypt($filter['keyword']) . "'";
            } else {
                $where .= " AND order_sn LIKE '%" . mysql_like_quote($filter['keyword']) . "%' ";
            }
        }

        $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('virtual_card') . " WHERE 1 $where";
        $filter['record_count'] = $this->db->getOne($sql);

        /* 分页大小 */
        $filter = page_and_size($filter);
        $start = ($filter['page'] - 1) * $filter['page_size'];

        /* 查询 */
        $sql = "SELECT card_id, goods_id, card_sn, card_password, end_date, is_saled, order_sn, crc32 " .
            " FROM " . $this->dsc->table('virtual_card') .
            " WHERE 1 " . $where .
            " ORDER BY $filter[sort_by] $filter[sort_order] " .
            " LIMIT $start, $filter[page_size]";
        $all = $this->db->getAll($sql);

        $arr = [];
        foreach ($all as $key => $row) {
            if ($row['crc32'] == 0 || $row['crc32'] == crc32(AUTH_KEY)) {
                $row['card_sn'] = dsc_decrypt($row['card_sn']);
                $row['card_password'] = dsc_decrypt($row['card_password']);
            } elseif ($row['crc32'] == crc32(OLD_AUTH_KEY)) {
                $row['card_sn'] = dsc_decrypt($row['card_sn'], OLD_AUTH_KEY);
                $row['card_password'] = dsc_decrypt($row['card_password'], OLD_AUTH_KEY);
            } else {
                $row['card_sn'] = '***';
                $row['card_password'] = '***';
            }

            $row['end_date'] = $row['end_date'] == 0 ? '' : date($GLOBALS['_CFG']['date_format'], $row['end_date']);

            $arr[] = $row;
        }

        return ['item' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    /**
     * 更新虚拟商品的商品数量
     *
     * @access  public
     * @param int $goods_id
     *
     * @return bool
     */
    private function update_goods_number($goods_id)
    {
        $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('virtual_card') . " WHERE goods_id = '$goods_id' AND is_saled = 0";
        $goods_number = $this->db->getOne($sql);

        $sql = "UPDATE " . $this->dsc->table('goods') . " SET goods_number = '$goods_number' WHERE goods_id = '$goods_id' AND extension_code='virtual_card'";

        return $this->db->query($sql);
    }
}
