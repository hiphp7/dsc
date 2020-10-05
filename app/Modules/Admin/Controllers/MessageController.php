<?php

namespace App\Modules\Admin\Controllers;

use App\Models\AdminMessage;
use App\Models\AdminUser;
use App\Repositories\Common\BaseRepository;
use App\Services\Message\MessageManageService;

/**
 * 管理中心管理员留言程序
 */
class MessageController extends InitController
{
    protected $baseRepository;
    protected $messageManageService;

    public function __construct(
        BaseRepository $baseRepository,
        MessageManageService $messageManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->messageManageService = $messageManageService;
    }

    public function index()
    {

        /* act操作项的初始化 */
        $_REQUEST['act'] = trim($_REQUEST['act']);
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        }

        /*------------------------------------------------------ */
        //-- 留言列表页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 权限的判断 */
            admin_priv('message_manage');

            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['msg_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['send_msg'], 'href' => 'message.php?act=send']);

            $list = $this->messageManageService->getMessageList();

            $this->smarty->assign('message_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('message_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 翻页、排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $list = $this->messageManageService->getMessageList();

            $this->smarty->assign('message_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('message_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 留言发送页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'send') {
            /* 权限的判断 */
            admin_priv('message_manage');

            /* 获取管理员列表 */
            $res = AdminUser::whereRaw(1);
            $admin_list = $this->baseRepository->getToArrayGet($res);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['send_msg']);
            $this->smarty->assign('action_link', ['href' => 'message.php?act=list', 'text' => $GLOBALS['_LANG']['msg_list']]);
            $this->smarty->assign('action', 'add');
            $this->smarty->assign('form_act', 'insert');
            $this->smarty->assign('admin_list', $admin_list);


            return $this->smarty->display('message_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 处理留言的发送
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert') {
            /* 权限的判断 */
            admin_priv('message_manage');

            $rec_arr = $_POST['receiver_id'];

            /* 向所有管理员发送留言 */
            if ($rec_arr[0] == 0) {
                /* 获取管理员信息 */
                $res = AdminUser::where('user_id', '<>', session('admin_id'));
                $result = $this->baseRepository->getToArrayGet($res);

                foreach ($result as $rows) {
                    $data = [
                        'sender_id' => session('admin_id'),
                        'receiver_id' => $rows['user_id'],
                        'sent_time' => gmtime(),
                        'read_time' => 0,
                        'readed' => 0,
                        'deleted' => 0,
                        'title' => $_POST['title'],
                        'message' => $_POST['message'],
                    ];
                    AdminMessage::insert($data);

                }

                /*添加链接*/
                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'message.php?act=list';

                $link[1]['text'] = $GLOBALS['_LANG']['continue_send_msg'];
                $link[1]['href'] = 'message.php?act=send';

                return sys_msg($GLOBALS['_LANG']['send_msg'] . "&nbsp;" . $GLOBALS['_LANG']['action_succeed'], 0, $link);

                /* 记录管理员操作 */
                admin_log(admin_log($GLOBALS['_LANG']['send_msg']), 'add', 'admin_message');
            } else {
                /* 如果是发送给指定的管理员 */
                foreach ($rec_arr as $key => $id) {
                    $data = [
                        'sender_id' => session('admin_id'),
                        'receiver_id' => $id,
                        'sent_time' => gmtime(),
                        'read_time' => 0,
                        'readed' => 0,
                        'deleted' => 0,
                        'title' => $_POST['title'],
                        'message' => $_POST['message'],
                    ];
                    AdminMessage::insert($data);

                }
                admin_log(addslashes($GLOBALS['_LANG']['send_msg']), 'add', 'admin_message');

                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'message.php?act=list';
                $link[1]['text'] = $GLOBALS['_LANG']['continue_send_msg'];
                $link[1]['href'] = 'message.php?act=send';

                return sys_msg($GLOBALS['_LANG']['send_msg'] . "&nbsp;" . $GLOBALS['_LANG']['action_succeed'], 0, $link);
            }
        }
        /*------------------------------------------------------ */
        //-- 留言编辑页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            /* 权限的判断 */
            admin_priv('message_manage');

            $id = intval($_REQUEST['id']);

            /* 获取管理员列表 */
            $res = AdminUser::whereRaw(1);
            $admin_list = $this->baseRepository->getToArrayGet($res);

            /* 获得留言数据*/
            $res = AdminMessage::where('message_id', $id);
            $msg_arr = $this->baseRepository->getToArrayFirst($res);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit_msg']);
            $this->smarty->assign('action_link', ['href' => 'message.php?act=list', 'text' => $GLOBALS['_LANG']['msg_list']]);
            $this->smarty->assign('form_act', 'update');
            $this->smarty->assign('admin_list', $admin_list);
            $this->smarty->assign('msg_arr', $msg_arr);


            return $this->smarty->display('message_info.dwt');
        } elseif ($_REQUEST['act'] == 'update') {
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $title = isset($_POST['title']) && !empty($_POST['title']) ? trim($_POST['id']) : '';
            $message = isset($_POST['message']) && !empty($_POST['message']) ? trim($_POST['message']) : '';

            /* 获得留言数据*/
            $msg_arr = [];
            $res = AdminMessage::where('message_id', $id);
            $msg_arr = $this->baseRepository->getToArrayFirst($res);

            $data = [
                'title' => $title,
                'message' => $message,
            ];
            AdminMessage::where('sender_id', $msg_arr['sender_id'])
                ->where('sent_time', $msg_arr['send_time'])
                ->update($data);

            $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[0]['href'] = 'message.php?act=list';

            return sys_msg($GLOBALS['_LANG']['edit_msg'] . ' ' . $GLOBALS['_LANG']['action_succeed'], 0, $link);

            /* 记录管理员操作 */
            admin_log(addslashes($GLOBALS['_LANG']['edit_msg']), 'edit', 'admin_message');
        }

        /*------------------------------------------------------ */
        //-- 留言查看页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'view') {
            /* 权限的判断 */
            admin_priv('message_manage');

            $msg_id = intval($_REQUEST['id']);

            /* 获得管理员留言数据 */
            $msg_arr = [];

            $res = AdminMessage::where('message_id', $msg_id);
            $res = $res->with(['getAdminUser' => function ($query) {
                $query->select('user_id', 'user_name');
            }]);
            $msg_arr = $this->baseRepository->getToArrayFirst($res);
            $msg_arr['user_name'] = '';
            if (isset($msg_arr['get_admin_user']) && !empty($msg_arr['get_admin_user'])) {
                $msg_arr['user_name'] = $msg_arr['get_admin_user']['user_name'];
            }

            $msg_arr['title'] = nl2br(htmlspecialchars($msg_arr['title']));
            $msg_arr['message'] = nl2br(htmlspecialchars($msg_arr['message']));
            $msg_arr['sent_time'] = local_date($GLOBALS['_CFG']['time_format'], $msg_arr['sent_time']);
            $msg_arr['read_time'] = local_date($GLOBALS['_CFG']['time_format'], $msg_arr['read_time']);

            /* 如果还未阅读 */
            if ($msg_arr['readed'] == 0) {
                $msg_arr['read_time'] = gmtime(); //阅读日期为当前日期

                //更新阅读日期和阅读状态
                $data = [
                    'read_time' => $msg_arr['read_time'],
                    'readed' => 1
                ];
                AdminMessage::where('message_id', $msg_id)->update($data);


                $msg_arr['read_time'] = local_date($GLOBALS['_CFG']['time_format'], $msg_arr['read_time']);
            }

            //模板赋值，显示
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['view_msg']);
            $this->smarty->assign('action_link', ['href' => 'message.php?act=list', 'text' => $GLOBALS['_LANG']['msg_list']]);
            $this->smarty->assign('admin_user', session('admin_name'));
            $this->smarty->assign('msg_arr', $msg_arr);

            return $this->smarty->display('message_view.dwt');
        }

        /*------------------------------------------------------ */
        //--留言回复页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'reply') {
            /* 权限的判断 */
            admin_priv('message_manage');

            $msg_id = intval($_REQUEST['id']);

            /* 获得留言数据 */
            $msg_val = [];

            $res = AdminMessage::where('message_id', $msg_id);
            $res = $res->with(['getAdminUser' => function ($query) {
                $query->select('user_id', 'user_name');
            }]);
            $msg_arr = $this->baseRepository->getToArrayFirst($res);
            $msg_arr['user_name'] = '';
            if (isset($msg_arr['get_admin_user']) && !empty($msg_arr['get_admin_user'])) {
                $msg_arr['user_name'] = $msg_arr['get_admin_user']['user_name'];
            }

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['reply_msg']);
            $this->smarty->assign('action_link', ['href' => 'message.php?act=list', 'text' => $GLOBALS['_LANG']['msg_list']]);

            $this->smarty->assign('action', 'reply');
            $this->smarty->assign('form_act', 're_msg');
            $this->smarty->assign('msg_val', $msg_arr);


            return $this->smarty->display('message_info.dwt');
        }

        /*------------------------------------------------------ */
        //--留言回复的处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 're_msg') {
            $id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
            /* 权限的判断 */
            admin_priv('message_manage');

            $data = [
                'sender_id' => session('admin_id'),
                'receiver_id' => $id,
                'sent_time' => gmtime(),
                'read_time' => 0,
                'readed' => 0,
                'deleted' => 0,
                'title' => $_POST['title'],
                'message' => $_POST['message'],
            ];
            AdminMessage::insert($data);

            $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[0]['href'] = 'message.php?act=list';

            return sys_msg($GLOBALS['_LANG']['send_msg'] . ' ' . $GLOBALS['_LANG']['action_succeed'], 0, $link);

            /* 记录管理员操作 */
            admin_log(addslashes($GLOBALS['_LANG']['send_msg']), 'add', 'admin_message');
        }

        /*------------------------------------------------------ */
        //-- 批量删除留言记录
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_msg') {
            /* 权限的判断 */
            admin_priv('message_manage');

            if (isset($_POST['checkboxes'])) {
                $count = 0;
                foreach ($_POST['checkboxes'] as $key => $id) {
                    $data = ['deleted' => 1];
                    $res = AdminMessage::where('message_id', $id);
                    $admin_id = session('admin_id');
                    $res = $res->where(function ($query) use ($admin_id) {
                        $query->where('sender_id', $admin_id)->orWhere('receiver_id', $admin_id);
                    });
                    $res->update($data);
                    $count++;
                }

                admin_log('', 'remove', 'admin_message');
                $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'message.php?act=list'];
                return sys_msg(sprintf($GLOBALS['_LANG']['batch_drop_success'], $count), 0, $link);
            } else {
                return sys_msg($GLOBALS['_LANG']['no_select_msg'], 1);
            }
        }

        /*------------------------------------------------------ */
        //-- 删除留言
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            /* 权限的判断 */
            admin_priv('message_manage');

            $id = intval($_GET['id']);

            $data = ['deleted' => 1];
            $res = AdminMessage::where('message_id', $id);
            $admin_id = session('admin_id');
            $res = $res->where(function ($query) use ($admin_id) {
                $query->where('sender_id', $admin_id)->orWhere('receiver_id', $admin_id);
            });
            $res->update($data);

            $url = 'message.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }
    }
}
