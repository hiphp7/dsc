<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Image;
use App\Models\Goods;
use App\Repositories\Common\BaseRepository;
use App\Services\Goods\GoodsManageService;
use App\Services\PictureBatch\PictureBatchManageService;

/**
 * 图片批量处理程序
 */
class PictureBatchController extends InitController
{
    protected $goodsManageService;
    protected $baseRepository;
    protected $pictureBatchManageService;

    public function __construct(
        GoodsManageService $goodsManageService,
        BaseRepository $baseRepository,
        PictureBatchManageService $pictureBatchManageService
    )
    {
        $this->goodsManageService = $goodsManageService;
        $this->baseRepository = $baseRepository;
        $this->pictureBatchManageService = $pictureBatchManageService;
    }

    public function index()
    {
        load_helper('goods', 'admin');

        $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => '12_batch_pic']);

        /* 权限检查 */
        admin_priv('picture_batch');

        if (empty($_GET['is_ajax'])) {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['12_batch_pic']);
            $goods_id = '';
            set_default_filter($goods_id); //设置默认筛选

            return $this->smarty->display('picture_batch.dwt');
        } elseif (!empty($_GET['get_goods'])) {
            $brand_id = intval($_GET['brand_id']);
            $cat_id = intval($_GET['cat_id']);
            $res = Goods::whereRaw(1);

            if (!empty($cat_id)) {
                $cat_id = $this->baseRepository->getExplode($cat_id);
                $res = $res->whereIn('cat_id', $cat_id);
            }
            if (!empty($brand_id)) {
                $res = $res->where('brand_id', $brand_id);
            }
            $res = $res->limit(50);
            $goods_list = $this->baseRepository->getToArrayGet($res);
            return response()->json($goods_list);
        } else {
            $proc_thumb = (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0);
            $do_album = empty($_GET['do_album']) ? 0 : 1;
            $do_icon = empty($_GET['do_icon']) ? 0 : 1;

            $goods_id = empty($_GET['goods_id']) ? [] : explode(",", $_GET['goods_id']); //by wu
            $brand_id = intval($_GET['brand_id']);
            $cat_id = intval($_GET['cat_id']);

            $module_no = 0;

            if ($do_album == 1 and $do_icon == 0) {
                $module_no = 1;
            }

            /* 设置最长执行时间为5分钟 */
            @set_time_limit(300);

            if (isset($_GET['start'])) {
                $page_size = 50; // 默认50张/页
                $thumb = empty($_GET['thumb']) ? 0 : 1;
                $watermark = empty($_GET['watermark']) ? 0 : 1;
                $change = empty($_GET['change']) ? 0 : 1;
                $silent = empty($_GET['silent']) ? 0 : 1;

                /* 检查GD */
                if (app(Image::class)->gd_version() < 1) {
                    return make_json_error($GLOBALS['_LANG']['missing_gd']);
                }

                /* 如果需要添加水印，检查水印文件 */
                if ((!empty($GLOBALS['_CFG']['watermark'])) && ($GLOBALS['_CFG']['watermark_place'] > 0) && $watermark && (!app(Image::class)->validate_image($GLOBALS['_CFG']['watermark']))) {
                    return make_json_error(app(Image::class)->error_msg());
                }
                $title = '';

                if (isset($_GET['total_icon'])) {
                    $count = $this->pictureBatchManageService->getGoodsCount($goods_id, $cat_id, $brand_id);
                    $title = sprintf($GLOBALS['_LANG']['goods_format'], $count, $page_size);
                }

                if (isset($_GET['total_album'])) {

                    $count = $this->pictureBatchManageService->getGoodsGallery($goods_id, $cat_id, $brand_id);

                    $title = sprintf('&nbsp;' . $GLOBALS['_LANG']['gallery_format'], $count, $page_size);
                    $module_no = 1;
                }
                $result = ['error' => 0, 'message' => '', 'content' => '', 'module_no' => $module_no, 'done' => 1, 'title' => $title, 'page_size' => $page_size,
                    'page' => 1, 'thumb' => $thumb, 'watermark' => $watermark, 'total' => 1, 'change' => $change, 'silent' => $silent,
                    'do_album' => $do_album, 'do_icon' => $do_icon, 'goods_id' => $goods_id, 'brand_id' => $brand_id, 'cat_id' => $cat_id,
                    'row' => ['new_page' => sprintf($GLOBALS['_LANG']['page_format'], 1),
                        'new_total' => sprintf($GLOBALS['_LANG']['total_format'], ceil($count / $page_size)),
                        'new_time' => $GLOBALS['_LANG']['wait'],
                        'cur_id' => 'time_1']];

                return response()->json($result);
            } else {
                $result = ['error' => 0, 'message' => '', 'content' => '', 'done' => 2, 'do_album' => $do_album, 'do_icon' => $do_icon, 'goods_id' => $goods_id, 'brand_id' => $brand_id, 'cat_id' => $cat_id];
                $result['thumb'] = empty($_GET['thumb']) ? 0 : 1;
                $result['watermark'] = empty($_GET['watermark']) ? 0 : 1;
                $result['change'] = empty($_GET['change']) ? 0 : 1;
                $result['page_size'] = empty($_GET['page_size']) ? 100 : intval($_GET['page_size']);
                $result['module_no'] = empty($_GET['module_no']) ? 0 : intval($_GET['module_no']);
                $result['page'] = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $result['total'] = isset($_GET['total']) ? intval($_GET['total']) : 1;
                $result['silent'] = empty($_GET['silent']) ? 0 : 1;

                if ($result['silent']) {
                    $err_msg = [];
                }

                /*------------------------------------------------------ */
                //-- 商品图片
                /*------------------------------------------------------ */
                if ($result['module_no'] == 0) {
                    $count = $this->pictureBatchManageService->getGoodsCount($goods_id, $cat_id, $brand_id);
                    /* 页数在许可范围内 */
                    if ($result['page'] <= ceil($count / $result['page_size'])) {
                        $start_time = gmtime(); //开始执行时间

                        /* 开始处理 */
                        if ($proc_thumb) {
                            $this->pictureBatchManageService->processImageEx($result['page'], $result['page_size'], $result['module_no'], $result['thumb'], $result['watermark'], $result['change'], $result['silent'], $goods_id, $cat_id, $brand_id);
                        } else {
                            $this->pictureBatchManageService->processImage($result['page'], $result['page_size'], $result['module_no'], $result['thumb'], $result['watermark'], $result['change'], $result['silent'], $goods_id, $cat_id, $brand_id);
                        }
                        $end_time = gmtime();
                        $result['row']['pre_id'] = 'time_' . $result['total'];
                        $result['row']['pre_time'] = ($end_time > $start_time) ? $end_time - $start_time : 1;
                        $result['row']['pre_time'] = sprintf($GLOBALS['_LANG']['time_format'], $result['row']['pre_time']);
                        $result['row']['cur_id'] = 'time_' . ($result['total'] + 1);
                        $result['page']++; // 新行
                        $result['row']['new_page'] = sprintf($GLOBALS['_LANG']['page_format'], $result['page']);
                        $result['row']['new_total'] = sprintf($GLOBALS['_LANG']['total_format'], ceil($count / $result['page_size']));
                        $result['row']['new_time'] = $GLOBALS['_LANG']['wait'];
                        $result['total']++;
                    } else {
                        --$result['total'];
                        --$result['page'];
                        $result['done'] = 0;
                        $result['message'] = ($do_album) ? '' : $GLOBALS['_LANG']['done'];
                        /* 清除缓存 */
                        clear_cache_files();
                        return response()->json($result);
                    }
                } elseif ($result['module_no'] == 1 && $result['do_album'] == 1) {
                    //商品相册
                    $count = $this->pictureBatchManageService->getGoodsGallery($goods_id, $cat_id, $brand_id);

                    if ($result['page'] <= ceil($count / $result['page_size'])) {
                        $start_time = gmtime(); // 开始执行时间
                        /* 开始处理 */
                        if ($proc_thumb) {
                            $this->pictureBatchManageService->processImageEx($result['page'], $result['page_size'], $result['module_no'], $result['thumb'], $result['watermark'], $result['change'], $result['silent'], $goods_id, $cat_id, $brand_id);
                        } else {
                            $this->pictureBatchManageService->processImage($result['page'], $result['page_size'], $result['module_no'], $result['thumb'], $result['watermark'], $result['change'], $result['silent'], $goods_id, $cat_id, $brand_id);
                        }
                        $end_time = gmtime();

                        $result['row']['pre_id'] = 'time_' . $result['total'];
                        $result['row']['pre_time'] = ($end_time > $start_time) ? $end_time - $start_time : 1;
                        $result['row']['pre_time'] = sprintf($GLOBALS['_LANG']['time_format'], $result['row']['pre_time']);
                        $result['row']['cur_id'] = 'time_' . ($result['total'] + 1);
                        $result['page']++;
                        $result['row']['new_page'] = sprintf($GLOBALS['_LANG']['page_format'], $result['page']);
                        $result['row']['new_total'] = sprintf($GLOBALS['_LANG']['total_format'], ceil($count / $result['page_size']));
                        $result['row']['new_time'] = $GLOBALS['_LANG']['wait'];

                        $result['total']++;
                    } else {
                        $result['row']['pre_id'] = 'time_' . $result['total'];
                        $result['row']['cur_id'] = 'time_' . ($result['total'] + 1);
                        $result['row']['new_page'] = sprintf($GLOBALS['_LANG']['page_format'], $result['page']);
                        $result['row']['new_total'] = sprintf($GLOBALS['_LANG']['total_format'], ceil($count / $result['page_size']));
                        $result['row']['new_time'] = $GLOBALS['_LANG']['wait'];

                        /* 执行结束 */
                        $result['done'] = 0;
                        $result['message'] = $GLOBALS['_LANG']['done'];
                        /* 清除缓存 */
                        clear_cache_files();
                    }
                }

                if ($result['silent'] && $err_msg) {
                    $result['content'] = implode('<br />', $err_msg);
                }

                return response()->json($result);
            }
        }
    }
}
