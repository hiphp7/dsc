<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Image;
use App\Models\Card;
use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;

/**
 * 贺卡管理程序
 */
class CardController extends InitController
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
        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

        $exc = new Exchange($this->dsc->table("card"), $this->db, 'card_id', 'card_name');

        $act = addslashes(trim(request()->input('act', '')));

        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);

        /*------------------------------------------------------ */
        //-- 包装列表
        /*------------------------------------------------------ */
        if ($act == 'list') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['07_card_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['card_add'], 'href' => 'card.php?act=add']);
            $this->smarty->assign('full_page', 1);

            $cards_list = $this->cards_list($adminru['ru_id']);

            $this->smarty->assign('card_list', $cards_list['card_list']);
            $this->smarty->assign('filter', $cards_list['filter']);
            $this->smarty->assign('record_count', $cards_list['record_count']);
            $this->smarty->assign('page_count', $cards_list['page_count']);

            return $this->smarty->display('card_list.htm');
        }

        /*------------------------------------------------------ */
        //-- ajax列表
        /*------------------------------------------------------ */
        elseif ($act == 'query') {
            $cards_list = $this->cards_list($adminru['ru_id']);

            $this->smarty->assign('card_list', $cards_list['card_list']);
            $this->smarty->assign('filter', $cards_list['filter']);
            $this->smarty->assign('record_count', $cards_list['record_count']);
            $this->smarty->assign('page_count', $cards_list['page_count']);

            $sort_flag = sort_flag($cards_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('card_list.htm'), '', ['filter' => $cards_list['filter'], 'page_count' => $cards_list['page_count']]);
        }
        /*------------------------------------------------------ */
        //-- 删除贺卡
        /*------------------------------------------------------ */
        elseif ($act == 'remove') {
            /* 检查权限 */
            $check_auth = check_authz_json('card_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $card_id = (int)request()->input('id', 0);

            $name = $exc->get_name($card_id);
            $this->dscRepository->getDelBatch('', $card_id, ['card_img'], 'card_id', Card::whereRaw(1), 0, DATA_DIR . '/cardimg/'); //删除图片

            if ($exc->drop($card_id)) {
                admin_log(addslashes($name), 'remove', 'card');

                $url = 'card.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

                return dsc_header("Location: $url\n");
            } else {
                return make_json_error($this->db->error());
            }
        }
        /*------------------------------------------------------ */
        //-- 添加新包        装
        /*------------------------------------------------------ */
        elseif ($act == 'add') {
            /* 权限判断 */
            admin_priv('card_manage');

            /*初始化显示*/
            $card['card_fee'] = 0;
            $card['free_money'] = 0;

            $this->smarty->assign('card', $card);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['card_add']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['07_card_list'], 'href' => 'card.php?act=list']);
            $this->smarty->assign('form_action', 'insert');


            return $this->smarty->display('card_info.htm');
        } elseif ($act == 'insert') {
            /* 权限判断 */
            admin_priv('card_manage');

            $card_name = request()->input('card_name');
            $card_fee = request()->input('card_fee');
            $free_money = request()->input('free_money');
            $card_desc = request()->input('card_desc');

            /*检查包装名是否重复*/
            $is_only = $exc->is_only('card_name', $card_name);

            if (!$is_only) {
                return sys_msg(sprintf($GLOBALS['_LANG']['cardname_exist'], stripslashes($card_name)), 1);
            }

            /*处理图片*/
            $img_name = basename($image->upload_image($_FILES['card_img'], "cardimg"));

            $this->dscRepository->getOssAddFile([DATA_DIR . '/cardimg/' . $img_name]);

            /*插入数据*/
            $sql = "INSERT INTO " . $this->dsc->table('card') . "(card_name, card_fee, free_money, card_desc, card_img, user_id)
            VALUES ('$card_name', '$card_fee', '$free_money', '$card_desc', '$img_name', '$adminru[ru_id]')";
            $this->db->query($sql);

            admin_log($card_name, 'add', 'card');

            /*添加链接*/
            $link[0]['text'] = $GLOBALS['_LANG']['continue_add'];
            $link[0]['href'] = 'card.php?act=add';

            $link[1]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[1]['href'] = 'card.php?act=list';

            return sys_msg($card_name . $GLOBALS['_LANG']['cardadd_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑包        装
        /*------------------------------------------------------ */
        elseif ($act == 'edit') {
            /* 权限判断 */
            admin_priv('card_manage');
            $id = request()->input('id');

            $sql = "SELECT card_id, card_name, card_fee, free_money, card_desc, card_img FROM " . $this->dsc->table('card') . " WHERE card_id='$id'";
            $card = $this->db->GetRow($sql);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['card_edit']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['07_card_list'], 'href' => 'card.php?act=list&' . list_link_postfix()]);
            $this->smarty->assign('card', $card);
            $this->smarty->assign('form_action', 'update');


            return $this->smarty->display('card_info.htm');
        } elseif ($act == 'update') {
            /* 权限判断 */
            admin_priv('card_manage');

            $card_name = request()->input('card_name');
            $old_cardname = request()->input('old_cardname');
            $id = request()->input('id');

            $old_cardimg = request()->input('old_cardimg');
            $card_fee = request()->input('card_fee');
            $free_money = request()->input('free_money');
            $card_desc = request()->input('card_desc');

            if ($card_name != $old_cardname) {
                /*检查品牌名是否相同*/
                $is_only = $exc->is_only('card_name', $card_name, $id);

                if (!$is_only) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['cardname_exist'], stripslashes($card_name)), 1);
                }
            }
            $param = "card_name = '$card_name', card_fee = '$card_fee', free_money= $free_money, card_desc = '$card_desc'";
            /* 处理图片 */
            $img_name = basename($image->upload_image($_FILES['card_img'], "cardimg", $old_cardimg));

            $this->dscRepository->getOssAddFile([DATA_DIR . '/cardimg/' . $img_name]);

            if ($img_name) {
                $param .= "  ,card_img ='$img_name' ";
            }

            if ($exc->edit($param, $id)) {
                admin_log($card_name, 'edit', 'card');

                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'card.php?act=list&' . list_link_postfix();

                $note = sprintf($GLOBALS['_LANG']['cardedit_succeed'], $card_name);
                return sys_msg($note, 0, $link);
            } else {
                return $this->db->error();
            }
        } /* 删除卡片图片 */
        elseif ($act == 'drop_card_img') {
            /* 权限判断 */
            admin_priv('card_manage');
            $card_id = (int)request()->input('id', 0);

            $this->dscRepository->getDelBatch('', $card_id, ['card_img'], 'card_id', Card::whereRaw(1), 0, DATA_DIR . '/cardimg/'); //删除图片
            $sql = "UPDATE " . $this->dsc->table('card') . " SET card_img = '' WHERE card_id = '$card_id'";
            $this->db->query($sql);

            $link = [['text' => $GLOBALS['_LANG']['card_edit_lnk'], 'href' => 'card.php?act=edit&id=' . $card_id], ['text' => $GLOBALS['_LANG']['card_list_lnk'], 'href' => 'brand.php?act=list']];
            return sys_msg($GLOBALS['_LANG']['drop_card_img_success'], 0, $link);
        }
        /*------------------------------------------------------ */
        //-- ajax编辑卡片名字
        /*------------------------------------------------------ */
        elseif ($act == 'edit_card_name') {
            $check_auth = check_authz_json('card_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $card_id = (int)request()->input('id', 0);
            $card_name = json_str_iconv(trim(request()->input('val', '')));

            if (!$exc->is_only('card_name', $card_name, $card_id)) {
                return make_json_error(sprintf($GLOBALS['_LANG']['cardname_exist'], $card_name));
            }
            $old_card_name = $exc->get_name($card_id);
            if ($exc->edit("card_name='$card_name'", $card_id)) {
                admin_log(addslashes($old_card_name), 'edit', 'card');
                return make_json_result(stripcslashes($card_name));
            } else {
                return make_json_error($this->db->error());
            }
        }
        /*------------------------------------------------------ */
        //-- ajax编辑卡片费用
        /*------------------------------------------------------ */
        elseif ($act == 'edit_card_fee') {
            $check_auth = check_authz_json('card_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $card_id = (int)request()->input('id', 0);
            $card_fee = floatval(request()->input('val', 0.00));

            $card_name = $exc->get_name($card_id);
            if ($exc->edit("card_fee ='$card_fee'", $card_id)) {
                admin_log(addslashes($card_name), 'edit', 'card');
                return make_json_result($card_fee);
            } else {
                return make_json_error($this->db->error());
            }
        }
        /*------------------------------------------------------ */
        //-- ajax编辑�        �费额度
        /*------------------------------------------------------ */
        elseif ($act == 'edit_free_money') {
            $check_auth = check_authz_json('card_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $card_id = (int)request()->input('id', 0);
            $free_money = floatval(request()->input('val', 0.00));

            $card_name = $exc->get_name($card_id);
            if ($exc->edit("free_money ='$free_money'", $card_id)) {
                admin_log(addslashes($card_name), 'edit', 'card');
                return make_json_result($free_money);
            } else {
                return make_json_error($this->db->error());
            }
        }
    }

    private function cards_list($ru_id)
    {
        //ecmoban模板堂 --zhuo start
        $where = " WHERE 1";
        if ($ru_id > 0) {
            $where .= " AND user_id = '$ru_id' ";
        }
        //ecmoban模板堂 --zhuo end

        $result = get_filter();
        if ($result === false) {
            $filter['sort_by'] = addslashes(trim(request()->input('sort_by', 'card_id')));
            $filter['sort_order'] = addslashes(trim(request()->input('sort_order', 'DESC')));

            /* 分页大小 */
            $sql = "SELECT count(*) FROM " . $this->dsc->table('card') . $where;
            $filter['record_count'] = $this->db->getOne($sql);

            $filter = page_and_size($filter);

            /* 查询 */
            $sql = "SELECT card_id, card_name, card_img, card_fee, free_money, card_desc, user_id" .
                " FROM " . $this->dsc->table('card') . $where .
                " ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
                " LIMIT " . $filter['start'] . ',' . $filter['page_size'];

            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $card_list = $this->db->getAll($sql);

        $arr = [];
        foreach ($card_list as $key => $row) {
            $arr[$key] = $row;
            $arr[$key]['ru_name'] = $this->merchantCommonService->getShopName($row['user_id'], 1); //ecmoban模板堂 --zhuo
        }

        $arr = ['card_list' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }
}
