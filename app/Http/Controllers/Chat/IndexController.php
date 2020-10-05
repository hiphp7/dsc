<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\InitController;
use App\Models\Goods;
use App\Models\Users;
use App\Models\Users as User;
use App\Repositories\Common\DscRepository;
use App\Services\Cart\CartCommonService;
use App\Services\Comment\CommentService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\User\UserCommonService;
use Exception;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class IndexController extends InitController
{
    protected $config = [];
    protected $merchantCommonService;
    protected $commentService;
    protected $userCommonService;
    protected $cartCommonService;
    protected $dscRepository;

    public function __construct(
        MerchantCommonService $merchantCommonService,
        CommentService $commentService,
        UserCommonService $userCommonService,
        CartCommonService $cartCommonService,
        DscRepository $dscRepository
    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->commentService = $commentService;
        $this->userCommonService = $userCommonService;
        $this->cartCommonService = $cartCommonService;
        $this->dscRepository = $dscRepository;
    }

    /**
     * 构造函数
     */
    protected function initialize()
    {
        parent::initialize();
        $this->config = config('chat');
    }

    /**
     * workman.index
     * 客户进入聊天页面
     * 判断最近会话是否存在  没有则重新接入
     * 1. 直接进入页面  没有数据
     * 2. 商品页  或店铺页进入 保存ID
     * 3. 个人中心 历史记录页进入
     * @param Request $request
     * @return RedirectResponse|void
     * @throws Exception
     */
    public function index(Request $request)
    {
        $shop_id = (int)request()->get('ru_id', 0);
        $goods_id = (int)request()->get('goods_id', 0);
        $token_id = request()->get('t', '');
        $type = request()->get('type', '');

        // 一次性获取用户信息
        if ($token = Cache::get($token_id)) {
            Cache::forget($token_id);
        }

        /**
         * 跨设备登录
         */
        $user = $this->ecjiaLogin($request);
        if ($user !== false) {
            $user_id = collect($user)->get('user_id');
            $username = collect($user)->get('user_name');

            session(['user_id' => $user_id]);
            $GLOBALS['user']->set_session($username);
            $GLOBALS['user']->set_cookie($username);
            $this->userCommonService->updateUserInfo();
            $this->cartCommonService->recalculatePriceCart();
        }
        $uid = session('user_id', 0);


        if (empty($uid)) {
            try {
                $userInfo = decrypt($token);
                $uid = $userInfo['user_id'];

                session(['user_id' => $uid]);
                $GLOBALS['user']->set_session($userInfo['user_id']);
                $GLOBALS['user']->set_cookie($userInfo['user_id']);
                $this->userCommonService->updateUserInfo();
                $this->cartCommonService->recalculatePriceCart();
            } catch (DecryptException $e) {
                Log::error('DecryptException Error');
                return redirect()->back();
            }

            if (empty($uid)) {
                // 判断是否回到PC
                return redirect()->route('user');
            }
        }

        /**
         * 接受到用户ID
         */
        $user = User::where('user_id', $uid)->first();
        if (empty($user)) {
            // 判断是否回到PC
            return redirect()->route('home');
        }

        $user = $user->toArray();

        /**
         * 显示用户信息
         */
        if (empty($user['user_picture'])) {
            $user['avatar'] = asset('assets/chat/images/avatar.png');
        } else {
            $user['avatar'] = get_image_path($user['user_picture']);
        }
        $user['user_name'] = !empty($user['nick_name']) ? $user['nick_name'] : $user['user_name'];
        $this->assign('user', $user);

        /**
         * 显示商品信息
         */
        if ($goods_id) {
            $goods = Goods::select('goods_id', 'goods_name', 'shop_price', 'goods_thumb', 'user_id')
                ->where('goods_id', $goods_id)
                ->first();
            $goods = $goods ? $goods->toArray() : [];
            if (!empty($goods)) {
                $goods['goods_thumb'] = get_image_path($goods['goods_thumb']);
            }
            $this->assign('goods', $goods);
        }
        $shop_id = isset($goods['user_id']) ? $goods['user_id'] : intval($shop_id);

        /**
         * 查找会话
         */
        $dialog = DB::table('im_dialog')
            ->where('customer_id', $uid)
            ->where('services_id', '<>', '0')
            ->orderBy('start_time', 'desc')
            ->first();

        if (!empty($dialog) && $dialog->store_id == $shop_id) {
            $this->assign('status', $dialog->status);
            $this->assign('services_id', $dialog->services_id);
        } else {
            $this->assign('status', 0);
            $this->assign('services_id', 0);
        }

        /**
         * 获取店铺信息
         */
        $shopinfo = $this->merchantCommonService->getShopName($shop_id, 2);
        if (isset($shopinfo['rz_shopName']) && empty($shopinfo['rz_shopName'])) {
            return redirect('/');
        }

        $shopinfo['ru_id'] = $shop_id;
        $shopinfo['shop_name'] = $this->merchantCommonService->getShopName($shop_id, 1);
        $shopinfo['logo_thumb'] = $shopinfo['logo_thumb'] ?? '';
        $shopinfo['logo_thumb'] = get_image_path(str_replace('../', '', $shopinfo['logo_thumb']));
        $this->assign('shopinfo', $shopinfo);

        /**
         * socket配置
         */
        if (empty($this->config['listen_route'])) {
            $listen_route = $this->getServerIp();
        } else {
            $listen_route = $this->config['listen_route'];
        }

        if (empty($this->config['port'])) {
            return show_message('socket端口号未配置');
        }

        $this->assign('listen_route', $listen_route); //监听路由
        $this->assign('port', $this->config['port']); //监听端口

        //将离线消息状态改变 当前商家
        $sql = "UPDATE " . db_config('prefix') . "im_message m "
            . " LEFT JOIN " . db_config('prefix') . "im_dialog d ON d.id = m.dialog_id"
            . " SET m.status = 0"
            . " WHERE m.status = 1 AND m.to_user_id = " . $uid
            . " AND d.store_id = " . $shop_id;
        DB::update($sql);
        // 店铺信息
        if ($shop_id > 0) {
            // 非自营商家信息
            $sql = "SELECT * FROM {pre}merchants_shop_information as a JOIN {pre}seller_shopinfo as b ON a.user_id = b.ru_id WHERE user_id = '" . intval($shop_id) . "'";
            $data = $this->db->getRow($sql);
            if ($data === false) {
                return redirect('/');
            }

            $sql = "SELECT count(user_id) as a FROM {pre}collect_store WHERE ru_id = '" . intval($data['user_id']) . "'";
            $follow = $this->db->getOne($sql);

            $info = $this->shopdata($data);
            $info['count_gaze'] = intval($follow);
        } else {
            // 查询自营店信息
            $sql = "SELECT shop_address, kf_tel FROM {pre}seller_shopinfo WHERE ru_id = 0";
            $data = $this->db->getRow($sql);
            $info = [
                'shop_name' => $shopinfo['shop_name'],
                'shop_desc' => $shopinfo['shop_name'],
                'shop_start' => '',
                'shop_address' => $data['shop_address'],
                'shop_tel' => $data['kf_tel']
            ];
        }

        $this->assign('shop_info', $info);

        // app 客服
        if (!empty($type) && $type == 'app') {
            return ['user' => $user, 'shop_info' => $info, 'shopinfo' => $shopinfo];
        }

        // 订单查询
        // $orderList = $this->orderListByUid($uid);
        // $this->assign('order_list', $orderList);

        $this->assign('title', '在线客服 - ' . $shopinfo['shop_name']);

        return $this->display('kefu.' . (is_mobile_device() ? 'mobile' : 'desktop'));
    }

    /**
     * 根据用户信息查询订单列表
     */
    public function orderList()
    {
        if ($this->checkReferer() === false) {
            return response()->json(['code' => 1, 'msg' => 'referer error']);
        }

        $result = ['code' => 0, 'msg' => '', 'order_list' => ''];

        $ruId = (int)request()->get('uid', 0);
        $uid = session('user_id', 0);
        $start = (int)request()->get('start', 0);
        $num = (int)request()->get('num', 10);

        if (empty($uid)) {
            $result['code'] = 1;
            $result['msg'] = '参数错误';
            return $result;
        }
        $sql = 'SELECT oi.order_sn, (oi.goods_amount + oi.shipping_fee + oi.insure_fee + oi.pay_fee + oi.pack_fee + oi.card_fee + oi.tax - oi.discount) as order_amount, oi.add_time as order_time, g.goods_id, g.goods_name, g.goods_thumb FROM {pre}order_info oi';
        $sql .= " LEFT JOIN {pre}order_goods og ON oi.order_id = og.order_id";
        $sql .= " LEFT JOIN {pre}goods g ON g.goods_id = og.goods_id";
        $sql .= ' WHERE oi.user_id = ' . $uid . ' AND g.user_id = ' . $ruId . ' ORDER BY oi.order_id DESC LIMIT ' . $start . ', ' . $num;

        $goodsList = $this->db->getAll($sql);
        foreach ($goodsList as $k => $v) {
            $goodsList[$k]['goods_thumb'] = get_image_path($v['goods_thumb']);
            $goodsList[$k]['order_amount'] = price_format($v['order_amount'], true);
            $goodsList[$k]['order_time'] = local_date('Y年m月d日', $v['order_time']);
            $goodsList[$k]['goods_url'] = $this->dscRepository->dscUrl('goods.php?id=' . $v['goods_id']);
        }
        $result['order_list'] = $goodsList;

        return $result;
    }

    /**
     * 组合商品信息
     * @param array $data
     * @return mixed
     */
    private function shopdata($data = [])
    {
        $user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
        if (empty($user_id)) {
            return false;
        }
        $shop_expiredatestart = strtotime($data['shop_expireDateStart']);
        $info['shop_id'] = $data['shop_id'];
        $info['ru_id'] = $data['user_id'];
        $info['shop_logo'] = get_image_path(str_replace('../', '', $data['logo_thumb']));
        $info['street_thumb'] = get_image_path(str_replace('../', '', $data['street_thumb']));
        $info['shop_name'] = $this->merchantCommonService->getShopName($data['user_id'], 1);
        $info['shop_desc'] = $data['shop_name'];
        $info['shop_start'] = local_date('Y年m月d日', $shop_expiredatestart);
        $info['shop_address'] = $data['shop_address'];
        $info['shop_flash'] = get_image_path($data['street_thumb']);
        $info['shop_tel'] = $data['kf_tel'];
        $info['is_im'] = $data['is_IM'];
        $info['self_run'] = $data['self_run'];
        $info['meiqia'] = $data['meiqia'];
        $info['kf_appkey'] = $data['kf_appkey'];

        return $info;
    }

    /**
     * 个人中心聊天记录
     */
    public function chatList()
    {
        if ($this->checkReferer() === false) {
            return response()->json(['code' => 1, 'msg' => 'referer error']);
        }

        $uid = session('user_id', 0);
        if (empty($uid)) {
            //0表示成功，其它失败
            return ['code' => 100, 'msg' => '请先登录'];
        }

        /** 找出所有客服ID */
        $sql = "SELECT d.id as dialog_id, s.id as service_id, u.user_id as admin_id, u.ru_id, i.logo_thumb, i.shop_name FROM " . db_config('prefix') . "im_dialog d ";
        $sql .= " LEFT JOIN " . db_config('prefix') . "im_service s ON s.id = d.services_id";
        $sql .= " LEFT JOIN " . db_config('prefix') . "admin_user u ON u.user_id = s.user_id";
        $sql .= " LEFT JOIN " . db_config('prefix') . "seller_shopinfo i ON i.ru_id = u.ru_id";
        $sql .= " WHERE  d.customer_id = " . $uid . " GROUP BY services_id ";
        $serId = $this->db->getAll($sql);
        //
        $store = [];

        foreach ($serId as $k => $v) {
            if (is_null($v['ru_id'])) {
                continue;
            }
            $store[$v['ru_id']][$v['service_id']] = $v;
            $store[$v['ru_id']]['logo_thumb'] = get_image_path($v['logo_thumb']);
            $store[$v['ru_id']]['shop_name'] = $v['shop_name'];
        }
        /** 根据店铺查询消息记录 */
        $storeMessage = [];

        foreach ($store as $k => $v) {
            $storeMessage[$k]['ru_id'] = $k;
            $storeMessage[$k]['thumb'] = $v['logo_thumb'];
            $storeMessage[$k]['shop_name'] = $v['shop_name'];
            unset($v['logo_thumb']);
            unset($v['shop_name']);
            $serviceId = implode(',', array_keys($v));  //所有客服ID
            if (empty($serviceId)) {
                continue;
            }    ///有问题
            // 搜索消息记录
            $sql = "SELECT count(*) FROM " . db_config('prefix') . "im_message WHERE (from_user_id in (" . $serviceId . ")  AND to_user_id =" . $uid . ") AND status = 1";
            $storeMessage[$k]['count'] = $this->db->getOne($sql);

            $sql = "SELECT message, add_time, from_user_id, to_user_id, user_type FROM " . db_config('prefix') . "im_message WHERE (from_user_id in (" . $serviceId . ")  AND to_user_id =" . $uid . ") OR (to_user_id in (" . $serviceId . ")  AND from_user_id =" . $uid . ")  ORDER BY add_time DESC limit 1";
            $res = $this->db->getRow($sql);

            $storeMessage[$k]['message'] = htmlspecialchars_decode($res['message']);
            $storeMessage[$k]['add_time'] = local_date('Y-m-d H:i:s', $res['add_time']);
            $storeMessage[$k]['service_id'] = ($res['user_type'] == 2) ? $res['to_user_id'] : $res['from_user_id'];
        }

        if (request()->isMethod('post')) {
            return $storeMessage;
        } else {
            $this->assign('message', $storeMessage);
            return $this->display('kefu.chatlist');
        }
    }

    /**
     * 客户页面聊天历史记录
     */
    public function singleChatList($store_id = 0)
    {
        if ($this->checkReferer() === false) {
            return response()->json(['code' => 1, 'msg' => 'referer error']);
        }

        $uid = session('user_id', 0);

        $store_id = (int)request()->get('store_id', $store_id);
        $user_type = (int)request()->get('user_type', 1); // 参数有两个：1为客服，2为客户。
        $page = (int)request()->get('page', 1);

        $default_size = 5; //默认显示条数
        $size = 10;
        $type = request()->get('type', 0);//
        if ($type === 'default') {
            $page = 1;
            $size = $default_size;
        }

        $default = (int)request()->get('default', 0);
        $start = ($page - 1) * $size;
        if ($default == 1) {
            $start += $default_size;
        }
        if ($page > 1) {
            $start -= $size;
        }

        // 根据店铺ID和当前会话显示消息列表
        $sql = 'SELECT * FROM ' . db_config('prefix') . 'im_dialog WHERE store_id = ' . intval($store_id);
        if ($user_type == 2) {
            $sql .= ' AND customer_id = ' . intval($uid);
        } else {
            $sql .= ' AND services_id = ' . intval($uid);
        }

        $sql .= ' order by id desc';

        $dialog = $this->db->getCol($sql);

        $dialog = is_array($dialog) ? $dialog : [0];

        $dialogSql = " dialog_id in (" . implode(',', $dialog) . ") ";

        $sql = "SELECT id, IF(from_user_id = " . $uid . ", to_user_id, from_user_id) as service_id, message, user_type, from_user_id, to_user_id, add_time, status
 FROM " . db_config('prefix') . "im_message WHERE " . $dialogSql . " ORDER BY add_time DESC, id DESC";

        $sql .= ' limit ' . $start . ', ' . $size;
        $services = $this->db->getAll($sql);

        if (empty($services) && $page > 1) {
            return ['error' => 1, 'content' => '没有历史记录了'];
        }

        foreach ($services as $k => $v) {
            if ($v['user_type'] == 1) {
                $sql = "SELECT s.nick_name, i.logo_thumb FROM " . db_config('prefix') . "im_service s"
                    . " LEFT JOIN " . db_config('prefix') . "admin_user u ON s.user_id = u.user_id"
                    . " LEFT JOIN " . db_config('prefix') . "seller_shopinfo i ON i.ru_id = u.ru_id"
                    . " WHERE s.id = " . $v['from_user_id'];
                $nickName = $this->db->getRow($sql);
                $services[$k]['name'] = $this->merchantCommonService->getShopName($store_id, 1);
                $services[$k]['avatar'] = $this->formatImage($nickName['logo_thumb']);
            } elseif ($v['user_type'] == 2) {
                $users = Users::where('user_id', $v['from_user_id'])->first();
                $users = $users ? $users->toArray() : [];

                $services[$k]['name'] = $users['nick_name'];
                if (empty($users['user_picture'])) {
                    $services[$k]['avatar'] = asset('assets/chat/images/avatar.png');
                } else {
                    if (strpos($users['user_picture'], 'http') !== false) {
                        $services[$k]['avatar'] = $users['user_picture'];
                    } else {
                        $services[$k]['avatar'] = $this->dscRepository->dscUrl($users['user_picture']);
                    }
                }
            }

            $services[$k]['message'] = htmlspecialchars_decode($v['message']);
            $services[$k]['add_time'] = local_date('Y-m-d H:i:s', $v['add_time']);
            $services[$k]['time'] = local_date('Y-m-d H:i:s', $v['add_time']);
            $services[$k]['id'] = $v['id'];
        }

        return $services;
    }

    /**
     * 根据店铺ID 查找客服列表
     * @param $store_id
     * @return mixed
     */
    private function getServiceIdByRuId($store_id)
    {
        //根据店铺ID查找客服列表
        $sql = "SELECT s.id FROM " . db_config('prefix') . "im_service" . ' s'
            . " LEFT JOIN " . db_config('prefix') . "admin_user" . ' u ON s.user_id = u.user_id'
            . " WHERE u.ru_id = {$store_id}";

        $serArr = $this->db->getCol($sql); //客服列表

        return $serArr;
    }

    /**
     * 查找最新一次会话的商品信息
     * 将未读消息 改为 已读
     */
    public function serviceChatData()
    {
        if ($this->checkReferer() === false) {
            return response()->json(['code' => 1, 'msg' => 'referer error']);
        }

        $uid = session('user_id', 0); // TODO TEST : request()->get('uid');
        $serviceId = (int)request()->get("id", 0);
        $goodsId = (int)request()->get("goods_id", 0);

        // 查出 商家ID
        $sql = "SELECT u.ru_id FROM " . db_config('prefix') . "admin_user u LEFT JOIN " . db_config('prefix') . "im_service s ON u.user_id = s.user_id WHERE s.id = " . $serviceId;
        $res = $this->db->getRow($sql);
        if (empty($res) || empty($res['ru_id'])) {
            $res['ru_id'] = 0;
        }

        request()->offsetSet('store_id', $res['ru_id']);

        $services = $this->singleChatList($res['ru_id']);

        //  未读数量
        $sql = "UPDATE " . db_config('prefix') . "im_message m "
            . " LEFT JOIN " . db_config('prefix') . "im_dialog d ON d.id = m.dialog_id"
            . " SET m.status = 0"
            . " WHERE m.status = 1 AND m.to_user_id = " . $uid
            . " AND d.store_id = " . $res['ru_id'];
        $this->db->query($sql);
        //
        if ($serviceId == 0) {
            $sql = "SELECT goods_thumb, goods_sn, goods_name, goods_id FROM " . db_config('prefix') . "goods  WHERE goods_id = " . $goodsId;
            $goods = $this->db->getRow($sql);
        } else {
            $sql = "SELECT g.goods_thumb, g.goods_sn, g.goods_name, g.goods_id FROM " . db_config('prefix') . "im_dialog d";
            $sql .= " LEFT JOIN " . db_config('prefix') . "goods g ON d.goods_id = g.goods_id";
            $sql .= " WHERE d.customer_id = {$uid} AND d.services_id = {$serviceId}";
            $sql .= " ORDER BY d.id DESC LIMIT 1";
            $goods = $this->db->getRow($sql);
        }

        if (!empty($goods)) {
            $goods['goods_thumb'] = get_image_path($goods['goods_thumb']);
            $goods['goods_url'] = $this->dscRepository->dscUrl('goods.php?id=' . $goods['goods_id']);
        }

        return ['goods' => $goods, 'chat' => $services];
    }

    /**
     * 获取IP
     */
    public function getServerIp()
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

    /**
     * 过滤图片
     * 处理商家图片
     */
    public function formatImage($pic = '')
    {
        return asset('assets/chat/images/service.png');
    }

    /**
     * 发送图片给客服
     * @param Request $request
     * @return array
     */
    public function sendImage(Request $request)
    {
        $uid = session('user_id', 0);
        if (empty($uid)) {
            return [
                'code' => 100,//0表示成功，其它失败
                'msg' => '请先登录',
            ];
        }

        // 区别PC与mobile不同的表单名称
        $input = $request->has('file') ? 'file' : 'myfile';

        $validator = Validator::make($request->all(), [
            $input => 'required|image',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return [
                'code' => 100,//0表示成功，其它失败
                'msg' => $errors->first('myfile')
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
        } else {
            return [
                'code' => 100,//0表示成功，其它失败
                'msg' => '上次失败'
            ];
        }
    }
}
