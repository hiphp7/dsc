<?php

namespace App\Services\Common;

use App\Models\ShopConfig;
use Illuminate\Support\Facades\Storage;

/**
 * Class Config
 * @package App\Services\Common
 */
class ConfigService
{

    /**
     * 载入配置信息
     * @param null $item 配置名称
     * @param bool $force 强制获取
     * @return array|\Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getConfig($item = null, $force = false)
    {
        $arr = cache('shop_config');

        $dataLockfile = app_path("Http/Controllers/Install/data/install.lock.php");

        if (is_file($dataLockfile)) {
            $data = '大商创x https://www.dscmall.cn/';
            Storage::disk('local')->put('seeder/install.lock.php', $data);

            unlink($dataLockfile);
        }

        $lockfile = Storage::disk('local')->exists('seeder/install.lock.php');

        if (!$lockfile) {
            $arr = [
                'lang' => 'zh-CN',
                'cloud_client_id' => 0,
                'cloud_appkey' => '',
                'cloud_dsc_appkey' => '',
                'cloud_storage' => 0
            ];

            return is_null($item) ? $arr : $arr[$item];
        }

        if (is_null($arr) || $force) {
            $res = ShopConfig::where('parent_id', '>', 0)->get();
            $res = is_null($res) ? [] : $res->toArray();

            if ($res) {
                foreach ($res as $row) {
                    $arr[$row['code']] = $row['value'];
                }
            } else {
                return [];
            }

            /* 处理客服QQ数组 by kong */
            if ($arr['qq']) {
                $kf_qq = array_filter(preg_split('/\s+/', $arr['qq']));
                if (!empty($kf_qq[0])) {
                    $kf_qq = $kf_qq && $kf_qq[0] ? explode("|", $kf_qq[0]) : [];
                    if ($kf_qq) {
                        if (isset($kf_qq[1]) && !empty($kf_qq[1])) {
                            $kf_qq_one = $kf_qq[1];
                        }
                    }
                }
            } else {
                $kf_qq_one = "";
            }
            /* 处理客服旺旺数组 by kong */
            if ($arr['ww']) {
                $kf_ww = array_filter(preg_split('/\s+/', $arr['ww']));
                if (!empty($kf_ww[0])) {
                    $kf_ww = $kf_ww && $kf_ww[0] ? explode("|", $kf_ww[0]) : [];
                    if (isset($kf_ww[1]) && !empty($kf_ww[1])) {
                        $kf_ww_one = $kf_ww[1];
                    } else {
                        $kf_ww_one = "";
                    }
                }
            } else {
                $kf_ww_one = "";
            }

            /* 对数值型设置处理 */
            $arr['watermark_alpha'] = intval($arr['watermark_alpha']);
            $arr['market_price_rate'] = floatval($arr['market_price_rate']);
            $arr['integral_scale'] = floatval($arr['integral_scale']);
            $arr['integral_percent'] = isset($arr['integral_percent']) ? floatval($arr['integral_percent']) : 0;
            $arr['cache_time'] = intval($arr['cache_time']);
            $arr['thumb_width'] = intval($arr['thumb_width']);
            $arr['thumb_height'] = intval($arr['thumb_height']);
            $arr['image_width'] = intval($arr['image_width']);
            $arr['image_height'] = intval($arr['image_height']);
            $arr['best_number'] = !empty($arr['best_number']) && intval($arr['best_number']) > 0 ? intval($arr['best_number']) : 3;
            $arr['new_number'] = !empty($arr['new_number']) && intval($arr['new_number']) > 0 ? intval($arr['new_number']) : 3;
            $arr['hot_number'] = !empty($arr['hot_number']) && intval($arr['hot_number']) > 0 ? intval($arr['hot_number']) : 3;
            $arr['promote_number'] = !empty($arr['promote_number']) && intval($arr['promote_number']) > 0 ? intval($arr['promote_number']) : 3;
            $arr['top_number'] = intval($arr['top_number']) > 0 ? intval($arr['top_number']) : 10;
            $arr['history_number'] = intval($arr['history_number']) > 0 ? intval($arr['history_number']) : 5;
            $arr['comments_number'] = intval($arr['comments_number']) > 0 ? intval($arr['comments_number']) : 5;
            $arr['article_number'] = intval($arr['article_number']) > 0 ? intval($arr['article_number']) : 5;
            $arr['page_size'] = intval($arr['page_size']) > 0 ? intval($arr['page_size']) : 10;
            $arr['bought_goods'] = intval($arr['bought_goods']);
            $arr['goods_name_length'] = intval($arr['goods_name_length']);
            $arr['top10_time'] = intval($arr['top10_time']);
            $arr['goods_gallery_number'] = intval($arr['goods_gallery_number']) ? intval($arr['goods_gallery_number']) : 5;
            $arr['no_picture'] = !empty($arr['no_picture']) ? $arr['no_picture'] : 'images/no_picture.gif'; // 修改默认商品图片的路径
            $arr['qq'] = !empty($kf_qq_one) ? $kf_qq_one : ''; // by kong 改
            $arr['ww'] = !empty($kf_ww_one) ? $kf_ww_one : ''; // by kong 改
            $arr['default_storage'] = isset($arr['default_storage']) ? intval($arr['default_storage']) : 1;
            $arr['min_goods_amount'] = isset($arr['min_goods_amount']) ? floatval($arr['min_goods_amount']) : 0;
            $arr['one_step_buy'] = empty($arr['one_step_buy']) ? 0 : 1;
            $arr['invoice_type'] = !isset($arr['invoice_type']) && empty($arr['invoice_type']) ? ['type' => [], 'rate' => []] : $arr['invoice_type'];
            $arr['show_order_type'] = isset($arr['show_order_type']) ? $arr['show_order_type'] : 0;    // 显示方式默认为列表方式
            $arr['help_open'] = isset($arr['help_open']) ? $arr['help_open'] : 1;    // 显示方式默认为列表方式
            $arr['currency_format'] = !empty($arr['currency_format']) ? strip_tags($arr['currency_format']) : '';
            $arr['cat_belongs'] = isset($arr['cat_belongs']) ? $arr['cat_belongs'] : 0;

            if (!is_array($arr['invoice_type'])) {
                $arr['invoice_type'] = $this->unserialize($arr['invoice_type']);
            }

            //限定语言项
            $lang_array = ['zh-CN', 'zh-TW', 'en'];
            if (empty($arr['lang']) || !in_array($arr['lang'], $lang_array)) {
                $arr['lang'] = 'zh-CN'; // 默认语言为简体中文
            }

            cache()->forever('shop_config', $arr);
        }

        return is_null($item) ? $arr : $arr[$item] ?? '';
    }

    /**
     * 设置全局配置
     * @param $k
     * @param $v
     * @return mixed
     */
    public function setConfig($k, $v)
    {
        return ShopConfig::where('code', $k)->update(['value' => $v]);
    }

    /**
     * 重返序列化
     * @param $serial_str
     * @return mixed
     */
    private function unserialize($serial_str)
    {
        $out = preg_replace_callback('!s:(\d+):"(.*?)";!s', function ($r) {
            return 's:' . strlen($r[2]) . ':"' . $r[2] . '";';
        }, $serial_str);
        return unserialize($out);
    }
}
