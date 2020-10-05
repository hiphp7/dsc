<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Adsense;
use App\Models\Goods;
use App\Repositories\Common\DscRepository;

/**
 * 广告处理文件
 */
class AfficheController extends InitController
{
    protected $dscRepository;

    public function __construct(
        DscRepository $dscRepository
    )
    {
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        $ad_id = intval(request()->input('ad_id', 0));

        /* 没有指定广告的id及跳转地址 */
        if (empty($ad_id)) {
            return redirect("/");
        }

        /* act 操作项的初始化*/
        $act = addslashes(request()->input('act', ''));

        $charset = addslashes(request()->input('charset', ''));

        if ($act == 'js') {
            /* 编码转换 */
            if (empty($charset)) {
                $charset = 'UTF8';
            }

            header('Content-type: application/x-javascript; charset=' . ($charset == 'UTF8' ? 'utf-8' : $charset));

            $url = url('/') . '/';
            $str = "";

            $now = gmtime();

            /* 取得广告的信息 */
            $ad_info = Ad::select('ad_id', 'ad_name', 'ad_link', 'ad_code')
                ->where('ad_id', $ad_id)
                ->where('start_time', '<=', $now)
                ->where('end_time', '>=', $now);

            $ad_info = $ad_info->whereHas('getAdPosition');

            $ad_info = $ad_info->first();

            $ad_info = $ad_info ? $ad_info->toArray() : [];

            if (!empty($ad_info)) {
                /* 转换编码 */
                if ($charset != 'UTF8') {
                    $ad_info['ad_name'] = dsc_iconv('UTF8', $charset, $ad_info['ad_name']);
                    $ad_info['ad_code'] = dsc_iconv('UTF8', $charset, $ad_info['ad_code']);
                }

                /* 初始化广告的类型和来源 */
                $type = intval(request()->input('type', 0));
                $from = urlencode(request()->input('from', ''));

                $str = '';
                switch ($type) {
                    case '0':
                        /* 图片广告 */
                        $src = (strpos($ad_info['ad_code'], 'http://') === false && strpos($ad_info['ad_code'], 'https://') === false) ? $url . DATA_DIR . "/afficheimg/$ad_info[ad_code]" : $ad_info['ad_code'];
                        $str = '<a href="' . $url . 'affiche.php?ad_id=' . $ad_info['ad_id'] . '&from=' . $from . '&uri=' . urlencode($ad_info['ad_link']) . '" target="_blank">' .
                            '<img src="' . $src . '" border="0" alt="' . $ad_info['ad_name'] . '" /></a>';
                        break;

                    case '1':
                        /* Falsh广告 */
                        $src = (strpos($ad_info['ad_code'], 'http://') === false && strpos($ad_info['ad_code'], 'https://') === false) ? $url . DATA_DIR . '/afficheimg/' . $ad_info['ad_code'] : $ad_info['ad_code'];
                        $str = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0"> <param name="movie" value="' . $src . '"><param name="quality" value="high"><embed src="' . $src . '" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash"></embed></object>';
                        break;

                    case '2':
                        /* 代码广告 */
                        $str = $ad_info['ad_code'];
                        break;

                    case 3:
                        /* 文字广告 */
                        $str = '<a href="' . $url . 'affiche.php?ad_id=' . $ad_info['ad_id'] . '&from=' . $from . '&uri=' . urlencode($ad_info['ad_link']) . '" target="_blank">' . nl2br(htmlspecialchars(addslashes($ad_info['ad_code']))) . '</a>';
                        break;
                }
            }
            echo "document.writeln('$str');";
        }

        /* ------------------------------------------------------ */
        //-- 获取投放站点
        /* ------------------------------------------------------ */
        else {
            $site_name = htmlspecialchars(request()->input('from', addslashes($GLOBALS['_LANG']['self_site'])));

            /* 商品的ID */
            $goods_id = intval(request()->input('goods_id', 0));

            /* 存入SESSION中,购物后一起存到订单数据表里 */
            session([
                'from_ad' => $ad_id,
                'referer' => stripslashes($site_name)
            ]);

            /* 如果是商品的站外JS */
            if ($ad_id == '-1') {
                $adsense = Adsense::where('from_ad', '-1')->where('referer', $site_name)->count();
                if ($adsense > 0) {
                    Adsense::where('from_ad', '-1')->where('referer', $site_name)->increment('clicks', 1);
                } else {
                    $adsenseOther = [
                        'from_ad' => '-1',
                        'referer' => $site_name,
                        'clicks' => 1
                    ];

                    Adsense::insert($adsenseOther);
                }

                $goods_name = Goods::where('goods_id', $goods_id)->value('goods_name');
                $uri = $this->dscRepository->buildUri('goods', ['gid' => $goods_id], $goods_name);

                return dsc_header("Location: $uri\n");
            } else {
                /* 更新站内广告的点击次数 */
                Ad::where('ad_id', $ad_id)->increment('click_count', 1);

                $adsense = Adsense::where('from_ad', $ad_id)->where('referer', $site_name)->count();
                if ($adsense > 0) {
                    Adsense::where('from_ad', $ad_id)->where('referer', $site_name)->increment('clicks', 1);
                } else {
                    $adsenseOther = [
                        'from_ad' => $ad_id,
                        'referer' => $site_name,
                        'clicks' => 1
                    ];

                    Adsense::insert($adsenseOther);
                }

                $ad_info = Ad::where('ad_id', $ad_id)->first();

                $ad_info = $ad_info ? $ad_info->toArray() : [];

                /* 跳转到广告的链接页面 */
                if (!empty($ad_info['ad_link'])) {
                    $uri = (strpos($ad_info['ad_link'], 'http://') === false && strpos($ad_info['ad_link'], 'https://') === false) ? $this->dsc->http() . urldecode($ad_info['ad_link']) : urldecode($ad_info['ad_link']);
                } else {
                    $uri = url('/');
                }

                return dsc_header("Location: $uri\n");
            }
        }
    }
}
