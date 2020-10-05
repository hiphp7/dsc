<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\ImConfigure;
use App\Models\ImMessage;
use App\Models\ImService;
use App\Models\Kefu;
use App\Repositories\Common\TimeRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Class AdminController
 * @package App\Http\Controllers\Chat
 */
class AdminController extends Controller
{
    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var TimeRepository
     */
    protected $timeRepository;

    /**
     * AdminController constructor.
     * @param TimeRepository $timeRepository
     */
    public function __construct(TimeRepository $timeRepository)
    {
        $this->timeRepository = $timeRepository;
    }

    protected function initialize()
    {
        $this->config = config('chat');
    }

    /**
     * admin.index
     */
    public function index()
    {
        if (is_mobile_device()) {
            return redirect()->route('kefu.adminp.mobile');
        }

        $signInData = $this->userCheck();
        $admin = $signInData['admin'];
        $service = $signInData['service'];

        /** 验证失败则跳转到登录页 */
        if (empty($signInData['service'])) {
            return redirect()->route('kefu.login.index');
        }
        if ($service['chat_status'] == 1 && empty(session("kefu_id"))) {
            return show_message('客服已登录', 'login/index');
        }

        /**
         * socket server
         */
        if (empty($this->config['listen_route'])) {
            $listen_route = $this->getServerIp();
        } else {
            $listen_route = $this->config['listen_route'];
        }

        if (empty($this->config['port'])) {
            return show_message('socket端口号未配置');
        }

        /** 等待接入 */
        $waitMessageArr = Kefu::getWait($admin['ru_id']);
        if (is_null($waitMessageArr)) {
            $waitMessageArr = [
                'waitMessage' => [[]], // 预留数组生成tr
                'waitMessageDataList' => [],
                'total' => 0,
            ];
        }

        $this->assign('total_wait', $waitMessageArr['total']); //
        $this->assign('wait_message_list', json_encode($waitMessageArr['waitMessageDataList'])); //待接入消息列表

        /** 聊天记录 */
        $messageList = Kefu::getChatLog($service);

        $this->assign('message_list', $messageList);
        $this->assign('message_list_json', json_encode($messageList));

        /** 快捷回复 */
        $reply = Kefu::getReply($service['id'], 1);
        $this->assign('reply', json_encode($reply)); //

        $reply = Kefu::getReply($service['id'], 2);
        $this->assign('take_reply', json_encode($reply)); //

        $reply = Kefu::getReply($service['id'], 3);
        $this->assign('leave_reply', json_encode($reply)); //

        /** 没有聊天默认图片 */
        $this->assign('mouse_img', asset('assets/chat/images/mouse.png'));
        $this->assign('root_path', url('/') . '/');
        $this->assign('listen_route', $listen_route);
        $this->assign('port', $this->config['port']);

        $this->assign('user_id', $service['id']);
        $this->assign('store_id', $admin['ru_id']);
        $this->assign('nick_name', $service['nick_name']);
        $this->assign('wait_message', $waitMessageArr['waitMessage']);
        $this->assign('image_path', asset('assets/chat/images'));

        $storeInfo = Kefu::getStoreInfo($admin['ru_id']);

        $this->assign('avatar', $storeInfo['logo_thumb']); //
        $this->assign('user_name', $storeInfo['shop_name']); //

        //判断https
        $this->assign('is_ssl', is_ssl()); //
        //获取客服列表
        $serviceList = Kefu::getServiceList($admin['ru_id'], $service['id']);

        $this->assign('service_list', $serviceList); //

        // 改变客服登录状态
        $_GET['id'] = $service['id'];
        $_GET['status'] = 1;
        $this->changeLogin();

        return $this->display('kefu.admin');
    }

    /**
     * 登录验证
     */
    private function userCheck()
    {
        // 检查cookie
        $ecscpCookie = request()->cookie('ECSCP');

        // 记住密码验证
        if (!is_null($ecscpCookie) && isset($ecscpCookie['kefu_id'])) {
            $kefu_id = intval($ecscpCookie['kefu_id']);
            $service = Kefu::getServiceById($kefu_id);

            $adminId = $service['user_id'];
            if (!empty($adminId)) {
                $admin = Kefu::getAdmin($adminId);

                $token = md5($admin['password'] . C('hash_code'));
                if ($token == $ecscpCookie['kefu_token']) {
                    return [
                        'admin' => $admin,
                        'service' => $service
                    ];
                }
            }
        }

        //没有记住密码  判断是否已登录
        $kefuId = session('kefu_id');
        $adminId = session('kefu_admin_id');  // 管理员ID
        $admin = Kefu::getAdmin($adminId);
        $service = Kefu::getService($adminId);
        if (empty($service) || $service['id'] != $kefuId) {
            return false;
        }

        return [
            'admin' => $admin,
            'service' => $service
        ];
    }

    /**
     * 聊天列表
     */
    public function history()
    {
        $uid = request()->get('uid', 0);
        $tid = request()->get('tid', 0);
        $page = request()->get('page', 0);
        $keyword = request()->get('keyword', '');
        $time = request()->get('time', '');

        return Kefu::getHistory($uid, $tid, $keyword, $time, $page);
    }

    /**
     * 搜索最近10条记录
     */
    public function searchhistory()
    {
        $mid = request()->get('mid', 0);

        return Kefu::getSearchHistory($mid);
    }

    /**
     * 将未读消息改为已读
     */
    public function changeMessageStatus()
    {
        $serviceId = session('kefu_id');
        $customId = request()->get('id', 0);
        if (empty($serviceId)) {
            return ['error' => 1, 'msg' => '没有客服'];
        }
        Kefu::changeMessageStatus($serviceId, $customId);
    }

    /**
     * 获取商品信息
     */
    public function getGoods()
    {
        //获取商品信息
        $gid = empty($_POST['gid']) ? 0 : intval($_POST['gid']);
        if ($gid == 0) {
            return ['error' => 1, 'content' => "invalid params"];
        }
        return Kefu::getGoods($gid);
    }

    /**
     * 获取店铺信息
     */
    public function getStore()
    {
        //获取店铺信息
        $sid = empty($_POST['sid']) ? 0 : intval($_POST['sid']);
        if ($sid == 0) {
            return ['error' => 1, 'content' => "invalid params"];
        }
        return Kefu::getStoreInfo($sid);
    }

    /**
     * 添加快捷回复
     */
    public function addReply()
    {
        $content = request()->get('content');
        $customerId = session('kefu_id');

        $data['ser_id'] = $customerId;
        $data['type'] = 1;
        $data['content'] = addslashes($content);
        $data['is_on'] = 0;
        $id = ImConfigure::create($data);

        return ['error' => 0, 'id' => $id];
    }

    /**
     * 删除快捷回复
     */
    public function removeReply()
    {
        $id = request()->get('id', 0);
        $customerId = session('kefu_id');

        ImConfigure::whereRaw('id=' . $id . ' and ser_id=' . $customerId)->delete();

        return ['error' => 0];
    }

    /**
     * 修改客服状态
     */
    public function changeStatus()
    {
        $status = request()->get('status');
        $customerId = session('kefu_id');

        $data['chat_status'] = $status;
        $id = ImService::whereRaw('id=' . $customerId)->update($data);

        return ['error' => 0, 'id' => $id];
    }

    /**
     * 添加接入回复
     */
    public function insertUserReply()
    {
        $mid = request()->get('mid', 0);
        $content = request()->get('content', '');
        $customerId = session('kefu_id');

        $res = ImConfigure::where('id', $mid)->first();

        $data['ser_id'] = $customerId;
        $data['type'] = 2;
        $data['content'] = trim($content);
        if (!empty($res)) {
            $mid = ImConfigure::where('id', $mid)->update($data);
        } else {
            $mid = ImConfigure::create($data);
        }
        return ['error' => 0, 'mid' => $mid];
    }

    /**
     * 接入回复是否开启
     */
    public function takeUserReply()
    {
        $id = request()->get('id', 0);
        $status = request()->get('status', 0);
        if (empty($id)) {
            return ['error' => 1, 'msg' => '请先编辑接入回复'];
        }

        $data['is_on'] = $status;
        $id = ImConfigure::where('id', $id)->update($data);
        return ['error' => 0, 'id' => $id];
    }

    /**
     * 添加离开回复
     */
    public function insertUserLeaveReply()
    {
        $mid = request()->get('mid', 0);
        $content = request()->get('content', '');
        $customerId = session('kefu_id');

        $res = ImConfigure::where('id', $mid)->find();

        $data['ser_id'] = $customerId;
        $data['type'] = 3;
        $data['content'] = trim($content);
        if (!empty($res)) {
            $mid = ImConfigure::where('id', $mid)->update($data);
        } else {
            $mid = ImConfigure::create($data);
        }
        return ['error' => 0, 'mid' => $mid];
    }

    /**
     * 离开回复是否开启
     */
    public function userLeaveReply()
    {
        $id = request()->get('id', 0);
        $status = request()->get('status', 0);
        if (empty($id)) {
            return ['error' => 1, 'msg' => '请先编辑离开回复'];
        }

        $data['is_on'] = $status;
        $id = ImConfigure::where('id', $id)->update($data);
        return ['error' => 0, 'id' => $id];
    }

    /**
     * 会话信息
     */
    public function dialogInfo()
    {
        $uid = request()->get('uid', 0);
        $cid = request()->get('cid', 0);

        $dialog = Kefu::getRecentDialog($uid, $cid);

        $user = Kefu::userInfo($dialog['customer_id']);

        // if ($dialog) {
        //     $service['id'] = $dialog['id'];
        // }

        $dialogInfo = [
            'customer_id' => $dialog['customer_id'],
            'avatar' => $user['avatar'],
            'name' => $user['user_name'],
            'services_id' => $uid,
            'goods' => ($dialog['goods_id'] > 0) ? Kefu::getGoods($dialog['goods_id']) : '',
            'store_id' => $dialog['store_id'],
            'start_time' => $dialog['start_time'],
            'origin' => ($dialog['origin'] == 1) ? "PC" : "H5",
            // 'message' => Kefu::getChatLog($service),
        ];
        return $dialogInfo;
    }

    /**
     * 关闭会话
     */
    public function closeDialog()
    {
        $uid = request()->get('uid', 0);
        $tid = request()->get('tid', 0);

        Kefu::closeWindow($uid, $tid);
    }

    /**
     * 创建会话
     */
    public function createdialog()
    {
        $uid = request()->get('uid', 0);  //客服
        $fid = request()->get('fid', 0);  //之前的客服
        $cid = request()->get('cid', 0);  //客户ID

        $dialog = Kefu::getRecentDialog($fid, $cid);

        Kefu::addDialog([
            'customer_id' => $dialog['customer_id'],
            'services_id' => $uid,
            'goods_id' => $dialog['goods_id'],
            'store_id' => $dialog['store_id'],
            'start_time' => $dialog['start_time'],
            'origin' => $dialog['origin'],
        ]);
    }

    /**
     * 关闭会话
     * 条件： 超过1个小时没有对话
     * 秒为单位
     */
    public function closeOldDialog()
    {
        $expire = 600;

        return Kefu::closeOldWindow($expire);
    }

    /**
     * socket数据
     * 修改客服登录状态
     */
    public function changeLogin()
    {
        $id = request()->get('id', 0);  //获取客服ID
        $status = request()->get('status', 0);  //获取客服ID
        $status = in_array($status, [0, 1]) ? $status : 0;

        $data['chat_status'] = $status;

        ImService::where('id', $id)->where('status', 1)->update($data);
    }

    /**
     * socket数据
     * 存储消息
     * @from_id
     * @name
     * @time
     * @avatar
     * @goods_id
     * @message_type
     * @user_type
     * @to_id
     */
    public function storageMessage()
    {
        $data = request()->all();  //获取数据

        $fromId = empty($data['from_id']) ? 0 : intval($data['from_id']);
        $toId = empty($data['to_id']) ? 0 : intval($data['to_id']);
        $goodsId = empty($data['goods_id']) ? 0 : intval($data['goods_id']);
        $storeId = empty($data['store_id']) ? 0 : intval($data['store_id']);
        $status = isset($data['status']) && ($data['status'] === 0 || $data['status'] === '0') ? 0 : 1;
        $origin = (empty($data['origin']) || $data['origin'] == 'PC') ? 1 : 2;
        if ($fromId == 0) {
            return;
        }
        $user_type = isset($data['user_type']) && ($data['user_type'] == 'service') ? 2 : 1;

        $dialogData = [
            'customer_id' => ($data['user_type'] == 'service') ? $data['from_id'] : $data['to_id'],
            'services_id' => ($data['user_type'] == 'service') ? $data['to_id'] : $data['from_id'],
            'goods_id' => $goodsId,
            'store_id' => $storeId,
            'start_time' => $this->timeRepository->getGmTime(),
            'end_time' => '',
            'origin' => $origin
        ];

        /** 检查会话表 */
        $dialogId = Kefu::isDialog($dialogData);

        if (!$dialogId) {
            //如果不存在  则创建新会话  并结束之前所有会话
            //添加会话表
            $dialogId = Kefu::addDialog($dialogData);
        }

        //存储
        $data['message'] = escapeHtml($data['message']);
        $d = [
            'from_user_id' => $fromId,
            'to_user_id' => $toId,
            'message' => escapeHtml($data['message']),
            'add_time' => $this->timeRepository->getGmTime(),
            'user_type' => $user_type,
            'dialog_id' => $dialogId,
            'status' => $status
        ];
        $res = ImMessage::create($d);
        if (!$res) {
            logResult('storage_message:' . json_encode($data));
        }
    }

    /**
     * socket数据
     * 修改客户待接入的消息
     */
    public function changeMsgInfo()
    {
        $cusId = request()->get('cus_id', 0);  //客户ID
        $serId = request()->get('ser_id', 0);  //客服ID
        /** 修改会话表 */

        Kefu::updateDialog($cusId, $serId);
    }

    /**
     * 切换客服 更新会话表与消息表
     */
    public function changeNewMsgInfo()
    {
        $cusId = request()->get('cus_id', 0);  //客户ID
        $serId = request()->get('ser_id', 0);  //客服ID
        /** 修改会话表 */

        Kefu::updateNewDialog($cusId, $serId);
    }

    /**
     * socket数据
     * 获取接入回复
     */
    public function getreply()
    {
        $serviceId = request()->get('service_id', 0);  //客服ID

        $content = Kefu::getServiceReply($serviceId);

        if (empty($content)) {
            $content = '您好';
        }

        return $content;
    }

    /**
     * 上传图片
     */
    public function uploadImage(Request $request)
    {
        load_helper('common');

        $validator = Validator::make($request->all(), [
            'file' => 'required|image',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return [
                'code' => 100,//0表示成功，其它失败
                'msg' => $errors->first('file')
            ];
        }

        $path = 'images/upload/images/' . date('Ymd');
        $result = $this->upload($path, true);

        if ($result['error'] == 0) {
            return [
                'code' => 0,//0表示成功，其它失败
                'msg' => '上传成功',//提示信息 //一般上传失败后返回
                'data' => [
                    'src' => $result['url'],
                    'title' => ''
                ]
            ];
        }
    }

    /**
     * 处理链接信息api
     */
    public function transMessage()
    {
        //获取商品信息
        $message = request()->get('message', '');
        if (empty($message)) {
            return ['error' => 1, 'content' => "invalid params"];
        }
        return Kefu::format_msg($message);
    }

    /**
     * 获取IP
     */
    private function getServerIp()
    {
        if (request()->server()) {
            if (request()->server('SERVER_ADDR')) {
                $server_ip = request()->server('SERVER_ADDR');
            } else {
                $server_ip = request()->server('LOCAL_ADDR');
            }
        } else {
            $server_ip = getenv('SERVER_ADDR');
        }
        return $server_ip;
    }
}
