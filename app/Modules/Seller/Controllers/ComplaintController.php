<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Exchange;
use App\Libraries\Image;
use App\Repositories\Common\DscRepository;

/**
 * 交易投诉
 */
class ComplaintController extends InitController
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

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }
        $adminru = get_admin_ru_id();
        $exc = new Exchange($this->dsc->table("complaint"), $this->db, 'complaint_id', 'title_id');
        $this->smarty->assign('menu_select', ['action' => '04_order', 'current' => '11_complaint']);
        $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['04_order']);
        if ($_REQUEST['act'] == 'list') {
            admin_priv('complaint');
            //页面赋值
            $this->smarty->assign("ur_here", $GLOBALS['_LANG']['11_complaint']);
            $complaint_list = $this->get_complaint_list();
            $this->smarty->assign('complaint_list', $complaint_list['list']);
            $this->smarty->assign('filter', $complaint_list['filter']);
            $this->smarty->assign('record_count', $complaint_list['record_count']);
            $this->smarty->assign('page_count', $complaint_list['page_count']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign("act_type", $_REQUEST['act']);

            return $this->smarty->display("complaint.dwt");
        }
        /*------------------------------------------------------ */
        //-- Ajax投诉内容
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $check_auth = check_authz_json('complaint');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $complaint_list = $this->get_complaint_list();
            $this->smarty->assign('complaint_list', $complaint_list['list']);
            $this->smarty->assign('filter', $complaint_list['filter']);
            $this->smarty->assign('record_count', $complaint_list['record_count']);
            $this->smarty->assign('page_count', $complaint_list['page_count']);

            return make_json_result(
                $this->smarty->fetch('complaint.dwt'),
                '',
                ['filter' => $complaint_list['filter'], 'page_count' => $complaint_list['page_count']]
            );
        }
        /*------------------------------------------------------ */
        //-- 处理投诉
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'view') {
            admin_priv('complaint');
            load_helper('order');
            $complaint_id = !empty($_REQUEST['complaint_id']) ? intval($_REQUEST['complaint_id']) : 0;
            $this->smarty->assign("ur_here", $GLOBALS['_LANG']['complaint_view']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['11_complaint'], 'href' => 'complaint.php?act=list']);
            $complaint_info = get_complaint_info($complaint_id);
            $talk_list = checkTalkView($complaint_id, 'seller');

            //获取订单详情
            $order_info = order_info($complaint_info['order_id']);

            if ($order_info) {
                $order_info['order_goods'] = get_order_goods_toInfo($order_info['order_id']);
                $order_info['status'] = $GLOBALS['_LANG']['os'][$order_info['order_status']] . ',' . $GLOBALS['_LANG']['ps'][$order_info['pay_status']] . ',' . $GLOBALS['_LANG']['ss'][$order_info['shipping_status']];

                $this->smarty->assign('talk_list', $talk_list);
                $this->smarty->assign("complaint_info", $complaint_info);
                $this->smarty->assign("order_info", $order_info);
            } else {
                return redirect(SELLER_PATH . '/complaint.php?act=list');
            }

            return $this->smarty->display('complaint_view.dwt');
        }
        /*------------------------------------------------------ */
        //-- 上传申诉图片
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'upload_img') {
            $check_auth = check_authz_json('complaint');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $result = ['content' => '', 'sgs' => ''];

            $complaint_id = !empty($_REQUEST['complaint_id']) ? intval($_REQUEST['complaint_id']) : 0;
            $order_id = !empty($_REQUEST['order_id']) ? intval($_REQUEST['order_id']) : 0;
            $img_file = isset($_FILES['file']) ? $_FILES['file'] : [];

            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

            $img_file = $image->upload_image($img_file, 'appeal_img/' . date('Ym')); //原图
            if ($img_file === false) {
                $result['error'] = 1;
                $result['msg'] = $image->error_msg();
                return response()->json($result);
            }

            $this->dscRepository->getOssAddFile([$img_file]);

            $report = [
                'order_id' => $order_id,
                'ru_id' => $adminru['ru_id'],
                'img_file' => $img_file,
                'complaint_id' => $complaint_id
            ];

            $sql = "SELECT count(*) FROM " . $this->dsc->table('appeal_img') . " WHERE complaint_id = '$complaint_id' AND order_id = '$order_id'";
            $img_count = $this->db->getOne($sql);

            if ($img_count < 5 && $img_file) {
                $this->db->autoExecute($this->dsc->table('appeal_img'), $report, 'INSERT');
                $result['img_id'] = $this->db->insert_id();//获取id
                $result['img_file'] = get_image_path($img_file);
            } else {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['complaint_img_number'];
            }
            return response()->json($result);
        }
        /*------------------------------------------------------ */
        //-- 删除申诉图片
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'del_img') {
            $check_auth = check_authz_json('complaint');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $result = ['error' => '', 'message' => ''];
            $complaint_id = !empty($_REQUEST['complaint_id']) ? intval($_REQUEST['complaint_id']) : 0;
            $img_id = !empty($_REQUEST['img_id']) ? intval($_REQUEST['img_id']) : 0;
            if ($img_id > 0) {
                $sql = "SELECT img_file FROM" . $this->dsc->table('appeal_img') . " WHERE img_id = '$img_id' AND complaint_id = '$complaint_id' LIMIT 1";
                $img_file = $this->db->getOne($sql);

                if ($img_file) {
                    $this->dscRepository->getOssDelFile([$img_file]);
                    @unlink(storage_public($img_file));
                }
                $sql = "DELETE FROM " . $this->dsc->table('appeal_img') . " WHERE img_id = '$img_id'";
                $this->db->query($sql);
            } else {
                $result['error'] = "1";
                $result['message'] = $GLOBALS['_LANG']['unknown_error'];
            }
            return response()->json($result);
        }
        /*------------------------------------------------------ */
        //-- 删除投诉
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('complaint');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $id = intval($_GET['id']);
            //删除相关图片
            del_complaint_img($id);
            del_complaint_img($id, 'appeal_img');
            //删除相关聊天
            del_complaint_talk($id);
            $exc->drop($id);
            $url = 'complaint.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        }
        /*------------------------------------------------------ */
        //-- 申诉�        �库/提交仲裁
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'appeal_submit') {
            admin_priv('complaint');
            $complaint_id = !empty($_REQUEST['complaint_id']) ? intval($_REQUEST['complaint_id']) : 0;
            $appeal_messg = !empty($_REQUEST['appeal_messg']) ? trim($_REQUEST['appeal_messg']) : '';
            $state_type = !empty($_REQUEST['state_type']) ? intval($_REQUEST['state_type']) : 0;
            $time = gmtime();
            $set = '';
            if ($state_type == 0) {
                $set = ",appeal_messg = '$appeal_messg',appeal_time = '$time'";
            }
            $sql = " UPDATE" . $this->dsc->table('complaint') . " SET complaint_state = complaint_state+1 $set WHERE complaint_id = '$complaint_id'";
            $this->db->query($sql);
            $link[0]['text'] = $GLOBALS['_LANG']['back_info'];
            $link[0]['href'] = 'complaint.php?act=view&complaint_id=' . $complaint_id;
            return sys_msg($GLOBALS['_LANG']['handle_success'], 0, $link);
        }
        /*------------------------------------------------------ */
        //-- 发布聊天
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'talk_release') {
            $check_auth = check_authz_json('complaint');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $result = ['error' => '', 'message' => ''];
            $complaint_id = !empty($_REQUEST['complaint_id']) ? intval($_REQUEST['complaint_id']) : 0;
            $talk_content = !empty($_REQUEST['talk_content']) ? trim($_REQUEST['talk_content']) : '';
            $type = !empty($_REQUEST['type']) ? intval($_REQUEST['type']) : 0;
            //执行操作类型  1、刷新，0入库
            if ($type == 0) {
                $complaint_talk = [
                    'complaint_id' => $complaint_id,
                    'talk_member_id' => $adminru['ru_id'],
                    'talk_member_name' => session('seller_name'),
                    'talk_member_type' => 2,
                    'talk_content' => $talk_content,
                    'talk_time' => gmtime(),
                    'view_state' => 'seller'
                ];
                $this->db->autoExecute($this->dsc->table('complaint_talk'), $complaint_talk, 'INSERT');
            }
            $talk_list = checkTalkView($complaint_id, 'seller');
            $this->smarty->assign('talk_list', $talk_list);

            $result['content'] = $this->smarty->fetch("library/talk_list.lbi");
            return response()->json($result);
        }
    }

    //获取纠纷列表
    private function get_complaint_list()
    {
        $result = get_filter();
        if ($result === false) {
            $adminru = get_admin_ru_id();
            $where = ' WHERE 1 ';
            /* 初始化分页参数 */
            $filter = [];
            $filter['handle_type'] = !empty($_REQUEST['handle_type']) ? $_REQUEST['handle_type'] : '-1';
            $filter['keywords'] = !empty($_REQUEST['keywords']) ? trim($_REQUEST['keywords']) : '';

            if ($filter['keywords']) {
                $where .= " AND (user_name LIKE '%" . mysql_like_quote($filter['keywords']) . "%' OR order_sn LIKE '%" . mysql_like_quote($filter['keywords']) . "%')";
            }
            if ($filter['handle_type'] != '-1') {
                $where .= " AND complaint_state = '" . $filter['handle_type'] . "'";
            }
            $where .= " AND ru_id = '" . $adminru['ru_id'] . "' AND complaint_active = 1";
            /* 查询记录总数，计算分页数 */
            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('complaint') . $where;
            $filter['record_count'] = $this->db->getOne($sql);
            $filter = page_and_size($filter);

            /* 查询记录 */
            $sql = "SELECT complaint_id,order_id,order_sn,user_id,user_name,ru_id,shop_name,title_id,complaint_content,add_time,complaint_handle_time,"
                . "admin_id,appeal_messg,appeal_time,end_handle_time,end_admin_id,complaint_state,complaint_active FROM" . $this->dsc->table('complaint')
                . " $where ORDER BY add_time DESC ";
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }
        $res = $this->db->selectLimit($sql, $filter['page_size'], $filter['start']);

        $arr = [];
        $k = 0;
        foreach ($res as $rows) {
            if ($rows['title_id'] > 0) {
                $sql_title = "SELECT title_name FROM " . $this->dsc->table("complain_title") . "WHERE title_id = '" . $rows['title_id'] . "'";
                $rows['title_name'] = $this->db->getOne($sql_title);
            }
            //获取举报图片列表
            $sql = "SELECT img_file ,img_id FROM " . $GLOBALS["dsc"]->table('complaint_img') . " WHERE complaint_id = '" . $rows['complaint_id'] . "' ORDER BY  img_id DESC";
            $img_list = $this->db->getAll($sql);
            if (!empty($img_list)) {
                foreach ($img_list as $k => $v) {
                    $img_list[$k]['img_file'] = get_image_path($v['img_file']);
                }
            }
            $rows['img_list'] = $img_list;

            $rows['has_talk'] = 0;
            //获取是否存在未读信息
            if ($rows['complaint_state'] > 1) {
                $sql = "SELECT view_state FROM" . $this->dsc->table('complaint_talk') . "WHERE complaint_id='" . $rows['complaint_id'] . "' ORDER BY talk_time DESC";
                $talk_list = $this->db->getAll($sql);
                if ($talk_list) {
                    foreach ($talk_list as $k => $v) {
                        if ($v['view_state']) {
                            $view_state = explode(',', $v['view_state']);
                            if (!in_array('seller', $view_state)) {
                                $rows['has_talk'] = 1;
                                break;
                            }
                        }
                    }
                }
            }

            $arr[] = $rows;
        }
        return ['list' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }
}
