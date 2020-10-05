<?php

namespace App\Http\Controllers;

use App\Models\HomeTemplates;
use App\Models\RsRegion;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\FileSystemsRepository;
use App\Services\Article\ArticleCommonService;
use App\Services\Common\TemplateService;
use App\Services\Goods\GoodsGuessService;

/**
 * 首页文件
 */
class IndexController extends InitController
{
    protected $goodsGuessService;
    protected $templateService;
    protected $fileSystemsRepository;
    protected $dscRepository;
    protected $baseRepository;
    protected $homeindex;
    protected $config;
    protected $articleCommonService;

    public function __construct(
        GoodsGuessService $goodsGuessService,
        TemplateService $templateService,
        FileSystemsRepository $fileSystemsRepository,
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        ArticleCommonService $articleCommonService
    )
    {
        $this->goodsGuessService = $goodsGuessService;
        $this->templateService = $templateService;
        $this->fileSystemsRepository = $fileSystemsRepository;
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->config = $this->config();
        $this->articleCommonService = $articleCommonService;
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
        /* End */

        load_helper('visual');

        $user_id = session('user_id', 0);

        /* 跳转H5 start */
        $Loaction = 'mobile/';
        $uachar = $this->dscRepository->getReturnMobile($Loaction);

        if ($uachar) {
            return $uachar;
        }
        /* 跳转H5 end */

        //判断可视化模板
        //预览传值
        $suffix = addslashes(request()->input('suffix', ''));
        $preview = 1;

        $defalut_template = $this->config['template'] ?? 'ecmoban_dsc2017';

        //不是预览且开启可视化后调用可视化模板
        if (empty($suffix) && isset($this->config['openvisual']) && $this->config['openvisual'] == 1) {
            $rs_id = 0;
            if (isset($this->config['region_store_enabled']) && $this->config['region_store_enabled'] == 1) {

                //获取卖场rs_id
                $rs_id = RsRegion::where('region_id', $this->city_id)->value('rs_id');
                $rs_id = $rs_id ? $rs_id : 0;

                $count_temp = HomeTemplates::where('rs_id', $rs_id)->count();

                if ($count_temp == 0 && $rs_id > 0) {
                    $des = storage_public(DATA_DIR . '/home_templates/' . $this->config['template']);
                    $new_suffix = get_new_dir_name(0, $des);

                    $enableTem = HomeTemplates::where('rs_id', 0)->where('theme', $this->config['template'])->where('is_enable', 1)->value('code');
                    if (!empty($new_suffix) && $enableTem) {
                        //新建目录
                        if (!is_dir($des . "/" . $new_suffix)) {
                            make_dir($des . "/" . $new_suffix);
                        }
                        recurse_copy($des . "/" . $enableTem, $des . "/" . $new_suffix, 1);

                        $HomeTempInsert = [
                            'rs_id' => $rs_id,
                            'code' => $new_suffix,
                            'is_enable' => 1,
                            'theme' => $this->config['template']
                        ];
                        HomeTemplates::insert($HomeTempInsert);
                    }
                }
            }

            $enableTem = HomeTemplates::where('rs_id', $rs_id)->where('theme', $defalut_template)->where('is_enable', 1)->value('code');
            $suffix = !empty($enableTem) ? trim($enableTem) : '';
            $preview = 0;
        }

        $dir = storage_public(DATA_DIR . '/home_templates/' . $defalut_template . '/' . $suffix);
        if ($preview == 1) {
            $dir_temp = storage_public(DATA_DIR . '/home_templates/' . $defalut_template . '/' . $suffix . "/temp");
            if (is_dir($dir_temp)) {
                $dir = $dir_temp;
            }
        }

        $this->smarty->assign('cfg_bonus_adv', $this->config['bonus_adv'] ?? 0);

        /* 缓存编号 */
        $cache_id = sprintf('%X', crc32(session('user_rank') . '-' . $this->config['lang']));

        /* ------------------------------------------------------ */
        //-- 判断是否存在缓存，如果存在则调用缓存，反之读取相应内        容
        /* ------------------------------------------------------ */
        if (!empty($suffix) && $this->fileSystemsRepository->dirExists($dir)) {

            /**
             * 首页可视化
             * 下载OSS模板文件
             */
            get_down_hometemplates($suffix);

            $this->homeindex = app(HomeindexController::class);

            $this->homeindex->suffix = $suffix;
            $this->homeindex->preview = $preview;
            $this->homeindex->dir = $dir;
            $this->homeindex->warehouse_id = $warehouse_id;
            $this->homeindex->area_id = $area_id;
            $this->homeindex->area_city = $area_city;
            $this->homeindex->config = $this->config;
            $this->homeindex->articleCommonService = $this->articleCommonService;
            $this->homeindex->dscRepository = $this->dscRepository;

            $home = $this->homeindex;

            $content = cache()->remember('index.home.' . $cache_id, $this->config['cache_time'], function () use ($home) {
                return $home->index();
            });
        } else {
            $content = cache()->remember('index.dwt' . $cache_id, $this->config['cache_time'], function () use ($warehouse_id, $area_id, $area_city, $user_id, $cache_id) {
                assign_template();

                $position = assign_ur_here();
                $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置

                //获取seo start
                $seo = get_seo_words('index');

                if ($seo) {
                    foreach ($seo as $key => $value) {
                        $seo[$key] = str_replace(['{sitename}', '{key}', '{description}'], [$position['title'], $this->config['shop_keywords'], $this->config['shop_desc']], $value);
                    }
                }

                if (isset($seo['keywords']) && !empty($seo['keywords'])) {
                    $this->smarty->assign('keywords', htmlspecialchars($seo['keywords']));
                } else {
                    $this->smarty->assign('keywords', htmlspecialchars($this->config['shop_keywords']));
                }

                if (isset($seo['description']) && !empty($seo['description'])) {
                    $this->smarty->assign('description', htmlspecialchars($seo['description']));
                } else {
                    $this->smarty->assign('description', htmlspecialchars($this->config['shop_desc']));
                }

                if (isset($seo['title']) && !empty($seo['title'])) {
                    $this->smarty->assign('page_title', htmlspecialchars($seo['title']));
                } else {
                    $this->smarty->assign('page_title', $position['title']);
                }
                //获取seo end

                /* meta information */
                $this->smarty->assign('flash_theme', $this->config['flash_theme']);  // Flash轮播图片模板

                $this->smarty->assign('feed_url', ($this->config['rewrite'] == 1) ? 'feed.xml' : 'feed.php'); // RSS URL

                /**小图 start**/
                $ad_arr = '';
                $index_ad = '';
                $cat_goods_banner = '';
                $cat_goods_hot = '';
                $index_brand_banner = '';
                $index_brand_street = '';
                $index_group_banner = '';
                $index_banner_group = '';
                $recommend_category = '';
                $index_expert_field = '';
                $recommend_merchants = '';
                for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                    $ad_arr .= "'c" . $i . ","; // 分类广告位
                    $index_ad .= "'index_ad" . $i . ","; //首页轮播图
                    $cat_goods_banner .= "'cat_goods_banner" . $i . ","; //首页楼层轮播图
                    $cat_goods_hot .= "'cat_goods_hot" . $i . ","; //首页楼层轮播图
                    $index_brand_banner .= "'index_brand_banner" . $i . ","; //首页品牌街轮播图
                    $index_brand_street .= "'index_brand_street" . $i . ","; //首页品牌街品牌
                    $index_group_banner .= "'index_group_banner" . $i . ","; //首页团购活动
                    $index_banner_group .= "'index_banner_group" . $i . ","; //首页轮播图团购促销

                    $recommend_category .= "'recommend_category" . $i . ","; //新首页推荐分类广告 liu
                    $index_expert_field .= "'expert_field_ad" . $i . ","; //新首页达人专区广告 liu
                    $recommend_merchants .= "'recommend_merchants" . $i . ","; //新首页推荐店铺广告 liu
                }

                $this->smarty->assign('adarr', $ad_arr);
                $this->smarty->assign('index_ad', $index_ad);

                $this->smarty->assign('rec_cat', $recommend_category);
                $this->smarty->assign('expert_field', $index_expert_field);
                $this->smarty->assign('recommend_merchants', $recommend_merchants);

                $this->smarty->assign('cat_goods_banner', $cat_goods_banner);
                $this->smarty->assign('cat_goods_hot', $cat_goods_hot);
                $this->smarty->assign('index_brand_banner', $index_brand_banner);
                $this->smarty->assign('index_brand_street', $index_brand_street);
                $this->smarty->assign('index_group_banner', $index_group_banner);
                $this->smarty->assign('index_banner_group', $index_banner_group);
                $this->smarty->assign('top_banner', 'top_banner');

                $this->smarty->assign('warehouse_id', $warehouse_id);
                $this->smarty->assign('area_id', $area_id);
                /**小图 end**/

                $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助

                $bonushome = '';
                for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                    $bonushome .= "'bonushome" . $i . ","; //首页楼层左侧广告图
                }
                $this->smarty->assign('bonushome', $bonushome);

                $floor_data = $this->templateService->getFloorData('index');
                $this->smarty->assign('floor_data', $floor_data);

                /**
                 * Start
                 *
                 * 猜你喜欢商品
                 */
                $guess_num = 10;
                $where = [
                    'warehouse_id' => $warehouse_id,
                    'area_id' => $area_id,
                    'area_city' => $area_city,
                    'user_id' => $user_id,
                    'history' => 1,
                    'page' => 1,
                    'limit' => $guess_num
                ];
                $guess_goods = $this->goodsGuessService->getGuessGoods($where);

                $this->smarty->assign('guess_goods', $guess_goods);
                /* End */

                /* 页面中的动态内容 */
                assign_dynamic('index', $warehouse_id, $area_id);

                return $this->smarty->display('index.dwt', $cache_id);
            });
        }

        return $content;
    }
}
