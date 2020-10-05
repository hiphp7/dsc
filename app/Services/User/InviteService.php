<?php

namespace App\Services\User;

use App\Models\OrderInfo;
use App\Models\Users;
use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsMobileService;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use think\Image;

/**
 * 我的分享
 * Class InviteService
 * @package App\Services\User
 */
class InviteService
{
    protected $config;
    protected $goodsMobileService;
    protected $dscRepository;

    public function __construct(
        GoodsMobileService $goodsMobileService,
        DscRepository $dscRepository
    )
    {
        $this->goodsMobileService = $goodsMobileService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 生成分享二维码
     *
     * @param $user_id
     * @param int $page
     * @param int $size
     * @param int $goods_id
     * @return array
     * @throws \Exception
     */
    public function getInvite($user_id, $page = 1, $size = 10, $goods_id = 0)
    {
        $share = [];
        if ($this->config['affiliate']) {
            $share = unserialize($this->config['affiliate']);
        }

        if ($share && $share['on'] == 0) {
            return false;
        }

        $rt = [];
        if (empty($goods_id)) {
            empty($share) && $share = [];

            // 推荐注册分成
            $affdb = [];
            $num = count($share['item']);
            $up_uid = $user_id;
            $all_uid = $user_id;
            for ($i = 1; $i <= $num; $i++) {
                $count = 0;
                if ($up_uid) {
                    $rs = Users::select('user_id')->whereRaw('parent_id IN(' . $up_uid . ')')->get();
                    $rs = $rs ? $rs->toArray() : [];

                    $up_uid = '';
                    foreach ($rs as $k => $v) {
                        $up_uid .= $up_uid ? ",'$v[user_id]'" : "'$v[user_id]'";
                        if ($i < $num) {
                            $all_uid .= ", '$v[user_id]'";
                        }
                        $count++;
                    }
                }
                $affdb[$i]['num'] = $count;
                $affdb[$i]['point'] = $share['item'][$i - 1]['level_point'];
                $affdb[$i]['money'] = $share['item'][$i - 1]['level_money'];
            }

            $res = OrderInfo::where('main_count', 0)
                ->where('ru_id', 0)
                ->where('user_id', '>', 0);

            //只显示平台分成订单
            $begin = ($page - 1) * $size;

            // 推荐注册分成
            if ($share['config']['separate_by'] == 0) {
                $all_uid = explode(',', $all_uid);
                // 分成会员订单
                $sqlcount = $res->whereHas('getUsers', function ($query) use ($all_uid) {
                    $query->whereIn('parent_id', $all_uid);
                });

                $sqlcount = $sqlcount->whereHas('getAffiliateLog', function ($query) use ($user_id) {
                    $query->orWhere('user_id', $user_id);
                });
                $sqlcount = $sqlcount->where(function ($query) {
                    $query->where('is_separate', 0)->orWhere('is_separate', '>', 0);
                });


                $res_count = $sqlcount->count();

                $sqlcount = $sqlcount->with(['getAffiliateLog' => function ($query) {
                    $query->selectRaw('order_id,log_id, user_id as suid,  user_name as auser, money, point, separate_type');
                }]);

                $all_res = $sqlcount->orderBy('order_id', 'DESC')
                    ->offset($begin)
                    ->limit($size)
                    ->get();
                $all_res = $all_res ? $all_res->toArray() : [];
            } else {
                // 推荐订单分成
                $sqlcount = $res->whereHas('getUsers');
                $sqlcount = $sqlcount->whereHas('getAffiliateLog', function ($query) use ($user_id) {
                    $query->orWhere('user_id', $user_id);
                });
                $sqlcount = $sqlcount->where(function ($query) use ($user_id) {
                    $query->where('is_separate', 0)->orWhere('is_separate', '>', 0)->where('parent_id', $user_id);
                });

                $res_count = $sqlcount->count();

                $sqlcount = $sqlcount->with(['getAffiliateLog' => function ($query) {
                    $query->selectRaw('order_id,log_id, user_id as suid,  user_name as auser, money, point, separate_type');
                }, 'getUsers' => function ($query) {
                    $query->selectRaw('parent_id as up');
                }]);

                $all_res = $sqlcount->orderBy('order_id', 'DESC')
                    ->offset($begin)
                    ->limit($size)
                    ->get();
                $all_res = $all_res ? $all_res->toArray() : [];
            }

            if ($all_res) {
                foreach ($all_res as $k => $v) {
                    if (!empty($v['get_users']['suid'])) {
                        // 在affiliate_log有记录
                        if ($v['get_affiliate_log']['separate_type'] == -1 || $v['get_affiliate_log']['separate_type'] == -2) {
                            // 已被撤销
                            $rt[$k]['is_separate'] = 3;
                        }
                    }

                    $rt[$k]['order_sn'] = substr($v['order_sn'], 0, strlen($v['order_sn']) - 5) . "***" . substr($v['order_sn'], -2, 2);
                    $rt[$k]['affiliate_type'] = $share['config']['separate_by'];
                    $rt[$k]['order_id'] = $v['order_id'];
                    $rt[$k]['is_separate'] = $v['is_separate'];
                    $rt[$k]['log_id'] = $v['get_affiliate_log']['log_id'];
                    $rt[$k]['suid'] = $v['get_affiliate_log']['suid'];
                    $rt[$k]['auser'] = $v['get_affiliate_log']['auser'];
                    $rt[$k]['money'] = $v['get_affiliate_log']['money'];
                    $rt[$k]['point'] = $v['get_affiliate_log']['point'];
                    $rt[$k]['separate_type'] = $v['get_affiliate_log']['separate_type'];
                }
            }
        } else {
            // 单个商品推荐
            $types = [1, 2, 3, 4, 5];
            $goods = $this->goodsMobileService->goodsInfo($goods_id);
            if ($goods) {
                $goods['goods_img'] = $this->dscRepository->getImagePath($goods['goods_img']);
                $goods['goods_thumb'] = $this->dscRepository->getImagePath($goods['goods_thumb']);
                $goods['shop_price'] = $this->dscRepository->getPriceFormat($goods['shop_price']);
            }
        }

        $type = $share['config']['expire_unit'];
        $expire_unit = '';
        switch ($type) {
            case 'hour':
                $expire_unit = lang('user.expire_unit.hour');//时效单位
                break;
            case 'day':
                $expire_unit = lang('user.expire_unit.day');//时效单位
                break;
            case 'week':
                $expire_unit = lang('user.expire_unit.week');//时效单位
                break;
        }

        $config_info = [];
        if ($share['config']['separate_by'] == 0) {
            $config_info['separate_by'] = $share['config']['separate_by'];//分成模式
            $config_info['expire'] = $share['config']['expire'];//分成时效
            $config_info['level_register_all'] = $share['config']['level_register_all'];//注册送的积分
            $config_info['level_register_up'] = $share['config']['level_register_up'];//注册送的积分上限
            $config_info['level_money_all'] = $share['config']['level_money_all'];//金额比例
            $config_info['level_point_all'] = $share['config']['level_point_all'];//积分比例
        }
        if ($share['config']['separate_by'] == 1) {
            $config_info['separate_by'] = $share['config']['separate_by'];//分成模式
            $config_info['expire'] = $share['config']['expire'];//分成时效
            $config_info['level_money_all'] = $share['config']['level_money_all'];//金额比例
            $config_info['level_point_all'] = $share['config']['level_point_all'];//积分比例
        }


        // 二维码内容
        //$parent_id = base64_encode($user_id);
        $url = dsc_url('/#/home') . '?' . http_build_query(['parent_id' => $user_id], '', '&');

        //保存二维码目录
        $file_path = storage_public('data/attached/share_qrcode/');
        if (!file_exists($file_path)) {
            make_dir($file_path);
        }
        //二维码背景
        $qrcode_bg = public_path('img/affiliate.jpg');
        // 输出图片
        $share_img = $file_path . 'user_share_' . $user_id . '_bg.png';

        // 生成二维码条件
        $generate = false;
        if (file_exists($share_img)) {
            $lastmtime = filemtime($share_img) + 3600 * 24 * 30; // 30天有效期之后重新生成
            if (time() >= $lastmtime) {
                $generate = true;
            }
        }

        if (!file_exists($share_img) || $generate == true) {
            // 生成二维码
            $qrCode = new QrCode($url);

            $qrCode->setSize(266);
            $qrCode->setMargin(15);
            $qrCode->setErrorCorrectionLevel(new ErrorCorrectionLevel('quartile'));
            $qrCode->writeFile($share_img); // 保存二维码

            // 背景图+二维码
            $bg_width = Image::open($qrcode_bg)->width(); // 背景图宽
            $bg_height = Image::open($qrcode_bg)->height(); // 背景图高

            $logo_width = Image::open($share_img)->width(); // logo图宽 296
            Image::open($qrcode_bg)->water($share_img, [($bg_width - $logo_width) / 2, $bg_height / 2], 100)->save($share_img);
        }

        $image_name = 'data/attached/share_qrcode/' . basename($share_img);

        $result = [];
        $result['config_info'] = $config_info; // 分成模式 配置信息
        $result['expire_unit'] = $expire_unit; // 分成时效
        $result['all_res'] = $rt;  // 推荐记录
        $result['all_res_total'] = $res_count ?? 0; // 记录总数
        $result['affdb'] = $affdb ?? [];
        $result['share'] = $share;

        if ($goods_id > 0) {
            $result['types'] = $types ?? [];
            $result['$goods'] = $goods ?? [];
        }
        // 返回图片
        $result['img_src'] = $this->dscRepository->getImagePath($image_name);
        $result['file'] = $image_name;

        return $result;
    }
}
