<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Models\SellerShopinfo;
use App\Models\Users;
use App\Repositories\Common\DscRepository;
use App\Services\Wholesale\GoodsService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    protected $config;
    protected $dscRepository;
    protected $goodsService;

    public function __construct(
        DscRepository $dscRepository,
        GoodsService $goodsService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->goodsService = $goodsService;
    }

    /**
     * 客服链接
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function index(Request $request)
    {
        $shop_id = $request->get('shop_id', 0);
        $goods_id = $request->get('goods_id', 0);
        $type = $request->get('type', '');
        $uid = $this->uid;

        if (empty($uid)) {
            return $this->failed(lang('user.not_login'));
        }

        // 供应商 kf_qq
        if (!empty($type) && $type == 'supplier') {
            $supplier = $this->goodsService->getSupplierHome($shop_id);
            $chat_url = 'http://wpa.qq.com/msgrd?v=3&uin=' . $supplier['kf_qq'] . '&site=qq&menu=yes';
            return $this->succeed(['url' => $chat_url]);
        }

        // 默认统一客服模式
        $customer_service = $this->config['customer_service'] ?? 0;
        if ($customer_service == 0) {
            $shop_id = 0;
        }

        // 店铺信息
        $shopInfo = SellerShopinfo::where('ru_id', $shop_id);
        $shopInfo = $shopInfo->with([
            'getMerchantsShopInformation'
        ]);

        $shopInfo = $shopInfo->first();

        $shopInfo = $shopInfo ? $shopInfo->toArray() : [];
        $is_IM = $shopInfo['get_merchants_shop_information']['is_IM'] ?? 0;

        if ($is_IM == 0) {

            $chat = $this->dscRepository->chatQq($shopInfo);
            $shopInfo['kf_type'] = $chat['kf_type'];
            $shopInfo['kf_qq'] = $chat['kf_qq'];
            $shopInfo['kf_ww'] = $chat['kf_ww'];

            // QQ客服
            if ($shopInfo['kf_type'] === 0 && !empty($shopInfo['kf_qq'])) {
                $chat_url = 'http://wpa.qq.com/msgrd?v=3&uin=' . $shopInfo['kf_qq'] . '&site=qq&menu=yes';
                return $this->succeed(['url' => $chat_url]);
            }

            // 旺旺客服
            if ($shopInfo['kf_type'] === 1 && !empty($shopInfo['kf_ww'])) {
                $chat_url = 'http://amos.alicdn.com/msg.aw?v=2&uid=' . $shopInfo['kf_ww'] . '&site=cnalichn&s=10&charset=gbk';
                return $this->succeed(['url' => $chat_url]);
            }

            // 美恰客服
            if (!empty($shopInfo['meiqia'])) {
                $chat_url = 'meiqia';
                return $this->succeed(['url' => $chat_url]);
            }
        }

        // 自有客服
        $user = Users::where('user_id', $uid)->first();
        $user = $user ? $user->toArray() : [];

        $user_name = $user['user_name'] ?? '';

        $user_token = [
            'user_id' => $uid,
            'user_name' => $user_name,
            'hash' => md5($user_name . date('YmdH') . md5(config('app.key')))
        ];


        $token = Str::random();
        Cache::put($token, encrypt($user_token), Carbon::now()->addMinutes(1));

        // app 客服
        if (!empty($type) && $type == 'app') {
            return $this->succeed(['token' => $token, 'ru_id' => $shop_id, 'goods_id' => $goods_id]);
        }

        $chat_url = route('kefu.index.index', ['t' => $token, 'ru_id' => $shop_id, 'goods_id' => $goods_id]);

        return $this->succeed(['url' => $chat_url]);
    }
}
