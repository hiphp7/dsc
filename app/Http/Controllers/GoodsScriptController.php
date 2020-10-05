<?php

namespace App\Http\Controllers;

use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsService;

/**
 * 生成商品列表
 */
class GoodsScriptController extends InitController
{
    protected $goodsService;
    protected $dscRepository;

    public function __construct(
        GoodsService $goodsService,
        DscRepository $dscRepository
    )
    {
        $this->goodsService = $goodsService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        $charset = addslashes(request()->input('charset', EC_CHARSET));
        $type = addslashes(request()->input('type', ''));
        $type = $type ? 'collection' : '';

        if (strtolower($charset) == 'gb2312') {
            $charset = 'gbk';
        }
        header('content-type: application/x-javascript; charset=' . ($charset == 'UTF8' ? 'utf-8' : $charset));

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

        /* End */

        /*------------------------------------------------------ */
        //-- 判断是否存在缓存，如果存在则调用缓存，反之读取相应内        容
        /*------------------------------------------------------ */
        /* 缓存编号 */
        $cache_id = sprintf('%X', crc32(request()->server('QUERY_STRING')));

        $uid = (int)request()->input('u', 0);
        $cat_id = (int)request()->input('cat_id', 0);
        $goods_num = (int)request()->input('goods_num', 10);
        $rows_num = (int)request()->input('rows_num', 1);
        $intro_type = isset($intro_type) && !empty($intro_type) ? addslashes($intro_type) : '';

        $tpl = storage_public(DATA_DIR . '/goods_script.html');
        if (!$this->smarty->is_cached($tpl, $cache_id)) {
            $time = gmtime();
            /* 根据参数生成查询语句 */
            if ($type == '') {
                $sitename = addslashes(request()->input('sitename', ''));
                $_charset = addslashes(request()->input('charset', ''));
                $_from = (!empty($_charset) && $_charset != 'UTF8') ? urlencode(dsc_iconv('UTF-8', 'GBK', $sitename)) : urlencode(@$sitename);

                $goods_url = url('/') . '/' . 'affiche.php?ad_id=-1&amp;from=' . $_from . '&amp;goods_id=';

                $where = [
                    'is_delete' => 0,
                    'is_on_sale' => 1,
                    'is_alone_sale' => 1,
                    'cat_id' => $cat_id,
                    'time' => $time,
                    'intro_type' => $intro_type
                ];

                if (!empty($intro_type)) {
                    if ($intro_type == 'is_best' || $intro_type == 'is_new' || $intro_type == 'is_hot' || $intro_type == 'is_promote' || $intro_type == 'is_random') {
                        if ($intro_type == 'is_random') {
                            $where['sort_rnd'] = "RND()";
                        } else {
                            $where['sort'] = "add_time";
                            $where['order'] = "desc";
                            $where['intro_type'] = $intro_type;
                        }
                    }
                }
            } elseif ($type == 'collection') {
                $goods_url = asset('/') . "goods.php?u=$uid&id=";

                $where['collect'] = "collect_goods";
                $where['user_id'] = $uid;
            }

            $where['size'] = $goods_num;
            $where['warehouse_id'] = $warehouse_id;
            $where['area_id'] = $area_id;
            $where['area_city'] = $area_city;

            $res = $this->goodsService->getGoodsList($where);

            $goods_list = [];

            if ($res) {
                foreach ($res as $goods) {
                    if ($goods['promote_price'] > 0) {
                        $goods['goods_price'] = $goods['promote_price'];
                    } else {
                        $goods['goods_price'] = $goods['shop_price'];
                    }

                    // 转换编码
                    $goods['goods_price'] = $this->dscRepository->getPriceFormat($goods['goods_price']);
                    if ($charset != EC_CHARSET) {
                        if (EC_CHARSET == 'gbk') {
                            $tmp_goods_name = htmlentities($goods['goods_name'], ENT_QUOTES, 'gb2312');
                        } else {
                            $tmp_goods_name = htmlentities($goods['goods_name'], ENT_QUOTES, EC_CHARSET);
                        }
                        $goods['goods_name'] = dsc_iconv(EC_CHARSET, $charset, $tmp_goods_name);
                        $goods['goods_price'] = dsc_iconv(EC_CHARSET, $charset, $goods['goods_price']);
                    }
                    $goods['goods_name'] = $this->config['goods_name_length'] > 0 ? $this->dscRepository->subStr($goods['goods_name'], $this->config['goods_name_length']) : $goods['goods_name'];
                    $goods['goods_thumb'] = $this->dscRepository->getImagePath($goods['goods_thumb']);
                    $goods_list[] = $goods;
                }
            }

            /* 排列方式 */
            $arrange = request()->input('arrange', 'h');
            $arrange = !in_array($arrange, ['h', 'v']) ? 'h' : $arrange;

            /* 排列显示条目个数 */
            $goods_items = [];
            if ($goods_list) {
                if ($arrange == 'h') {
                    $goods_items = array_chunk($goods_list, $rows_num);
                } else {
                    $columns_num = ceil($goods_num / $rows_num);
                    $goods_items = array_chunk($goods_list, $columns_num);
                }
            }

            $this->smarty->assign('goods_list', $goods_items);


            /* 是否需要图片 */
            $need_image = request()->input('need_image');
            $need_image = empty($need_image) || $need_image == 'true' ? 1 : 0;
            $this->smarty->assign('need_image', $need_image);

            /* 图片大小 */
            $this->smarty->assign('thumb_width', intval($this->config['thumb_width']));
            $this->smarty->assign('thumb_height', intval($this->config['thumb_height']));

            /* 网站根目录 */
            $this->smarty->assign('url', url('/') . '/');

            /* 商品页面连接 */
            $this->smarty->assign('goods_url', $goods_url);
        }
        $output = $this->smarty->fetch($tpl, $cache_id);
        $output = str_replace("\r", '', $output);
        $output = str_replace("\n", '', $output);

        echo "document.write('$output');";
    }
}
