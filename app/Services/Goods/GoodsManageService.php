<?php

namespace App\Services\Goods;

use App\Libraries\Image;
use App\Models\Cart;
use App\Models\CollectGoods;
use App\Models\Comment;
use App\Models\Goods;
use App\Models\GoodsArticle;
use App\Models\GoodsAttr;
use App\Models\GoodsCat;
use App\Models\GoodsGallery;
use App\Models\GoodsLibGallery;
use App\Models\GroupbuyGoods;
use App\Models\GroupGoods;
use App\Models\LinkGoods;
use App\Models\MemberPrice;
use App\Models\OrderInfo;
use App\Models\PresaleActivity;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsChangelog;
use App\Models\ProductsWarehouse;
use App\Models\SuppliersGoodsGallery;
use App\Models\Tag;
use App\Models\VirtualCard;
use App\Models\WarehouseAreaAttr;
use App\Models\WarehouseAreaGoods;
use App\Models\WarehouseAttr;
use App\Models\WarehouseGoods;
use App\Models\Wholesale;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Category\CategoryService;

class GoodsManageService
{
    protected $categoryService;
    protected $baseRepository;
    protected $config;
    protected $image;
    protected $dscRepository;
    protected $timeRepository;

    public function __construct(
        CategoryService $categoryService,
        BaseRepository $baseRepository,
        Image $image,
        DscRepository $dscRepository,
        TimeRepository $timeRepository
    )
    {
        $this->categoryService = $categoryService;
        $this->baseRepository = $baseRepository;
        $this->image = $image;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->timeRepository = $timeRepository;
    }

    /**
     * 取得推荐类型列表
     *
     * @return array 推荐类型列表
     * @throws \Exception
     */
    public function getIntroList()
    {
        return [
            'is_best' => lang('manage/goods.is_best'),
            'is_new' => lang('manage/goods.is_new'),
            'is_hot' => lang('manage/goods.is_hot'),
            'store_best' => lang('manage/goods.store_best'),
            'store_new' => lang('manage/goods.store_new'),
            'store_hot' => lang('manage/goods.store_hot'),
            'is_promote' => lang('manage/goods.is_promote'),
            'all_type' => lang('manage/goods.all_type')
        ];
    }

    /**
     * 取得重量单位列表
     * @return  array   重量单位列表
     */
    public function getUnitList()
    {
        return [
            '1' => lang('manage/goods.unit_kg'),
            '0.001' => lang('manage/goods.unit_g')
        ];
    }

    /**
     * 获取一层分类
     *
     * @param int $cat_id
     * @param int $cat_level
     * @param array $seller_shop_cat
     * @return \App\Services\Category\mix|string
     */
    public function catListOne($cat_id = 0, $cat_level = 0, $seller_shop_cat = [], $id = 'cat_list', $onchange = 'catList')
    {
        if ($cat_id == 0) {
            $arr = $this->categoryService->catList($cat_id, 0, 0, 'category', $seller_shop_cat);
            return $arr;
        } else {
            $arr = $this->categoryService->catList($cat_id);

            foreach ($arr as $key => $value) {
                if ($key == $cat_id) {
                    unset($arr[$cat_id]);
                }
            }

            // 拼接字符串
            $str = '';
            if ($arr) {
                $cat_level++;

                $str .= "<select name='catList" . $cat_level . "' id='" . $id . $cat_level . "' onchange='" . $onchange . "(this.value, " . $cat_level . ")' class='select'>";
                $str .= "<option value='0'>全部分类</option>";

                foreach ($arr as $key1 => $value1) {
                    $str .= "<option value='" . $value1['cat_id'] . "'>" . $value1['cat_name'] . "</option>";
                }
                $str .= "</select>";
            }

            return $str;
        }
    }

    /**
     * 格式化商品图片名称（按目录存储）
     *
     * @param $type
     * @param $goods_id
     * @param $source_img
     * @param string $position
     * @param string $dir
     * @param string $up_type
     * @return bool|string
     */
    public function reformatImageName($type, $goods_id, $source_img, $position = '', $dir = IMAGE_DIR, $up_type = '')
    {
        $time = $this->timeRepository->getGmTime();

        $rand_name = $time . sprintf("%03d", mt_rand(1, 999));
        $img_ext = substr($source_img, strrpos($source_img, '.'));

        if ($up_type == 'album') {

            if (!file_exists(storage_public($dir))) {
                make_dir(storage_public($dir));
            }

            if (!file_exists(storage_public($dir . '/original_img'))) {
                make_dir(storage_public($dir . '/original_img'));
            }

            if (!file_exists(storage_public($dir . '/thumb_img'))) {
                make_dir(storage_public($dir . '/thumb_img'));
            }

            if (!file_exists(storage_public($dir . '/images'))) {
                make_dir(storage_public($dir . '/images'));
            }
        } else {
            $sub_dir = $this->timeRepository->getLocalDate('Ym', $time);

            if (!file_exists(storage_public($dir . '/' . $sub_dir))) {
                make_dir(storage_public($dir . '/' . $sub_dir));
            }

            if (!file_exists(storage_public($dir . '/' . $sub_dir . '/source_img'))) {
                make_dir(storage_public($dir . '/' . $sub_dir . '/source_img'));
            }

            if (!file_exists(storage_public($dir . '/' . $sub_dir . '/goods_img'))) {
                make_dir(storage_public($dir . '/' . $sub_dir . '/goods_img'));
            }

            if (!file_exists(storage_public($dir . '/' . $sub_dir . '/thumb_img'))) {
                make_dir(storage_public($dir . '/' . $sub_dir . '/thumb_img'));
            }
        }

        switch ($type) {
            case 'goods':
                $img_name = $goods_id . '_G_' . $rand_name;
                break;
            case 'goods_thumb':
                $img_name = $goods_id . '_thumb_G_' . $rand_name;
                break;
            case 'gallery':
                $img_name = $goods_id . '_P_' . $rand_name;
                break;
            case 'gallery_thumb':
                $img_name = $goods_id . '_thumb_P_' . $rand_name;
                break;
        }

        if (strpos($source_img, 'temp') !== false) {
            $ex_img = explode('temp', $source_img);
            $source_img = "temp" . $ex_img[1];
        }

        if ($up_type == 'album') {
            if ($position == 'source') {
                if ($this->moveImageFile($source_img, storage_public($dir . '/images/' . $img_name . $img_ext))) {
                    return $dir . '/images/' . $img_name . $img_ext;
                }
            } elseif ($position == 'thumb') {
                if ($this->moveImageFile($source_img, storage_public($dir . '/thumb_img/' . $img_name . $img_ext))) {
                    return $dir . '/thumb_img/' . $img_name . $img_ext;
                }
            } else {
                if ($this->moveImageFile($source_img, storage_public($dir . '/original_img/' . $img_name . $img_ext))) {
                    return $dir . '/original_img/' . $img_name . $img_ext;
                }
            }
        } else {
            if ($position == 'source') {
                if ($this->moveImageFile($source_img, storage_public($dir . '/' . $sub_dir . '/source_img/' . $img_name . $img_ext))) {
                    return $dir . '/' . $sub_dir . '/source_img/' . $img_name . $img_ext;
                }
            } elseif ($position == 'thumb') {
                if ($this->moveImageFile($source_img, storage_public($dir . '/' . $sub_dir . '/thumb_img/' . $img_name . $img_ext))) {
                    return $dir . '/' . $sub_dir . '/thumb_img/' . $img_name . $img_ext;
                }
            } else {
                if ($this->moveImageFile($source_img, storage_public($dir . '/' . $sub_dir . '/goods_img/' . $img_name . $img_ext))) {
                    return $dir . '/' . $sub_dir . '/goods_img/' . $img_name . $img_ext;
                }
            }
        }

        return false;
    }

    /**
     * @param $source
     * @param $dest
     * @return bool
     */
    public function moveImageFile($source, $dest)
    {
        if (@copy($source, $dest)) {
            if (file_exists($source)) {
                @unlink($source);
            }
            return true;
        }
        return false;
    }

    /**
     * 相册统计
     *
     * @param int $goods_id
     * @param int $is_lib
     * @return mixed
     */
    public function getGoodsGalleryCount($goods_id = 0, $is_lib = 0)
    {
        if ($is_lib == 1) {
            $res = GoodsLibGallery::whereRaw(1);
        } elseif ($is_lib == 2) {
            $res = SuppliersGoodsGallery::whereRaw(1);
        } else {
            $res = GoodsGallery::whereRaw(1);
        }

        $res = $res->where('goods_id', $goods_id);

        $count = $res->count();

        return $count;
    }

    /**
     * 为某商品生成唯一的货号
     * @param int $goods_id 商品编号
     * @return  string  唯一的货号
     */
    public function generateGoodSn($goods_id, $is_table = 0)
    {
        $goods_sn = $this->config['sn_prefix'] . str_repeat('0', 6 - strlen($goods_id)) . $goods_id;

        if ($is_table == 1) {
            $sn_list = Wholesale::whereRaw(1);
        } elseif ($is_table == 2) {
            $sn_list = GroupbuyGoods::whereRaw(1);
        } else {
            $sn_list = Goods::whereRaw(1);
        }

        $goods_sn = mysql_like_quote($goods_sn);
        $sn_list = $sn_list->where('goods_sn', 'like', $goods_sn . '%')
            ->where('goods_id', '<>', $goods_id)
            ->orderByRaw('LENGTH(goods_sn) desc');
        $sn_list = $this->baseRepository->getToArrayGet($sn_list);
        $sn_list = $this->baseRepository->getKeyPluck($sn_list, 'goods_sn');

        if ($goods_sn && in_array($goods_sn, $sn_list)) {
            $max = pow(10, strlen($sn_list[0]) - strlen($goods_sn) + 1) - 1;
            $new_sn = $goods_sn . mt_rand(0, $max);
            while (in_array($new_sn, $sn_list)) {
                $new_sn = $goods_sn . mt_rand(0, $max);
            }
            $goods_sn = $new_sn;
        }

        return $goods_sn;
    }

    /**
     * 添加商品相册
     * 保存某商品的相册图片
     *
     * @param $goods_id
     * @param $image_files
     * @param $image_descs
     * @param $image_urls
     * @param int $single_id
     * @param int $files_type
     * @param $is_ajax
     * @param int $gallery_count
     * @param int $is_lib
     * @param string $htm_maxsize
     */
    public function handleGalleryImageAdd($goods_id, $image_files, $image_descs, $image_urls, $single_id = 0, $files_type = 0, $is_ajax, $gallery_count = 0, $is_lib = 0, $htm_maxsize = '2M')
    {
        $admin_id = get_admin_id();
        $admin_temp_dir = "seller";
        $admin_temp_dir = storage_public("temp" . '/' . $admin_temp_dir . '/' . "admin_" . $admin_id);

        // 如果目标目录不存在，则创建它
        if (!file_exists($admin_temp_dir)) {
            make_dir($admin_temp_dir);
        }
        $thumb_img_id = [];

        /* 是否处理缩略图 */
        $proc_thumb = (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0) ? false : true;
        foreach ($image_descs as $key => $img_desc) {
            /* 是否成功上传 */
            $flag = false;
            if (isset($image_files['error'])) {
                if ($image_files['error'][$key] == 0) {
                    $flag = true;
                }
            } else {
                if ($image_files['tmp_name'][$key] != 'none' && $image_files['tmp_name'][$key]) {
                    $flag = true;
                }
            }
            if ($flag) {
                $upload = [
                    'name' => $image_files['name'][$key],
                    'type' => $image_files['type'][$key],
                    'tmp_name' => $image_files['tmp_name'][$key],
                    'size' => $image_files['size'][$key],
                ];
                if (isset($image_files['error'])) {
                    $upload['error'] = $image_files['error'][$key];
                }
                $img_original = $this->image->upload_image($upload, ['type' => 1]);
                if ($img_original === false) {
                    if ($is_ajax == 'ajax') {
                        $result['error'] = '1';
                        $result['massege'] = sprintf($GLOBALS['_LANG']['img_url_too_big'], $key + 1, $htm_maxsize);
                        return;
                    } else {
                        return sys_msg($this->image->error_msg(), 1, [], false);
                    }
                } else {
                    $img_original = storage_public($img_original);
                }
                $img_url = $img_original;

                // 生成缩略图
                if ($proc_thumb) {
                    $thumb_url = $this->image->make_thumb(['img' => $img_original, 'type' => 1], $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height']);
                    $thumb_url = is_string($thumb_url) ? $thumb_url : '';
                } else {
                    $thumb_url = $img_original;
                }

                // 如果服务器支持GD 则添加水印
                if ($proc_thumb && gd_version() > 0) {
                    $pos = strpos(basename($img_original), '.');
                    $newname = dirname($img_original) . '/' . $this->image->random_filename() . substr(basename($img_original), $pos);
                    copy($img_original, $newname);
                    $img_url = $newname;

                    $this->image->add_watermark($img_url, '', $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']);
                }

                /* 重新格式化图片名称 */
                if ($goods_id == 0) {
                    $img_original = $this->reformatImageName('gallery', $single_id, $img_original, 'source');
                    $img_url = $this->reformatImageName('gallery', $single_id, $img_url, 'goods');
                    $thumb_url = $this->reformatImageName('gallery_thumb', $single_id, $thumb_url, 'thumb');
                } else {
                    $img_original = $this->reformatImageName('gallery', $goods_id, $img_original, 'source');
                    $img_url = $this->reformatImageName('gallery', $goods_id, $img_url, 'goods');
                    $thumb_url = $this->reformatImageName('gallery_thumb', $goods_id, $thumb_url, 'thumb');
                }

                $other = [
                    'goods_id' => $goods_id,
                    'img_url' => $img_url,
                    'img_desc' => $gallery_count,
                    'thumb_url' => $thumb_url,
                    'img_original' => $img_original
                ];

                if ($is_lib != 2) {
                    if ($files_type == 0) {
                        $other['single_id'] = $single_id;
                    } elseif ($files_type = 1) {
                        $other['dis_id'] = $single_id;
                    }
                }

                if ($is_lib == 1) {
                    $thumb_img_id[] = GoodsLibGallery::insertGetId($other);
                } elseif ($is_lib == 2) {
                    $thumb_img_id[] = SuppliersGoodsGallery::insertGetId($other);
                } else {
                    $thumb_img_id[] = GoodsGallery::insertGetId($other);
                }

                /* 不保留商品原图的时候删除原图 */
                if ($proc_thumb && !$GLOBALS['_CFG']['retain_original_img'] && !empty($img_original)) {
                    if ($is_lib) {
                        $res = GoodsLibGallery::whereRaw(1);
                    } elseif ($is_lib == 2) {
                        $res = SuppliersGoodsGallery::whereRaw(1);
                    } else {
                        $res = GoodsGallery::whereRaw(1);
                    }

                    $res->where('goods_id', $goods_id)->update([
                        'img_original' => ''
                    ]);

                    dsc_unlink(storage_public($img_original));
                }
            } elseif (!empty($image_urls[$key]) && ($image_urls[$key] != $GLOBALS['_LANG']['img_file']) && ($image_urls[$key] != 'http://') && (strpos($image_urls[$key], 'http://') !== false || strpos($image_urls[$key], 'https://') !== false)) {
                if (get_http_basename($image_urls[$key], $admin_temp_dir)) {
                    $image_url = trim($image_urls[$key]);
                    //定义原图路径
                    $down_img = $admin_temp_dir . "/" . basename($image_url);

                    $img_wh = $this->image->get_width_to_height($down_img, $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height']);
                    $GLOBALS['_CFG']['image_width'] = isset($img_wh['image_width']) ? $img_wh['image_width'] : $GLOBALS['_CFG']['image_width'];
                    $GLOBALS['_CFG']['image_height'] = isset($img_wh['image_height']) ? $img_wh['image_height'] : $GLOBALS['_CFG']['image_height'];

                    $goods_img = $this->image->make_thumb(['img' => $down_img, 'type' => 1], $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height']);

                    // 生成缩略图
                    if ($proc_thumb) {
                        $thumb_url = $this->image->make_thumb(['img' => $down_img, 'type' => 1], $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height']);
                        $thumb_url = $this->reformatImageName('gallery_thumb', $goods_id, $thumb_url, 'thumb');
                    } else {
                        $thumb_url = $this->image->make_thumb(['img' => $down_img, 'type' => 1]);
                        $thumb_url = $this->reformatImageName('gallery_thumb', $goods_id, $thumb_url, 'thumb');
                    }

                    $img_original = $this->reformatImageName('gallery', $goods_id, $down_img, 'source');
                    $img_url = $this->reformatImageName('gallery', $goods_id, $goods_img, 'goods');

                    $other = [
                        'goods_id' => $goods_id,
                        'img_url' => $img_url,
                        'img_desc' => $gallery_count,
                        'thumb_url' => $thumb_url,
                        'img_original' => $img_original
                    ];

                    if ($is_lib != 2) {
                        if ($files_type == 0) {
                            $other['single_id'] = $single_id;
                        } elseif ($files_type = 1) {
                            $other['dis_id'] = $single_id;
                        }
                    }

                    if ($is_lib == 1) {
                        $thumb_img_id[] = GoodsLibGallery::insertGetId($other);
                    } elseif ($is_lib == 2) {
                        $thumb_img_id[] = SuppliersGoodsGallery::insertGetId($other);
                    } else {
                        $thumb_img_id[] = GoodsGallery::insertGetId($other);
                    }

                    @unlink($down_img);
                }
            }

            $this->dscRepository->getOssAddFile([$img_url, $thumb_url, $img_original]);
        }

        $id = session()->has('seller_id') && session('seller_id') ? session('seller_id') : session('admin_id', 0); // 商家后台兼容用

        if (!empty(session('thumb_img_id' . $id))) {
            $thumb_img_id = array_merge($thumb_img_id, session('thumb_img_id' . $id));
        }

        session()->put('thumb_img_id' . $id, $thumb_img_id);
    }


    /**
     * 从回收站删除多个商品
     * @param mix $goods_id 商品id列表：可以逗号格开，也可以是数组
     * @return  void
     */
    public function deleteGoods($goods_id = 0)
    {
        if (empty($goods_id)) {
            return;
        }

        /* 取得有效商品id */
        $goods_id = $this->baseRepository->getExplode($goods_id);
        $goods = Goods::whereIn('goods_id', $goods_id)
            ->where('is_delete', 1);
        $goods = $this->baseRepository->getToArrayGet($goods);
        $goods_id = $this->baseRepository->getKeyPluck($goods, 'goods_id');

        if (empty($goods_id)) {
            return;
        }

        /* 删除商品图片和轮播图片文件 */
        if ($goods) {
            $goods_thumb = $this->baseRepository->getKeyPluck($goods, 'goods_thumb');
            $goods_img = $this->baseRepository->getKeyPluck($goods, 'goods_img');
            $original_img = $this->baseRepository->getKeyPluck($goods, 'original_img');
            $goods_video = $this->baseRepository->getKeyPluck($goods, 'goods_video');

            $img = [
                $goods_thumb,
                $goods_img,
                $original_img,
                $goods_video
            ];

            $img = $this->baseRepository->getFlatten($img);

            $this->dscRepository->getOssDelFile($img);

            dsc_unlink($img, storage_public());
        }

        /* 删除商品 */
        Goods::whereIn('goods_id', $goods_id)->delete();

        /* 删除商品的货品记录 */
        Products::whereIn('goods_id', $goods_id)->delete();

        /* 删除商品相册的图片文件 */
        $goodsGallery = GoodsGallery::whereIn('goods_id', $goods_id);
        $goodsGallery = $this->baseRepository->getToArrayGet($goodsGallery);

        if ($goodsGallery) {
            $img_url = $this->baseRepository->getKeyPluck($goodsGallery, 'img_url');
            $thumb_url = $this->baseRepository->getKeyPluck($goodsGallery, 'thumb_url');
            $img_original = $this->baseRepository->getKeyPluck($goodsGallery, 'img_original');

            $img = [
                $img_url,
                $thumb_url,
                $img_original
            ];

            $img = $this->baseRepository->getFlatten($img);

            $this->dscRepository->getOssDelFile($img);

            dsc_unlink($img, storage_public());
        }

        /* 删除商品相册 */
        GoodsGallery::whereIn('goods_id', $goods_id)->delete();

        /* 删除相关表记录 */
        CollectGoods::whereIn('goods_id', $goods_id)->delete();
        GoodsArticle::whereIn('goods_id', $goods_id)->delete();
        GoodsAttr::whereIn('goods_id', $goods_id)->delete();
        GoodsCat::whereIn('goods_id', $goods_id)->delete();
        MemberPrice::whereIn('goods_id', $goods_id)->delete();

        GroupGoods::where(function ($query) use ($goods_id) {
            $query->whereIn('goods_id', $goods_id);
        })->orWhere(function ($query) use ($goods_id) {
            $query->whereIn('parent_id', $goods_id);
        })->delete();

        LinkGoods::where(function ($query) use ($goods_id) {
            $query->whereIn('goods_id', $goods_id);
        })->orWhere(function ($query) use ($goods_id) {
            $query->whereIn('link_goods_id', $goods_id);
        })->delete();

        Tag::whereIn('goods_id', $goods_id)->delete();
        Comment::where('comment_type', 0)->whereIn('id_value', $goods_id)->delete();
        Cart::whereIn('goods_id', $goods_id)->delete();
        PresaleActivity::whereIn('goods_id', $goods_id)->delete();

        WarehouseGoods::whereIn('goods_id', $goods_id)->delete();
        WarehouseAttr::whereIn('goods_id', $goods_id)->delete();
        WarehouseAreaGoods::whereIn('goods_id', $goods_id)->delete();
        WarehouseAreaAttr::whereIn('goods_id', $goods_id)->delete();
        ProductsWarehouse::whereIn('goods_id', $goods_id)->delete();
        ProductsArea::whereIn('goods_id', $goods_id)->delete();

        //清楚商品零时货品表数据
        ProductsChangelog::whereIn('goods_id', $goods_id)->delete();

        /* 删除相应虚拟商品记录 */
        VirtualCard::whereIn('goods_id', $goods_id)->delete();

        /* 清除缓存 */
        clear_cache_files();
    }


    /**
     * 检测商品是否有货品
     *
     * @access      public
     * @params      integer     $goods_id       商品id
     * @params      string      $where     sql条件
     * @return      string number               -1，错误；1，存在；0，不存在
     */
    public function checkGoodsProductExist($object, $goods_id, $where = [])
    {
        //$goods_id不能为空
        if (empty($goods_id)) {
            return 0;
        }

        $object = $object->where('goods_id', $goods_id);

        if ($where) {
            foreach ($where as $key => $val) {
                $object = $object->where($where[$key], $where[$val]);
            }
        }

        $count = $object->count();

        if ($count > 0) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 为某商品生成唯一的货号
     * @param int $goods_id 商品编号
     * @return  string  唯一的货号
     */
    public function generate_goods_sn($goods_id)
    {
        $goods_sn = $this->config['sn_prefix'] . str_repeat('0', 6 - strlen($goods_id)) . $goods_id;

        $sn_list = Goods::where('goods_id', '<>', $goods_id)
            ->where('goods_sn', 'like', '%' . $goods_sn . '%')
            ->orderByRaw('LENGTH(goods_sn) DESC')
            ->pluck('goods_sn');

        $sn_list = $sn_list ? $sn_list->toArray() : [];

        if (!empty($sn_list) && in_array($goods_sn, $sn_list)) {
            $max = pow(10, strlen($sn_list[0]) - strlen($goods_sn) + 1) - 1;
            $new_sn = $goods_sn . mt_rand(0, $max);
            while (in_array($new_sn, $sn_list)) {
                $new_sn = $goods_sn . mt_rand(0, $max);
            }
            $goods_sn = $new_sn;
        }

        return $goods_sn;
    }


    /**
     * 获取商品订单是否存在
     * @param int $goods_id
     * @return mixed
     */
    public function getOrderGoodsCout($goods_id = 0)
    {
        $res = OrderInfo::whereHas('getOrderGoods', function ($query) use ($goods_id) {
            $query->where('goods_id', $goods_id);
        });

        $order_count = $res->count();
        return $order_count;
    }

    /**
     * 列表链接
     * @param bool $is_add 是否添加（插入）
     * @param string $extension_code 虚拟商品扩展代码，实体商品为空
     * @return  array('href' => $href, 'text' => $text)
     */
    public function listLink($is_add = true, $extension_code = '')
    {
        $href = 'goods.php?act=list';
        if (!empty($extension_code)) {
            $href .= '&extension_code=' . $extension_code;
        }
        if (!$is_add) {
            $href .= '&' . list_link_postfix();
        }

        if ($extension_code == 'virtual_card') {
            $text = $GLOBALS['_LANG']['50_virtual_card_list'];
        } else {
            $text = $GLOBALS['_LANG']['01_goods_list'];
        }

        return ['href' => $href, 'text' => $text];
    }

    /**
     * 添加链接
     * @param string $extension_code 虚拟商品扩展代码，实体商品为空
     * @return  array('href' => $href, 'text' => $text)
     */
    public function addLink($extension_code = '')
    {
        $href = 'goods.php?act=add';
        if (!empty($extension_code)) {
            $href .= '&extension_code=' . $extension_code;
        }

        if ($extension_code == 'virtual_card') {
            $text = $GLOBALS['_LANG']['51_virtual_card_add'];
        } else {
            $text = $GLOBALS['_LANG']['02_goods_add'];
        }

        return ['href' => $href, 'text' => $text];
    }

    /**
     * 商品是否可操作
     *
     * @param int $goods_id
     * @param int $ru_id
     * @return boolean
     */
    public function goodsCanHandle($goods_id = 0, $ru_id = 0)
    {
        if ($goods_id > 0) {
            $user_id = Goods::where('goods_id', $goods_id)->value('user_id');

            if ($user_id !== null) {
                if ($user_id == $ru_id) //
                {
                    return true;
                }
            }
        } elseif ($goods_id == 0) // 添加暂时不判断 给平台用的方法
        {
            if ($ru_id == 0) {
                return true;
            }
        } else // 小于0 返回false
        {

        }

        return false;
    }

    /**
     * 取得某商品的会员价格列表
     * @param int $goods_id 商品编号
     * @return  array   会员价格列表 user_rank => user_price
     */
    function get_member_price_list($goods_id = 0)
    {
        if (empty($goods_id)) {
            return [];
        }

        /* 取得会员价格 */
        $price_list = [];

        $model = MemberPrice::query()->where('goods_id', $goods_id)->get();

        $res = $model ? $model->toArray() : [];

        if (!empty($res)) {
            foreach ($res as $row) {
                // 处理百分比
                if (isset($row['percentage']) && $row['percentage'] == 1) {
                    $row['user_price'] = $row['user_price'] . '%';
                }

                $price_list[$row['user_rank']] = $row['user_price'];
            }
        }

        return $price_list;
    }

    /**
     * 保存某商品的会员价格
     *
     * @param int $goods_id 商品编号
     * @param array $rank_list 等级列表
     * @param array $price_list 价格列表
     * @param int $is_discount 参与会员特价权益：0 否，1 是，默认 是
     * @return bool
     */
    public function handle_member_price($goods_id = 0, $rank_list = [], $price_list = [], $is_discount = 1)
    {
        if (empty($goods_id) || empty($rank_list)) {
            return false;
        }

        /* 循环处理每个会员等级 */
        foreach ($rank_list as $key => $rank) {
            /* 会员等级对应的价格 */
            $price = $price_list[$key] ?? 0;

            $insertData = [];
            $updateData = [];

            // 处理百分比
            if (stripos($price, '%') !== false) {
                $price = rtrim($price, '%');
                $updateData['percentage'] = $insertData['percentage'] = 1;
            }

            // 插入或更新记录
            $count = MemberPrice::where('goods_id', $goods_id)->where('user_rank', $rank)->count();

            if ($count > 0) {
                /* 如果会员价格是小于等于0则删除原来价格，不是则更新为新的价格 */
                if ($price <= 0 || $is_discount == 0) {
                    MemberPrice::where('goods_id', $goods_id)->where('user_rank', $rank)->delete();
                } else {
                    $updateData['user_price'] = $price;
                    MemberPrice::where('goods_id', $goods_id)->where('user_rank', $rank)->update($updateData);
                }
            } else {
                if ($price == -1 || empty($price) || $is_discount == 0) {
                    continue;
                } else {
                    $insertData['goods_id'] = $goods_id;
                    $insertData['user_rank'] = $rank;
                    $insertData['user_price'] = $price;
                    MemberPrice::insert($insertData);
                }
            }
        }

        return true;
    }
}
