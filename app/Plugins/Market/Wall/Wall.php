<?php

namespace App\Plugins\Market\Wall;

use App\Http\Controllers\Wechat\PluginController;
use App\Models\WechatMarketing;
use App\Models\WechatPrize;
use App\Models\WechatWallMsg;
use App\Models\WechatWallUser;
use Endroid\QrCode\QrCode;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Class Wall
 * @package App\Plugins\Market\Wall
 */
class Wall extends PluginController
{
    private $weObj = null;
    private $wechat_id = 0;
    private $wechat_ru_id = 0;
    private $market_id = 0;
    private $marketing_type = 'wall';

    protected $config = [];

    /**
     * 构造函数
     */
    public function __construct($config = [])
    {
        parent::__construct();

        $this->plugin_name = $this->marketing_type = strtolower(basename(__FILE__, '.php'));
        $this->config = $config;
        $this->wechat_ru_id = isset($this->config['wechat_ru_id']) ? $this->config['wechat_ru_id'] : 0;

        $this->wechat_id = $this->wechatService->getWechatId($this->wechat_ru_id);

        $this->config['plugin_path'] = 'Market';

        $this->market_id = request()->get('wall_id', 0);
        if (empty($this->market_id)) {
            return redirect()->route('/');
        }

        $this->plugin_assign('config', $this->config);
    }

    /**
     * 微信交流墙
     */
    public function actionWallMsg()
    {
        //活动内容
        $wall = WechatMarketing::select('id', 'name', 'logo', 'background', 'starttime', 'endtime', 'config', 'description', 'support')->where(['id' => $this->market_id, 'marketing_type' => 'wall', 'wechat_id' => $this->wechat_id])
            ->first();
        $wall = $wall ? $wall->toArray() : [];

        if ($wall) {
            $wall['status'] = $this->wechatService->get_status($wall['starttime'], $wall['endtime']); // 活动状态
            $wall['logo'] = $this->wechatHelperService->get_wechat_image_path($wall['logo']);
            $wall['background'] = $this->wechatHelperService->get_wechat_image_path($wall['background']);
        }
        $list = [];
        if ($wall && $wall['status'] == 1) {
            //留言

            $model = WechatWallMsg::from('wechat_wall_msg as m')->where('m.status', 1);

            $condition = [
                'wall_id' => $this->market_id,
                'wechat_id' => $this->wechat_id,
            ];

            $model = $model->leftJoin('wechat_wall_user as u', 'u.id', '=', 'm.user_id')
                ->where('m.wechat_id', $condition['wechat_id'])
                ->where('m.wall_id', $condition['wall_id']);

            $num = $model->count();

            $list = $model->limit(10)
                ->orderBy('m.addtime', 'DESC')
                ->get();
            $list = $list ? $list->toArray() : [];

            if ($list) {
                usort($list, function ($a, $b) {
                    if ($a['addtime'] == $b['addtime']) {
                        return 0;
                    }
                    return $a['addtime'] > $b['addtime'] ? 1 : -1;
                });
                foreach ($list as $k => $v) {
                    $list[$k]['addtime'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $v['addtime']);
                }
            }
        }

        $this->plugin_assign('msg_count', isset($num) ? $num : 0);
        $this->plugin_assign('wall', $wall);
        $this->plugin_assign('list', $list);
        return $this->show_display('wallmsg', $this->_data);
    }

    /**
     * 微信头像墙
     */
    public function actionWallUser()
    {
        if (request()->isMethod('post')) {
            $result['error'] = 0;
            $result['errMsg'] = '';

            $wall_id = request()->input('wall_id');
            if (empty($wall_id)) {
                $result['error'] = 1;
                return response()->json($result);
            }

            $url = route('wechat/market_show', ['type' => 'wall', 'function' => 'wall_user_wechat', 'wall_id' => $wall_id, 'wechat_ru_id' => $this->wechat_ru_id]);

            // 生成的文件位置
            $path = storage_public('data/attached/wall/');
            $water_logo = public_path('assets/mobile/img/shop_app_icon.png');
            // 输出二维码路径
            $qrcode = $path . 'wall_qrcode_' . $wall_id . '.png';

            if (!is_dir($path)) {
                @mkdir($path);
            }

            if (!file_exists($qrcode)) {
                $qrCode = new QrCode($url);

                $qrCode->setSize(357);
                $qrCode->setMargin(15);
                $qrCode->setLogoPath($water_logo); // 默认居中
                $qrCode->setLogoWidth(60);
                $qrCode->writeFile($qrcode); // 保存二维码
            }

            // 同步镜像上传到OSS
            $this->ossMirror('data/attached/wall/' . basename($qrcode), true);

            $qrcode_url = $this->wechatHelperService->get_wechat_image_path('data/attached/wall/' . basename($qrcode)) . '?t=' . time();

            $result['qr_code'] = $qrcode_url;
            return response()->json($result);
        }
        //活动内容
        $wall = WechatMarketing::where(['id' => $this->market_id, 'marketing_type' => 'wall', 'wechat_id' => $this->wechat_id])
            ->first();
        $wall = $wall ? $wall->toArray() : [];

        if ($wall) {
            $wall['status'] = $this->wechatService->get_status($wall['starttime'], $wall['endtime']); // 活动状态
            $wall['logo'] = $this->wechatHelperService->get_wechat_image_path($wall['logo']);
            $wall['background'] = $this->wechatHelperService->get_wechat_image_path($wall['background']);
        }

        //用户
        $model = WechatWallUser::where(['wall_id' => $this->market_id, 'status' => 1, 'wechat_id' => $this->wechat_id]);

        $list = $model->select('nickname', 'headimg', 'wechatname', 'headimgurl')
            ->orderBy('addtime', 'desc')
            ->get();
        $list = $list ? $list->toArray() : [];

        if ($list) {
            foreach ($list as $key => $val) {
                $val['headimg'] = isset($val['headimg']) ? $this->replace_wechatWallUser_image($val['headimg']) : '';
                $val['headimgurl'] = isset($val['headimgurl']) ? $this->replace_wechatWallUser_image($val['headimgurl']) : '';
            }
        }

        // 上墙用户
        $total = $model->count();
        $this->plugin_assign('total', $total);

        $this->plugin_assign('wall', $wall);
        $this->plugin_assign('list', $list);
        return $this->show_display('walluser', $this->_data);
    }

    /**
     * 抽奖页面
     */
    public function actionWallPrize()
    {
        //活动内容
        $wall = WechatMarketing::where(['id' => $this->market_id, 'marketing_type' => 'wall', 'wechat_id' => $this->wechat_id])
            ->first();
        $wall = $wall ? $wall->toArray() : [];

        if ($wall) {
            $wall['config'] = isset($wall['config']) ? unserialize($wall['config']) : [];
            $wall['logo'] = $this->wechatHelperService->get_wechat_image_path($wall['logo']);
            $wall['background'] = $this->wechatHelperService->get_wechat_image_path($wall['background']);
        }

        //中奖的用户
        $prefix = config('database.connections.mysql.prefix');
        $sql = "SELECT u.nickname, u.headimg, u.id, u.wechatname, u.headimgurl FROM " . $prefix . "wechat_prize p LEFT JOIN " . $prefix . "wechat_wall_user u ON u.openid = p.openid WHERE u.wall_id = " . $this->market_id . " AND u.status = 1 AND p.wechat_id = " . $this->wechat_id . " AND u.openid in (SELECT openid FROM " . $prefix . "wechat_prize WHERE market_id = " . $this->market_id . " AND wechat_id = " . $this->wechat_id . " AND activity_type = 'wall') GROUP BY u.id ORDER BY p.dateline ASC";
        $rs = DB::select($sql);

        $list = [];
        if ($rs) {
            foreach ($rs as $k => $v) {
                $v = $v ? collect($v)->toArray() : [];
                $list[$k + 1] = $v;
            }
        }
        $prize_user = count($rs);
        //参与人数
        $total = WechatWallUser::where(['status' => 1, 'wechat_id' => $this->wechat_id])->count();
        $total = $total - $prize_user;

        $this->plugin_assign('total', $total);
        // $this->plugin_assign('list', $list);
        $this->plugin_assign('wall', $wall);
        return $this->show_display('wallprize', $this->_data);
    }

    /**
     * 获取未中奖用户
     */
    public function actionNoPrize()
    {
        if (request()->isMethod('post')) {
            $result['errCode'] = 0;
            $result['errMsg'] = '';

            $wall_id = request()->input('wall_id');
            if (empty($wall_id)) {
                $result['errCode'] = 1;
                return response()->json($result);
            }
            $wall = WechatMarketing::select('id', 'name', 'starttime', 'endtime', 'config')->where(['id' => $wall_id, 'marketing_type' => 'wall', 'wechat_id' => $this->wechat_id])
                ->first();
            $wall = $wall ? $wall->toArray() : [];

            if (empty($wall)) {
                $result['errCode'] = 2;
                $result['errMsg'] = lang('wechat.activity_is_empty');
                return response()->json($result);
            }
            $wall['status'] = $this->wechatService->get_status($wall['starttime'], $wall['endtime']); // 活动状态
            if ($wall['status'] != 1) {
                $result['errCode'] = 2;
                $result['errMsg'] = lang('wechat.activity_not_start');
                return response()->json($result);
            }
            //没中奖的用户
            $prefix = config('database.connections.mysql.prefix');
            $sql = "SELECT nickname, headimg, id, wechatname, headimgurl FROM " . $prefix . "wechat_wall_user WHERE wall_id = " . $wall_id . " AND status = 1 AND openid not in (SELECT openid FROM " . $prefix . "wechat_prize WHERE market_id = " . $wall_id . " AND wechat_id = " . $this->wechat_id . " AND activity_type = 'wall') ORDER BY addtime DESC";
            $no_prize = DB::select($sql);

            if (empty($no_prize)) {
                $result['errCode'] = 2;
                $result['errMsg'] = lang('wechat.no_data');
                return response()->json($result);
            }

            foreach ($no_prize as $k => $v) {
                $no_prize[$k] = $v ? collect($v)->toArray() : [];
            }

            $result['data'] = $no_prize;
            return response()->json($result);
        }
    }

    /**
     * 抽奖的动作
     */
    public function actionStartDraw()
    {
        if (request()->isMethod('post')) {
            $result['errCode'] = 0;
            $result['errMsg'] = '';

            $wall_id = request()->input('wall_id');
            if (empty($wall_id)) {
                $result['errCode'] = 1;
                return response()->json($result);
            }
            $wall = WechatMarketing::select('id', 'name', 'starttime', 'endtime', 'config')->where(['id' => $wall_id, 'marketing_type' => 'wall', 'wechat_id' => $this->wechat_id])
                ->first();
            $wall = $wall ? $wall->toArray() : [];

            if (empty($wall)) {
                $result['errCode'] = 2;
                $result['errMsg'] = lang('wechat.activity_is_empty');
                return response()->json($result);
            }
            $wall['status'] = $this->wechatService->get_status($wall['starttime'], $wall['endtime']); // 活动状态
            if ($wall['status'] != 1) {
                $result['errCode'] = 2;
                $result['errMsg'] = lang('wechat.activity_not_start');
                return response()->json($result);
            }
            $prefix = config('database.connections.mysql.prefix');
            $sql = "SELECT u.nickname, u.headimg, u.openid, u.id, u.wechatname, u.headimgurl FROM " . $prefix . "wechat_wall_user u LEFT JOIN " . $prefix . "wechat_prize p ON u.openid = p.openid WHERE u.wall_id = '$wall_id' AND u.status = 1 AND u.wechat_id = '$this->wechat_id' AND u.openid not in (SELECT openid FROM " . $prefix . "wechat_prize WHERE market_id = '$wall_id' AND wechat_id = '$this->wechat_id' AND activity_type = 'wall') GROUP by u.openid ORDER BY u.addtime DESC";
            $list = DB::select($sql);

            if ($list) {
                foreach ($list as $k => $v) {
                    $list[$k] = $v ? collect($v)->toArray() : [];
                }
                //随机一个中奖人
                $key = mt_rand(0, count($list) - 1);
                $rs = isset($list[$key]) ? $list[$key] : $list[0];

                //存储中奖用户
                if ($rs) {
                    $data['wechat_id'] = $this->wechat_id;
                    $data['openid'] = $rs['openid'];
                    $data['issue_status'] = 0;
                    $data['dateline'] = $this->timeRepository->getGmTime();
                    $data['prize_type'] = 1;
                    $data['prize_name'] = lang('wechat.wall_prize_name');
                    $data['activity_type'] = 'wall';
                    $data['market_id'] = $wall_id;
                    WechatPrize::create($data);
                }

                $result['data'] = $rs;
                return response()->json($result);
            }
        }
        $result['errCode'] = 2;
        $result['errMsg'] = lang('wechat.no_data');
        return response()->json($result);
    }

    /**
     * 重置抽奖
     */
    public function actionResetDraw()
    {
        if (request()->isMethod('post')) {
            $result['errCode'] = 0;
            $result['errMsg'] = '';

            $wall_id = request()->input('wall_id');
            if (empty($wall_id)) {
                $result['errCode'] = 1;
                return response()->json($result);
            }
            $wall = WechatMarketing::select('id', 'name', 'starttime', 'endtime', 'config')->where(['id' => $wall_id, 'marketing_type' => 'wall', 'wechat_id' => $this->wechat_id])
                ->first();
            $wall = $wall ? $wall->toArray() : [];

            if (empty($wall)) {
                $result['errCode'] = 2;
                $result['errMsg'] = lang('wechat.activity_is_empty');
                return response()->json($result);
            }
            $wall['status'] = $this->wechatService->get_status($wall['starttime'], $wall['endtime']); // 活动状态
            if ($wall['status'] != 1) {
                $result['errCode'] = 2;
                $result['errMsg'] = lang('wechat.activity_not_start');
                return response()->json($result);
            }
            //删除中奖的用户
            WechatPrize::where(['market_id' => $wall_id, 'activity_type' => 'wall', 'wechat_id' => $this->wechat_id])->delete();
            //不显示在中奖池
            //WechatPrize::where(array('market_id' => $wall_id, 'activity_type' => 'wall', 'wechat_id' => $this->wechat_id))->update(['prize_type' => 0]);

            ///参与人数 = 总人数 - 中奖用户
            //中奖的用户
            $prefix = config('database.connections.mysql.prefix');
            $sql = "SELECT count(*) as num FROM " . $prefix . "wechat_prize p LEFT JOIN " . $prefix . "wechat_wall_user u ON u.openid = p.openid WHERE u.wall_id = " . $this->market_id . " AND u.status = 1 AND p.wechat_id = " . $this->wechat_id . " AND u.openid in (SELECT openid FROM " . $prefix . "wechat_prize WHERE market_id = " . $this->market_id . " AND wechat_id = " . $this->wechat_id . " AND activity_type = 'wall') GROUP BY u.id ORDER BY p.dateline ASC";
            $prize_num = DB::select($sql);
            $prize_num = !empty($prize_num) ? get_object_vars($prize_num[0]) : 0;
            $num = $prize_num['num'] ?? 0;

            $total = WechatWallUser::where(['status' => 1, 'wechat_id' => $this->wechat_id])->count();
            $rs['total_num'] = empty($total) ? 0 : $total - $num;
            $result['data'] = $rs;
            return response()->json($result);
        }
        $result['errCode'] = 2;
        $result['errMsg'] = lang('wechat.illegal_request');
        return response()->json($result);
    }

    /**
     * 微信端抽奖用户申请
     */
    public function actionWallUserWechat()
    {
        $openid = $this->get_openid($this->wechat_ru_id);
        if (!empty($openid)) {
            if (request()->isMethod('post')) {
                $wall_id = request()->input('wall_id', '');

                if (empty($wall_id)) {
                    return response()->json(['error' => 1, 'msg' => lang('wechat.activity_is_empty')]);
                }
                $data['nickname'] = request()->input('nickname', '');
                if (empty($data['nickname'])) {
                    return response()->json(['error' => 1, 'msg' => lang('wechat.please_fill_name')]);
                }
                $nickname = WechatWallUser::where(['nickname' => $data['nickname'], 'wall_id' => $wall_id, 'wechat_id' => $this->wechat_id])->value('nickname');
                if (!empty($nickname)) {
                    return response()->json(['error' => 1, 'msg' => lang('wechat.the_name_hasbeen_used')]);
                }

                $data['sign_number'] = request()->input('sign_number', '');

                $data['headimg'] = request()->input('headimg');
                $data['openid'] = $openid;

                $wechat_user = session()->get('wechat_user');
                $data['sex'] = $wechat_user['sex'] ?? 0;
                $data['wechatname'] = $wechat_user['nickname'] ?? '';
                $data['headimgurl'] = $wechat_user['headimgurl'] ?? '';

                $data['wall_id'] = $wall_id;
                $data['wechat_id'] = $this->wechat_id;
                $data['addtime'] = $this->timeRepository->getGmTime();

                $wall_user = WechatWallUser::where(['wall_id' => $wall_id, 'openid' => $openid, 'wechat_id' => $this->wechat_id])->first();
                $wall_user = $wall_user ? $wall_user->toArray() : [];

                if (empty($wall_user)) {
                    WechatWallUser::create($data);
                }
                // 进入聊天室
                return response()->json(['error' => 0, 'msg' => lang('wechat.success_to_enter'), 'url' => route('wechat/market_show', ['type' => 'wall', 'function' => 'wall_msg_wechat', 'wall_id' => $wall_id, 'wechat_ru_id' => $this->wechat_ru_id])]);
            }
            // 显示页面
            $wall_id = $this->market_id;

            //更改过头像跳到聊天页面
            $wall_user = WechatWallUser::where(['wall_id' => $wall_id, 'openid' => $openid, 'wechat_id' => $this->wechat_id])->first();
            $wall_user = $wall_user ? $wall_user->toArray() : [];

            if (empty($wall_user)) {
                $wechat_user = $this->wechatUserService->getWechatUserByOpenid($openid);
                session(['wechat_user' => $wechat_user]);
                $wall_user = [
                    'headimgurl' => $wechat_user['headimgurl'] ?? '',
                    'nickname' => $wechat_user['nickname'] ?? '',
                ];
            } else {
                // 进入聊天室
                return redirect()->route('wechat/market_show', ['type' => 'wall', 'function' => 'wall_msg_wechat', 'wall_id' => $wall_id, 'wechat_ru_id' => $this->wechat_ru_id]);
            }

            $this->plugin_assign('user', $wall_user);
            $this->plugin_assign('wall_id', $wall_id);
            return $this->show_display('walluserwechat', $this->_data);
        }
    }

    /**
     * 微信端留言页面
     */
    public function actionWallMsgWechat()
    {
        $openid = $this->get_openid($this->wechat_ru_id);
        if (!empty($openid)) {
            if (request()->isMethod('post')) {
                $wall_id = request()->input('wall_id');
                if (empty($wall_id)) {
                    return response()->json(['code' => 1, 'errMsg' => lang('wechat.activity_is_empty')]);
                }
                $data['user_id'] = request()->input('user_id');
                $data['content'] = request()->input('content', '');
                if (empty($data['user_id']) || empty($data['content'])) {
                    return response()->json(['code' => 1, 'errMsg' => lang('wechat.message_is_empty')]);
                }
                if (strlen($data['content']) > 100) {
                    return response()->json(['code' => 1, 'errMsg' => lang('wechat.message_length_limit')]);
                }
                $data['addtime'] = $this->timeRepository->getGmTime();
                $data['wall_id'] = $wall_id;
                $data['wechat_id'] = $this->wechat_id;

                WechatWallMsg::create($data);
                //留言成功，跳转
                return response()->json(['code' => 0, 'errMsg' => lang('wechat.send_success')]);// 您的留言正在进行审查，请关注微信墙
            }

            $wall_id = request()->input('wall_id');
            if (empty($wall_id)) {
                return redirect()->route('/');
            }

            $wall_user = WechatWallUser::select('id', 'status')->where(['openid' => $openid, 'wall_id' => $wall_id, 'wechat_id' => $this->wechat_id])->first();
            $wall_user = $wall_user ? $wall_user->toArray() : [];

            //聊天室人数
            $user_num = WechatWallMsg::selectRaw("COUNT(DISTINCT user_id) as num")->where(['wall_id' => $wall_id, 'wechat_id' => $this->wechat_id])->first();
            $user_num = $user_num ? $user_num->toArray() : [];
            //$user_num = WechatWallMsg::where(['wall_id' => $wall_id, 'wechat_id' => $this->wechat_id])->distinct('user_id')->count('user_id');

            $condition = [
                'wall_id' => $wall_id,
                'wechat_id' => $this->wechat_id
            ];

            $model = WechatWallMsg::from('wechat_wall_msg as m')
                ->leftJoin('wechat_wall_user as u', 'u.id', '=', 'm.user_id')
                ->where('m.wechat_id', $condition['wechat_id'])
                ->where('m.wall_id', $condition['wall_id']);

            $model = $model->where(function ($query) use ($openid) {
                $query->where('m.status', 1)->orWhere('u.openid', $openid);
            });

            $num = $model->count();

            $list = $model->select('m.id', 'm.content', 'm.addtime', 'u.nickname', 'u.headimg', 'u.id as user_id')->limit(10)
                ->orderBy('m.addtime', 'DESC')
                ->get();

            $list = $list ? $list->toArray() : [];

            // 最新一条数据id
            $last_msg_id = 0;
            if ($list) {
                $collect = collect($list)->last();
                $last_msg_id = Arr::get($collect, 'id');
            }
            $this->plugin_assign('last_msg_id', $last_msg_id);

            if ($list) {
                usort($list, function ($a, $b) {
                    if ($a['addtime'] == $b['addtime']) {
                        return 0;
                    }
                    return $a['addtime'] > $b['addtime'] ? 1 : -1;
                });
                foreach ($list as $k => $v) {
                    $list[$k]['addtime'] = $this->timeRepository->getLocalDate('Y-m-d', $this->timeRepository->getGmTime()) == $this->timeRepository->getLocalDate('Y-m-d', $v['addtime']) ? $this->timeRepository->getLocalDate('H:i:s', $v['addtime']) : $this->timeRepository->getLocalDate('Y-m-d H:i:s', $v['addtime']);
                }
            }

            $this->plugin_assign('list', $list);
            $this->plugin_assign('msg_count', $num);
            $this->plugin_assign('user_num', $user_num['num']);
            $this->plugin_assign('user', $wall_user);
            $this->plugin_assign('wall_id', $wall_id);
            return $this->show_display('wallmsgwechat', $this->_data);
        }
    }

    /**
     * ajax请求留言
     */
    public function actionGetWallMsg()
    {
        if (request()->isMethod('post')) {
            $start = request()->input('start', 0);
            $num = request()->input('num', 5);
            $wall_id = request()->input('wall_id');
            $req = request()->input('req', 0);
            if ((!empty($start) || $start == 0) && $num) {
                $cache_key = md5('cache_' . $wall_id . $start . $req);
                //微信端数据单独存储
                $openid = $this->get_openid($this->wechat_ru_id);
                if (!empty($openid)) {
                    $cache_key = md5('cache_wechat_' . $wall_id . $start . $req);
                }
                $list = cache($cache_key);
                if (is_null($list)) {

                    $condition = [
                        'wall_id' => $wall_id,
                        'wechat_id' => $this->wechat_id,
                    ];

                    $model = WechatWallMsg::from('wechat_wall_msg as m')
                        ->leftJoin('wechat_wall_user as u', 'u.id', '=', 'm.user_id')
                        ->where('m.wechat_id', $condition['wechat_id'])
                        ->where('m.wall_id', $condition['wall_id']);

                    if (!empty($openid)) {
                        $model = $model->where(function ($query) use ($openid) {
                            $query->where('m.status', 1)->orWhere('u.openid', $openid);
                        });
                    } else {
                        $model = $model->where('m.status', 1);
                    }

                    $data = $model->select('m.id', 'm.content', 'm.addtime', 'u.nickname', 'u.headimg', 'u.id as user_id', 'm.status')->offset($start)
                        ->limit($num)
                        ->orderBy('m.addtime', 'ASC')
                        ->get();
                    $data = $data ? $data->toArray() : [];

                    foreach ($data as $k => $v) {
                        $data[$k]['addtime'] = $this->timeRepository->getLocalDate('Y-m-d', $this->timeRepository->getGmTime()) == $this->timeRepository->getLocalDate('Y-m-d', $v['addtime']) ? $this->timeRepository->getLocalDate('H:i:s', $v['addtime']) : $this->timeRepository->getLocalDate('Y-m-d H:i:s', $v['addtime']);
                    }
                    cache()->put($cache_key, $data, 1);
                    $list = $data;
                }

                // 聊天室人数
                $user_num = WechatWallMsg::selectRaw("COUNT(DISTINCT user_id) as num")->where(['wall_id' => $wall_id, 'wechat_id' => $this->wechat_id])->first();
                $result = ['code' => 0, 'user_num' => $user_num['num'], 'data' => $list];
                return response()->json($result);
            }
        } else {
            $result = ['code' => 1, 'errMsg' => lang('wechat.illegal_request')];
            return response()->json($result);
        }
    }

    /**
     * 新抽奖页面 - 年会 主页面
     */
    public function actionWallPrizeNew()
    {
        //活动内容
        $wall = WechatMarketing::where(['id' => $this->market_id, 'marketing_type' => 'wall', 'wechat_id' => $this->wechat_id])->first();
        $wall = $wall ? $wall->toArray() : [];

        if ($wall) {
            $wall['config'] = unserialize($wall['config']);
            $wall['logo'] = $this->wechatHelperService->get_wechat_image_path($wall['logo']);
            $wall['background'] = $this->wechatHelperService->get_wechat_image_path($wall['background']);
        }
        $this->plugin_assign('wall', $wall);

        // 所有参与且通过审核用户
        $user_list = WechatWallUser::where('status', 1)
            ->where('wechat_id', $this->wechat_id)
            ->where('wall_id', $this->market_id)
            ->groupBy('openid')
            ->orderBy('addtime', 'DESC')
            ->get();
        $user_list = $user_list ? $user_list->toArray() : [];

        foreach ($user_list as $key => $value) {
            $user_list[$key]['is_prized'] = $this->get_is_prize($value['openid']);
            $user_list[$key]['headimgurl'] = empty($value['headimgurl']) ? $value['headimg'] : $value['headimgurl'];
        }
        $this->plugin_assign('user_list', $user_list);
        return $this->show_display('wallprizenew', $this->_data);
    }

    /**
     * 新抽奖页面 - 年会 用户中奖
     * @return
     */
    public function actionGetPrizeUser()
    {
        if (request()->isMethod('post')) {
            $result['errCode'] = 0;
            $result['errMsg'] = '';

            $user_id = request()->input('user_id', 0);
            if (empty($user_id)) {
                $result['errCode'] = 1;
                return response()->json($result);
            }
            $user = WechatWallUser::where(['id' => $user_id, 'wall_id' => $this->market_id, 'status' => 1, 'wechat_id' => $this->wechat_id])
                ->first();
            $user = $user ? $user->toArray() : [];

            if (empty($user)) {
                $result['errCode'] = 2;
                $result['errMsg'] = lang('wechat.user_is_empty');
                return response()->json($result);
            }
            // 未中奖用户
            $prefix = config('database.connections.mysql.prefix');
            $sql = "SELECT u.* FROM " . $prefix . "wechat_wall_user u LEFT JOIN " . $prefix . "wechat_prize p ON u.openid = p.openid WHERE u.wall_id = '$this->market_id' AND u.status = 1 AND u.id = '" . $user_id . "' AND u.wechat_id = '$this->wechat_id' AND u.openid not in (SELECT openid FROM " . $prefix . "wechat_prize WHERE market_id = '$this->market_id' AND wechat_id = '$this->wechat_id' AND activity_type = 'wall') GROUP by u.openid ORDER BY u.addtime DESC";
            $list = DB::select($sql);

            if ($list) {
                foreach ($list as $k => $v) {
                    $list[$k] = $v ? collect($v)->toArray() : [];
                }
                $user = $list[0];
                //存储中奖用户
                $data['wechat_id'] = $this->wechat_id;
                $data['openid'] = $user['openid'];
                $data['issue_status'] = 0;
                $data['dateline'] = $this->timeRepository->getGmTime();
                $data['prize_type'] = 1;
                $data['prize_name'] = lang('wechat.wall_prize_name');
                $data['activity_type'] = 'wall';
                $data['market_id'] = $this->market_id;
                WechatPrize::create($data);

                $result['data'] = $user;
                return response()->json($result);
            }
            $result['errCode'] = 2;
            $result['errMsg'] = lang('wechat.no_data');
            return response()->json($result);
        }
    }

    /**
     * 新抽奖页面 - 年会 查看会员信息
     * @return
     */
    public function actionGetOneUser()
    {
        if (request()->isMethod('post')) {
            $result['errCode'] = 0;
            $result['errMsg'] = '';

            $user_id = request()->input('user_id', 0);
            if (empty($user_id)) {
                $result['errCode'] = 1;
                return response()->json($result);
            }
            $user = WechatWallUser::where(['id' => $user_id, 'wall_id' => $this->market_id, 'status' => 1, 'wechat_id' => $this->wechat_id])
                ->first();
            $user = $user ? $user->toArray() : [];
            if (empty($user)) {
                $result['errCode'] = 2;
                $result['errMsg'] = lang('wechat.user_is_empty');
                return response()->json($result);
            }

            $result['data'] = $user;
            return response()->json($result);
        }
    }

    /**
     * 新抽奖页面 - 年会  是否中奖函数
     * @param string $openid
     * @param integer $wechat_id
     * @param integer $market_id
     * @return
     */
    public function get_is_prize($openid = '')
    {
        $count = WechatPrize::where(['openid' => $openid, 'wechat_id' => $this->wechat_id, 'market_id' => $this->market_id])->count();
        if ($count > 0) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 兼容处理老版本图片路径数据
     * @param string $image_path
     * @return mixed
     */
    protected function replace_wechatWallUser_image($image_path = '')
    {
        return preg_replace('/^\/mobile\/public\//', '', $image_path, 1);
    }
}
