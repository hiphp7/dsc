<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Repositories\Common\DscRepository;
use App\Services\Ads\AdsCommonService;
use App\Services\Wechat\WechatService;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;
use EasyWeChat\Kernel\Exceptions\RuntimeException;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psr\SimpleCache\InvalidArgumentException;

class IndexController extends Controller
{
    protected $config;
    protected $wechatService;
    protected $client;
    protected $dscRepository;
    protected $adsCommonService;

    public function __construct(
        WechatService $wechatService,
        Client $client,
        DscRepository $dscRepository,
        AdsCommonService $adsCommonService
    )
    {
        $this->wechatService = $wechatService;
        $this->client = $client;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->adsCommonService = $adsCommonService;
    }

    /**
     * App 首页数据
     * @param Request $request
     * @return Repository
     * @throws Exception
     */
    public function index(Request $request)
    {
        $id = $request->get('ru_id', 0);
        $type = $request->get('type', 'index');

        return cache()->get('app_visual_data_' . $id, function () use ($id, $type) {
            $defaultResp = $this->client(route('api.visual.default'), ['ru_id' => $id, 'type' => $type]);

            $viewResp = $this->client(route('api.visual.view'), ['id' => $defaultResp['data'], 'type' => $type]);

            return json_decode($viewResp['data']['data'], true);
        });
    }

    /**
     * App首页
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function home(Request $request)
    {
        $data = $this->index($request);

        return $this->succeed($data);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function parseArticle($data)
    {
        $data = [
            'cat_id' => $data['allValue']['optionCascaderVal'] ?? 0,
            'num' => $data['allValue']['number'] ?? 0
        ];

        $res = $this->client(route('api.visual.article'), $data);

        return $res['data'];
    }

    /**
     * @param $data
     * @return mixed
     */
    public function parseSeckill($data)
    {
        $data = [
            'num' => $data['allValue']['number'] ?? 0
        ];

        $res = $this->client(route('api.visual.seckill'), $data);

        return $res['data'];
    }

    /**
     * @param $data
     * @return mixed
     */
    public function parseShop($data)
    {
        $data = [
            'number' => $data['allValue']['number'] ?? 0
        ];

        $res = $this->client(route('api.visual.store'), $data);

        return $res['data'];
    }

    /**
     * @param $data
     * @return mixed
     */
    public function parseGoods($data)
    {
        $data = [
            'number' => $data['allValue']['number'] ?? 0,
            'goods_id' => isset($data['allValue']['selectGoodsId']) && !empty($data['allValue']['selectGoodsId']) ? implode(',', $data['allValue']['selectGoodsId']) : ''
        ];

        $res = $this->client(route('api.visual.checked'), $data);

        return $res['data'];
    }

    /**
     * 处理Http请求
     * @param $url
     * @param $data
     * @return mixed
     */
    protected function client($url, $data)
    {
        $response = $this->client->post($url, ['form_params' => $data]);

        return json_decode($response->getBody(), true);
    }

    /**
     * 获取商店配置
     * @return JsonResponse
     * @throws Exception
     */
    public function shopConfig()
    {
        $config = $this->config;

        $allow = [
            'shop_name',
            'shop_title',
            'shop_desc',
            'shop_keywords',
            'shop_logo',
            'wap_logo',
            'buyer_cash',
            'buyer_recharge',
            'register_article_id',
            'search_keywords',
            'stats_code',
            'shop_reg_closed',
            'currency_format',
            'lang',
            'privacy'
        ];

        foreach ($config as $key => $item) {
            if (!in_array($key, $allow)) {
                unset($config[$key]);
            }
        }

        $config['wap_logo'] = isset($config['wap_logo']) ? $this->dscRepository->getImagePath(str_replace('../', '', $config['wap_logo'])) : '';
        $config['shop_logo'] = isset($config['shop_logo']) ? $this->dscRepository->getImagePath(str_replace('../', '', $config['shop_logo'])) : '';
        $config['bonus_ad'] = $this->adsCommonService->getPopupAds(); // 获取手机首页弹框红包
        $config['mp_checked'] = config('app.app_mp_checked'); // 小程序审核模式（true：审核通过，false：审核中）

        return $this->succeed($config);
    }

    /**
     * 获取后台语言包api
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function shopLang(Request $request)
    {
        static $_LANG = [];

        $replace = $request->get('replace', []);
        $locale = $request->get('lang', 'zh-CN');

        // 默认加载语言包文件
        $defaultFiles = [
            'common',
            'user',
            'flow'
        ];
        // 接受自定义加载语言包文件
        $file = $request->get('file', ['goods']); // 支持数组 ['user','sms']
        if (!is_array($file)) {
            $file = [$file];
        }
        $files = array_merge($defaultFiles, $file);

        foreach ($files as $k => $vo) {
            $_LANG[$vo] = lang($vo, $replace, $locale);
        }

        return $this->succeed($_LANG);
    }

    /**
     * 返回wechat jssdk参数
     * @param Request $request
     * @return JsonResponse
     * @throws InvalidConfigException
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function jssdk(Request $request)
    {
        $url = $request->get('url', '');

        if (!empty($url)) {
            $ru_id = $request->get('ru_id', 0);

            $data = $this->wechatService->getJssdk($ru_id, $url);

            if ($data['status'] == '200') {
                return $this->succeed($data['data']);
            } else {
                return $this->setStatusCode(100)->failed($data['message']);
            }
        } else {
            return $this->setStatusCode(100)->failed('缺少参数');
        }
    }
}
