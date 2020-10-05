<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Image;
use App\Models\Card;
use App\Repositories\Common\DscRepository;
use App\Services\Other\CardManageService;

/**
 * 贺卡管理程序
 */
class CardController extends InitController
{
    protected $cardManageService;
    protected $dscRepository;

    public function __construct(
        CardManageService $cardManageService,
        DscRepository $dscRepository
    )
    {
        $this->cardManageService = $cardManageService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        /*------------------------------------------------------ */
        //-- 包装列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['07_card_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['card_add'], 'href' => 'card.php?act=add']);
            $this->smarty->assign('full_page', 1);

            $cards_list = $this->cardManageService->cardsList();

            $this->smarty->assign('card_list', $cards_list['card_list']);
            $this->smarty->assign('filter', $cards_list['filter']);
            $this->smarty->assign('record_count', $cards_list['record_count']);
            $this->smarty->assign('page_count', $cards_list['page_count']);

            return $this->smarty->display('card_list.htm');
        }

        /*------------------------------------------------------ */
        //-- ajax列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $cards_list = $this->cardManageService->cardsList();

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
        elseif ($_REQUEST['act'] == 'remove') {
            /* 检查权限 */
            $check_auth = check_authz_json('card_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $card_id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $card_name = Card::where('card_id', $card_id)->value('card_name');
            $name = $card_name ? $card_name : '';

            $this->dscRepository->getDelBatch('', $card_id, ['card_img'], 'card_id', Card::whereRaw(1), 0, DATA_DIR . '/cardimg/'); //删除图片

            $res = Card::where('card_id', $card_id)->delete();

            if ($res) {
                admin_log(addslashes($name), 'remove', 'card');
            }

            $url = 'card.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 添加新包装
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
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
        }

        /*------------------------------------------------------ */
        //-- 插入新包装
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert') {
            /* 权限判断 */
            admin_priv('card_manage');

            $card_name = isset($_POST['card_name']) && !empty($_POST['card_name']) ? trim($_POST['card_name']) : '';
            $card_fee = isset($_POST['card_fee']) && !empty($_POST['card_fee']) ? floatval(trim($_POST['card_fee'])) : 0;
            $free_money = isset($_POST['free_money']) && !empty($_POST['free_money']) ? floatval(trim($_POST['free_money'])) : 0;
            $card_desc = isset($_POST['card_desc']) && !empty($_POST['card_desc']) ? trim($_POST['card_desc']) : '';

            /*检查包装名是否重复*/
            $is_only = Card::where('card_name', $card_name)->count();

            if ($is_only > 0) {
                return sys_msg(sprintf($GLOBALS['_LANG']['cardname_exist'], stripslashes($card_name)), 1);
            }

            /*处理图片*/
            $img_name = basename($image->upload_image($_FILES['card_img'], "cardimg"));

            $this->dscRepository->getOssAddFile([DATA_DIR . '/cardimg/' . $img_name]);

            /* 插入数据 */
            $other = [
                'card_name' => $card_name,
                'card_fee' => $card_fee,
                'free_money' => $free_money,
                'card_desc' => $card_desc,
                'card_img' => $img_name,
                'user_id' => $adminru['ru_id']
            ];
            Card::insertGetId($other);

            admin_log($card_name, 'add', 'card');

            /*添加链接*/
            $link[0]['text'] = $GLOBALS['_LANG']['continue_add'];
            $link[0]['href'] = 'card.php?act=add';

            $link[1]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[1]['href'] = 'card.php?act=list';

            return sys_msg($_POST['card_name'] . $GLOBALS['_LANG']['cardadd_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑包装
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            /* 权限判断 */
            admin_priv('card_manage');

            $card_id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $card = $this->cardManageService->cardInfo($card_id);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['card_edit']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['07_card_list'], 'href' => 'card.php?act=list&' . list_link_postfix()]);
            $this->smarty->assign('card', $card);
            $this->smarty->assign('form_action', 'update');


            return $this->smarty->display('card_info.htm');
        }

        /*------------------------------------------------------ */
        //-- 更新包装
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update') {
            /* 权限判断 */
            admin_priv('card_manage');

            $id = isset($_POST['id']) && !empty($_POST['id']) ? intval($_POST['id']) : 0;
            $card_name = isset($_POST['card_name']) && !empty($_POST['card_name']) ? trim($_POST['card_name']) : '';
            $old_cardname = isset($_POST['old_cardname']) && !empty($_POST['old_cardname']) ? trim($_POST['old_cardname']) : '';
            $card_fee = isset($_POST['card_fee']) && !empty($_POST['card_fee']) ? floatval(trim($_POST['card_fee'])) : 0;
            $free_money = isset($_POST['free_money']) && !empty($_POST['free_money']) ? floatval(trim($_POST['free_money'])) : 0;
            $card_desc = isset($_POST['card_desc']) && !empty($_POST['card_desc']) ? trim($_POST['card_desc']) : '';

            if ($card_name != $old_cardname) {
                /*检查品牌名是否相同*/
                $is_only = Card::where('card_name', $card_name)->where('card_id', '<>', $id)->count();

                if ($is_only > 0) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['cardname_exist'], stripslashes($card_name)), 1);
                }
            }

            /* 处理图片 */
            $img_name = basename($image->upload_image($_FILES['card_img'], "cardimg", $_POST['old_cardimg']));

            $this->dscRepository->getOssAddFile([DATA_DIR . '/cardimg/' . $img_name]);

            $other = [
                'card_name' => $card_name,
                'card_fee' => $card_fee,
                'free_money' => $free_money,
                'card_desc' => $card_desc
            ];

            if ($img_name) {
                $other['card_img'] = $img_name;
            }

            $res = Card::where('card_id', $id)->update($other);

            if ($res) {
                admin_log($_POST['card_name'], 'edit', 'card');
            }

            $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[0]['href'] = 'card.php?act=list&' . list_link_postfix();

            $note = sprintf($GLOBALS['_LANG']['cardedit_succeed'], $_POST['card_name']);
            return sys_msg($note, 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 删除卡片图片
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_card_img') {

            /* 权限判断 */
            admin_priv('card_manage');
            $card_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

            $this->dscRepository->getDelBatch('', $card_id, ['card_img'], 'card_id', Card::whereRaw(1), 0, DATA_DIR . '/cardimg/'); //删除图片

            Card::where('card_id', $card_id)->update([
                'card_img' => ''
            ]);

            $link = [['text' => $GLOBALS['_LANG']['card_edit_lnk'], 'href' => 'card.php?act=edit&id=' . $card_id], ['text' => $GLOBALS['_LANG']['card_list_lnk'], 'href' => 'brand.php?act=list']];
            return sys_msg($GLOBALS['_LANG']['drop_card_img_success'], 0, $link);
        }
        /*------------------------------------------------------ */
        //-- ajax编辑卡片名字
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_card_name') {
            $check_auth = check_authz_json('card_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $card_id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $card_name = empty($_REQUEST['val']) ? '' : json_str_iconv(trim($_REQUEST['val']));

            $is_only = Card::where('card_name', $card_name)->where('card_id', '<>', $card_id)->count();

            if ($is_only > 0) {
                return make_json_error(sprintf($GLOBALS['_LANG']['cardname_exist'], $card_name));
            }

            $old_card_name = Card::where('card_id', $card_id)->value('card_name');
            $old_card_name = $old_card_name ? $old_card_name : '';

            $res = Card::where('card_id', $card_id)->update([
                'card_name' => $card_name
            ]);

            if ($res) {
                admin_log(addslashes($old_card_name), 'edit', 'card');
            }

            return make_json_result(stripcslashes($card_name));
        }

        /*------------------------------------------------------ */
        //-- ajax编辑卡片费用
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_card_fee') {
            $check_auth = check_authz_json('card_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $card_id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $card_fee = empty($_REQUEST['val']) ? 0.00 : floatval($_REQUEST['val']);

            $card_name = Card::where('card_id', $card_id)->value('card_name');
            $card_name = $card_name ? $card_name : '';

            $res = Card::where('card_id', $card_id)->update([
                'card_fee' => $card_fee
            ]);

            if ($res) {
                admin_log(addslashes($card_name), 'edit', 'card');
            }

            return make_json_result($card_fee);
        }

        /*------------------------------------------------------ */
        //-- ajax编辑费额度
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_free_money') {
            $check_auth = check_authz_json('card_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $card_id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $free_money = empty($_REQUEST['val']) ? 0.00 : floatval($_REQUEST['val']);

            $card_name = Card::where('card_id', $card_id)->value('card_name');
            $card_name = $card_name ? $card_name : '';

            $res = Card::where('card_id', $card_id)->update([
                'free_money' => $free_money
            ]);

            if ($res) {
                admin_log(addslashes($card_name), 'edit', 'card');
            }

            return make_json_result($free_money);
        }
    }
}
