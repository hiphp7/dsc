<?php

namespace App\Http\Controllers;

use App\Models\SellerShopinfo;
use App\Models\Users;
use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * 在线客服
 */
class OnlineController extends InitController
{
    protected $goodsService;
    protected $config;
    protected $dscRepository;

    public function __construct(
        GoodsService $goodsService,
        DscRepository $dscRepository
    )
    {
        $this->goodsService = $goodsService;
        $this->config = $this->config();
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        /**
         * Start
         *
         * @param $warehouse_id 仓库ID
         * @param $area_id 省份ID
         * @param $area_city 城市ID
         */
        $warehouse_id = $this->warehouseId();
        $area_id = $this->areaId();
        $area_city = $this->areaCity();
        $act = addslashes(request()->input('act', ''));
        /* End */

        assign_template();

        $user_id = session('user_id', 0);

        /**
         * 在线客服
         */
        if ($act == 'service') {
            $IM_menu = url('/') . '/online.php?act=service_menu';

            $goods_id = (int)request()->input('goods_id', 0);
            $seller_id = (int)request()->input('ru_id', -1);

            $ru_id = 0;

            if ($this->config['customer_service'] != 0) {
                if ($seller_id > -1) {
                    $ru_id = $seller_id;
                } else if ($goods_id > 0) {
                    $where = [
                        'goods_id' => $goods_id,
                        'warehouse_id' => $warehouse_id,
                        'area_id' => $area_id,
                        'area_city' => $area_city
                    ];
                    $goods = $this->goodsService->getGoodsInfo($where);

                    $ru_id = $goods['user_id'];
                }
            }

            // 优先使用自有在线客服
            if (class_exists('App\\Http\\Controllers\\Chat\\IndexController')) {
                if (empty($user_id)) {
                    return "<script>window.location.href='user.php';</script>";
                }

                load_helper('code');
                $dbhash = md5(config('app.key'));
                $user_token = [
                    'user_id' => $user_id,
                    'user_name' => session('user_name', ''),
                    'hash' => md5(session('user_name', '') . date('YmdH') . $dbhash)
                ];

                $token = Str::random();
                Cache::put($token, encrypt($user_token), Carbon::now()->addMinutes(1));

                return redirect()->route('kefu.index.index', ['t' => $token, 'ru_id' => $ru_id, 'goods_id' => $goods_id]);
            }

            $basic_info = SellerShopinfo::where('ru_id', $ru_id)->first();
            $basic_info = $basic_info ? $basic_info->toArray() : [];

            if ($basic_info) {
                IM($basic_info['kf_appkey'], $basic_info['kf_secretkey']);

                if (empty($basic_info['kf_logo']) || $basic_info['kf_logo'] == 'http://') {
                    $basic_info['kf_logo'] = 'http://dsc-kf.oss-cn-shanghai.aliyuncs.com/dsc_kf/p16812444.jpg';
                }

                $this->smarty->assign('kf_appkey', $basic_info['kf_appkey']);
                $this->smarty->assign('kf_touid', $basic_info['kf_touid']);
                $this->smarty->assign('kf_logo', $basic_info['kf_logo']);
                $this->smarty->assign('kf_welcomeMsg', $basic_info['kf_welcomeMsg']);
            }

            //判断用户是否登入,登入了就登入登入用户,未登入就登入匿名用户;
            if ($user_id) {

                $user_info = Users::where('user_id', $user_id)->first();
                $user_info = $user_info ? $user_info->toArray() : [];

                $user_info['user_id'] = 'dsc' . $user_id;
                if (empty($user_info['user_picture'])) {
                    $user_logo = $this->dscRepository->getImagePath('dsc_kf/dsc_kf_user_logo.jpg');
                } else {
                    $user_logo = $this->dscRepository->getImagePath($user_info['user_picture']);
                }
            } else {
                $user_info['user_id'] = $user_id;
                $user_logo = $this->dscRepository->getImagePath('dsc_kf/dsc_kf_user_logo.jpg');
            }

            $this->smarty->assign('user_id', $user_info['user_id'] ?? 0);
            $this->smarty->assign('user_logo', $user_logo);
            $this->smarty->assign('IM_menu', $IM_menu);
            $this->smarty->assign('goods_id', $goods_id);

            return $this->smarty->display('chats.dwt');
        }

        /**
         * 左侧菜单
         */
        if ($act == 'service_menu') {
            return $this->smarty->display('chats_menu.dwt');
        }

        /*
         * 右侧菜单
         */
        if ($act == 'history') {
            $request = dsc_decode(request()->input('q', ''), true);

            $itemId = $request['itemsId'][0];//商品ID;
            $url = url('/') . '/';
            echo $current_url = request()->server('SERVER_NAME') . request()->server('REQUEST_URI');
            die;

            $where = [
                'goods_id' => $itemId,
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city
            ];
            $goodsInfo = $this->goodsService->getGoodsInfo($where);

            echo <<<HTML
    {
    "code": "200",
    "desc": "powered by dscmall",
    "itemDetail": [
            {
                "userid": "{$request['userid']}",
                "itemid": "{$itemId}",
                "itemname": "{$goodsInfo['goods_name']}",
                "itempic": "{$url}{$goodsInfo['goods_thumb']}",
                "itemprice": "{$goodsInfo['shop_price']}",
                "itemurl": "{$current_url}",
                "extra": {}
            }
        ]
    }
HTML;
        }
    }
}
