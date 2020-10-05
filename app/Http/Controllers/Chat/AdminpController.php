<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Libraries\Mysql;
use App\Models\ImService;
use App\Models\Kefu;
use App\Models\Users;
use App\Services\Merchant\MerchantCommonService;

class AdminpController extends Controller
{
    protected $config = [];
    private $user;
    protected $merchantCommonService;

    public function __construct(
        MerchantCommonService $merchantCommonService
    )
    {
        $this->merchantCommonService = $merchantCommonService;
    }

    /**
     * init
     */
    protected function initialize()
    {
        $this->config = config('chat');

        $config = cache('shop_config');
        $config = !is_null($config) ? $config : false;
        if ($config === false) {
            $config = app(\App\Services\Common\ConfigService::class)->getConfig();
        }

        $GLOBALS['_CFG'] = $config;
        $GLOBALS['db'] = app(Mysql::class);

        load_helper(['time', 'common', 'ecmoban']);
    }

    public function mobile()
    {
        $domain = url('/') . '/';

        $this->assign('domain', $domain);
        return $this->display('kefu.adminp_mobile');
    }

    /**
     * adminp.index
     */
    public function index()
    {
        // 校验用户身份
        $result = $this->userInfo();
        if ($result['code'] == 1) {
            return $result;
        }

        $result['code'] = 0;

        $service = [
            'id' => $this->user['service_id']
        ];

        /** 验证失败则跳转到登录页 */
        if (empty($this->user['service_id'])) {
            $result['code'] = 1;
            $result['message'] = '该账号没有客服权限';
            return $result;
        }

        /**
         * if ($service['chat_status'] == 1) {
         * $result['code'] = 1;
         * $result['message'] = '客服已登录';
         * return $result;
         * }
         */

        /** 聊天记录 */

        $messageList = Kefu::getChatLog($service);
//        if ( count($messageList) == 1 && empty($messageList['id']) ) {
//            $messageList = [];
//        }

        $result['message_list'] = $messageList;

        // 改变客服登录状态
        $id = $service['id'];  //获取客服ID
        $status = 1;  //获取客服ID
        $status = in_array($status, [0, 1]) ? $status : 0;

        $data['chat_status'] = $status;

        ImService::where('id', $id)->where('status', 1)->update($data);

        return $result;
    }

    /**
     * 获取初始信息
     */
    public function initInfo()
    {
        $result = $this->userInfo();
        if ($result['code'] == 1) {
            return $result;
        }

        if (empty($this->user['service_id'])) {
            $result['code'] = 1;
            $result['message'] = '没有权限';
            return $result;
        }

        $result = ['code' => 0, 'message' => '', 'data' => []];

        $listen_route = $this->config['listen_route'];

        if (empty($this->config['port'])) {
            $result['code'] = 1;
            $result['message'] = 'socket端口号未配置';
            return $result;
        }

        $result['data']['listen_route'] = $listen_route;
        $result['data']['port'] = $this->config['port'];

        // 店铺信息
        $storeId = $this->getStoreIdByServiceId($this->user['service_id']);

        $storeInfo = Kefu::getStoreInfo($storeId);
        $result['data']['avatar'] = $storeInfo['logo_thumb'];
        $result['data']['user_name'] = $storeInfo['shop_name'];

        // 客服信息
        $service = Kefu::getServiceById($this->user['service_id']);
        $result['data']['nick_name'] = $service['nick_name'];
        $result['data']['user_id'] = $this->user['service_id'];
        $result['data']['store_id'] = $storeId;

        //判断https
        $result['data']['is_ssl'] = is_ssl();

        return $result;
    }

    /**
     * 访客列表
     * 待接入用户
     */
    public function visit()
    {
        // 校验用户身份
        $result = $this->userInfo();
        if ($result['code'] == 1) {
            return $result;
        }

        $serviceId = $this->user['service_id'];  // 客服ID

        $storeId = $this->getStoreIdByServiceId($serviceId);

        /** 等待接入 */
        $waitMessageArr = Kefu::getWait($storeId);

        if (count($waitMessageArr['waitMessage']) === 1 && empty($waitMessageArr['waitMessage'][0]['id'])) {
            $waitMessageArr['waitMessage'] = [];
        }

        $result = [
            'code' => 0,
            'message_list' => $waitMessageArr['waitMessageDataList'],
            'visit_list' => $waitMessageArr['waitMessage'],
            'total' => $waitMessageArr['total']
        ];
        return $result;
    }

    /**
     * 聊天页面历史记录
     */
    public function chatList()
    {
        // 校验用户身份
        $result = $this->userInfo();
        if ($result['code'] == 1) {
            return $result;
        }

        $serviceId = $this->user['service_id'];
        $userId = request()->get('user_id', 0);
        $rootUrl = url('/');
        // 查询 店铺ID
        $storeId = $this->getStoreIdByServiceId($serviceId);

        $page = request()->get('page', 1);
        if ($page > 6) {
            return ['error' => 1, 'content' => '没有更多了'];
        }
        $default_size = 3; //默认显示条数
        $size = 10;
        $type = request()->get('type', 0);
        if ($type === 'default') {
            $page = 1;
            $size = $default_size;
        }

        $serArr = $this->getServiceIdByRuId($storeId);
        $serArr = $serArr ? implode(',', $serArr) : '';

        $serArr1 = " AND to_user_id IN (" . $serArr . ")";
        $serArr2 = " AND from_user_id IN (" . $serArr . ")";

        $sql = "SELECT id, IF(from_user_id = " . $userId . ", to_user_id, from_user_id) as service_id, message, user_type, from_user_id, to_user_id, dialog_id,
add_time, status FROM " . db_config('prefix') . "im_message WHERE ((from_user_id = " . $userId . $serArr1 . ") OR (to_user_id = " . $userId . $serArr2 . ")) AND to_user_id <> 0 ORDER BY add_time DESC, id DESC";
        $default = request()->get('default', 0);
        $start = ($page - 1) * $size;
        if ($default == 1) {
            $start += $default_size;
        }
        $sql .= ' limit ' . $start . ', ' . $size;

        $services = $GLOBALS['db']->getAll($sql);

        if ($services) {
            foreach ($services as $k => $v) {
                if ($v['user_type'] == 1) {
                    $sql = "SELECT s.nick_name, i.logo_thumb FROM " . db_config('prefix') . "im_service s"
                        . " LEFT JOIN " . db_config('prefix') . "admin_user u ON s.user_id = u.user_id"
                        . " LEFT JOIN " . db_config('prefix') . "seller_shopinfo i ON i.ru_id = u.ru_id"
                        . " WHERE s.id = " . $v['from_user_id'];
                    $nickName = $GLOBALS['db']->getRow($sql);
                    $services[$k]['name'] = $this->merchantCommonService->getShopName($storeId, 1);

                    //
                    if (strpos($nickName['logo_thumb'], 'http') !== false) {
                        $services[$k]['avatar'] = $nickName['logo_thumb'];
                    } else {
                        if (empty($nickName['logo_thumb'])) {
                            $services[$k]['avatar'] = asset('assets/chat/images/service.png');
                        } else {
                            $services[$k]['avatar'] = $nickName['logo_thumb'];
                        }
                    }
                } elseif ($v['user_type'] == 2) {

                    $users = Users::where('user_id', $v['from_user_id'])->first();
                    $users = $users ? $users->toArray() : [];

                    $services[$k]['name'] = $users['nick_name'] ?? '';
                    $users['user_picture'] = $users['user_picture'] ?? '';

                    if (empty($users['user_picture'])) {
                        $services[$k]['avatar'] = asset('assets/chat/images/avatar.png');
                    } else {
                        $services[$k]['avatar'] = get_image_path($users['user_picture']);
                    }
                }

                $services[$k]['message'] = htmlspecialchars_decode($v['message']);
                $services[$k]['add_time'] = local_date('Y-m-d H:i:s', $v['add_time']);
                $services[$k]['time'] = local_date('Y-m-d H:i:s', $v['add_time']);
                $services[$k]['id'] = $v['id'];
            }

            $did = $services[0]['dialog_id'];    // 会话ID
            $result['goods'] = null;
        } else {
            $did = 0;    // 会话ID
            $result['goods'] = null;
        }

        if (!empty($did)) {
            $sql = "SELECT g.goods_id, goods_name, goods_thumb, shop_price as goods_price FROM " . db_config('prefix') . "im_dialog d";
            $sql .= " LEFT JOIN " . db_config('prefix') . "goods g on d.goods_id = g.goods_id";
            $sql .= " WHERE d.id = " . $did;
            $goods = $GLOBALS['db']->getRow($sql);
            $goods['goods_price'] = price_format($goods['goods_price'], true);
            $goods['goods_thumb'] = get_image_path($goods['goods_thumb']);
            $goods['goods_url'] = rtrim($rootUrl, '/') . '/goods.php?id=' . $goods['goods_id'];
            if (empty($goods['goods_id'])) {
                $result['goods'] = null;
            } else {
                $result['goods'] = $goods;
            }
        }
        $result['code'] = 0;
        $result['message_list'] = $services;

        return $result;
    }

    /**
     * 将未读消息改为已读
     */
    public function changeMessageStatus()
    {
        $result = $this->userInfo();
        if ($result['code'] == 1) {
            return $result;
        }

        $serviceId = $this->user['service_id'];
        $customId = request()->get('id', 0);
        if (empty($serviceId)) {
            return ['error' => 1, 'msg' => '没有客服'];
        }
        Kefu::changeMessageStatus($serviceId, $customId);
    }

    /**
     * 获取商品信息
     */
    public function goodsInfo()
    {
        $rootUrl = url('/');

        // 校验用户身份
        $result = $this->userInfo();
        if ($result['code'] == 1) {
            return $result;
        }

        //获取商品信息
        $gid = request()->get('gid', 0);
        if ($gid == 0) {
            return ['error' => 1, 'content' => "invalid params"];
        }
        $data = Kefu::getGoods($gid);

        //
        $data['goods_url'] = rtrim($rootUrl, '/') . '/goods.php?id=' . $gid;
        $data['goods_thumb'] = get_image_path($data['goods_thumb']);
        $data['goods_price'] = price_format($data['shop_price'], true);
        unset($data['shop_price']);

        $result = [
            'code' => 0,
            'goods_info' => $data,
        ];

        return $result;
    }

    /**
     * 用户信息
     * 设置页面
     */
    public function serviceInfo()
    {
        // 校验用户身份
        $result = $this->userInfo();
        if ($result['code'] == 1) {
            return $result;
        }

        $result = ['code' => 0, 'message' => '', 'data' => ''];
        $id = $this->user['service_id'];   //客服ID

        // 客服信息
        $service = Kefu::getServiceById($id);

        // 没有找到客服信息
        if (empty($service)) {
            $result['code'] = 1;
            $result['message'] = '客服信息错误';

            return $result;
        }

        // 管理员信息
        $admin = Kefu::getAdmin($service['user_id']);

        // 没有找到管理员信息
        if (empty($admin)) {
            $result['code'] = 1;
            $result['message'] = '管理员信息错误';

            return $result;
        }
        // 查找店铺信息
        $store = Kefu::getStoreInfo($admin['ru_id']);


        //  返回数据
        $result['data'] = [
            'nick_name' => $service['nick_name'],
            'user_name' => $admin['user_name'],
            'service_avatar' => $store['logo_thumb']
        ];

        return $result;
    }

    /**
     * 根据客服ID 获取店铺ID
     * @param $serviceId
     */
    private function getStoreIdByServiceId($serviceId)
    {
        //
        $sql = "SELECT u.ru_id FROM " . db_config('prefix') . "im_service" . ' s'
            . " LEFT JOIN " . db_config('prefix') . "admin_user" . ' u ON s.user_id = u.user_id'
            . " WHERE s.id = '$serviceId'";

        $ruId = $GLOBALS['db']->getOne($sql); //客服列表

        return $ruId;
    }

    /**
     * 根据店铺ID 查找客服列表
     * 返回客服ID 列表
     * @param $storeId
     */
    private function getServiceIdByRuId($storeId)
    {
        //根据店铺ID查找客服列表
        $sql = "SELECT s.id FROM " . db_config('prefix') . "im_service" . ' s'
            . " LEFT JOIN " . db_config('prefix') . "admin_user" . ' u ON s.user_id = u.user_id'
            . " WHERE u.ru_id = '$storeId'";

        $serArr = $GLOBALS['db']->getCol($sql); //客服列表

        return $serArr;
    }

    /**
     * 退出接口
     */
    public function logout()
    {
        // 校验用户身份
        $result = $this->userInfo();
        if ($result['code'] == 1) {
            return $result;
        }

        $result = [
            'code' => 0,
            'message' => '退出成功'
        ];

        $id = $this->user['service_id'];   //客服ID

        if (empty($id)) {
            $result['code'] = 1;
            $result['message'] = '验证失败';

            return $result;
        }
        $this->logoutStatus();  // 客服退出操作

        return $result;
    }

    /**
     * 退出操作
     * 将客服状态改为退出
     */
    private function logoutStatus()
    {
        $id = $this->user['service_id'];   //客服ID

        $data['chat_status'] = 0;   // 改为退出状态

        ImService::where('id', $id)->where('status', 1)->update($data);
    }

    /**
     * 校验用户身份
     */
    private function userInfo()
    {
        $result = [
            'code' => 0
        ];
        $token = request()->header('token');   // 获取到token
        $data = $this->tokenDecode($token);

        if ($data) {
            // 检查用户信息
            $userId = base64_decode(hex2bin($data['id']));

            $expire = $data['expire'];
            $time = local_gettime();  // 现在时间

            if ($expire < $time || is_null($userId)) {
                // token过期
                $result['code'] = 1;
                $result['message'] = '用户登录已失效';
                $user = [
                    'service_id' => $userId
                ];
                $this->user = $user;
                $this->logoutStatus();  // 客服退出操作

                return $result;
            }

            // 验证hash
            $hash = $data['hash'];
            if (md5(md5($userId) . config('app.key')) != $hash) {
                $result['code'] = 1;
                $result['message'] = '验证未通过';
                return $result;
            }

            // 存储用户数据
            $user = [
                'service_id' => $userId
            ];

            $this->user = $user;
        } else {
            $result['code'] = 1;
            $result['message'] = '用户登录已失效';
            return $result;
        }
    }

    /**
     * @param $token
     * @return bool|mixed
     * 解密token
     */
    private function tokenDecode($token)
    {
        try {
            $data = json_decode(base64_decode($token), true);
            // 判断数据
            if (!is_array($data)) {
                return false;
            }

            return $data;
        } catch (\Exception $e) {
            return false;
        }
    }
}
