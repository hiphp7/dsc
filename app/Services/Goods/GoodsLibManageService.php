<?php

namespace App\Services\Goods;

use App\Libraries\Image;
use App\Models\Goods;
use App\Models\GoodsLib;
use App\Models\GoodsLibGallery;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;

class GoodsLibManageService
{
    protected $dscRepository;
    protected $baseRepository;
    protected $timeRepository;

    public function __construct(
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        TimeRepository $timeRepository
    )
    {
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
    }

    /**
     * 列表链接
     * @param bool $is_add 是否添加（插入）
     * @param string $extension_code 虚拟商品扩展代码，实体商品为空
     * @return  array('href' => $href, 'text' => $text)
     */
    public function listLink($is_add = true, $extension_code = '')
    {
        $href = 'goods_lib.php?act=list';
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
        $href = 'goods_lib.php?act=add';
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

    //获取会员信息列表
    public function getSearchShopnameList($user_list)
    {
        $html = '';
        if ($user_list) {
            $html .= "<ul>";

            foreach ($user_list as $key => $user) {
                $html .= "<li data-name='" . $user['shop_name'] . "' data-id='" . $user['user_id'] . "'>" . $user['shop_name'] . "</li>";
            }

            $html .= '</ul>';
        } else {
            $html = '<span class="red">' . lang('admin/goods_lib.member_null') . '</span><input name="user_id" value="0" type="hidden" />';
        }

        return $html;
    }

    /**
     * 取得店铺导入商品列表
     * @return array
     */
    public function getImportGoodsList($ru_id = 0)
    {
        $res = Goods::where('user_id', $ru_id)->orderBy('sort_order');
        $res = $this->baseRepository->getToArrayGet($res);

        $goods_list = [];
        foreach ($res as $key => $row) {
            $goods_list[$key]['goods_id'] = $row['goods_id'];
            $goods_list[$key]['goods_name'] = addslashes($row['goods_name']);
        }
        return $goods_list;
    }

    /**
     * 从回收站删除多个商品
     * @param mix $goods_id 商品id列表：可以逗号格开，也可以是数组
     * @return  void
     */
    public function libDeleteGoods($goods_id)
    {
        if (empty($goods_id)) {
            return;
        }

        /* 取得有效商品id */
        $goods_id = $this->baseRepository->getExplode($goods_id);
        $res = GoodsLib::select('goods_id')->distinct()->whereIn('goods_id', $goods_id);
        $goods_id = $this->baseRepository->getToArrayGet($res);
        $goods_id = $this->baseRepository->getFlatten($goods_id);

        if (empty($goods_id)) {
            return;
        }

        /* 删除商品图片和轮播图片文件 */
        $res = GoodsLib::whereIn('goods_id', $goods_id);
        $res = $this->baseRepository->getToArrayGet($res);
        if ($res) {
            $arr = [];
            foreach ($res as $goods) {
                if (!empty($goods['goods_thumb'])) {
                    $arr[] = $goods['goods_thumb'];
                    dsc_unlink(storage_public($goods['goods_thumb']));
                }
                if (!empty($goods['goods_img'])) {
                    $arr[] = $goods['goods_img'];
                    dsc_unlink(storage_public($goods['goods_img']));
                }
                if (!empty($goods['original_img'])) {
                    $arr[] = $goods['original_img'];
                    dsc_unlink(storage_public($goods['original_img']));
                }
            }

            $this->dscRepository->getOssDelFile($arr);
        }


        /* 删除商品 */
        GoodsLib::whereIn('goods_id', $goods_id)->delete();

        /* 删除商品相册的图片文件 */
        $res = GoodsLibGallery::whereIn('goods_id', $goods_id);
        $res = $this->baseRepository->getToArrayGet($res);
        if ($res) {
            $arr = [];
            foreach ($res as $row) {
                if (!empty($row['img_url'])) {
                    $arr[] = $row['img_url'];
                    dsc_unlink(storage_public($row['img_url']));
                }
                if (!empty($row['thumb_url'])) {
                    $arr[] = $row['thumb_url'];
                    dsc_unlink(storage_public($row['thumb_url']));
                }
                if (!empty($row['img_original'])) {
                    $arr[] = $row['img_original'];
                    dsc_unlink(storage_public($row['img_original']));
                }
            }

            $this->dscRepository->getOssDelFile($arr);
        }

        /* 删除商品相册 */
        GoodsLibGallery::whereIn('goods_id', $goods_id)->delete();
        /* 清除缓存 */
        clear_cache_files();
    }

    /**
     * 修改商品某字段值
     * @param string $goods_id 商品编号，可以为多个，用 ',' 隔开
     * @param string $field 字段名
     * @param string $value 字段值
     * @return  bool
     */
    public function libUpdateGoods($goods_id, $field, $value, $content = '', $type = '')
    {
        if ($goods_id) {
            /* 清除缓存 */
            clear_cache_files();

            $goods_id = $this->baseRepository->getExplode($goods_id);
            $data = [
                $field => $value,
                'last_update' => $this->timeRepository->getGmTime()
            ];
            $res = GoodsLib::whereIn('goods_id', $goods_id)->update($data);
            return $res;
        } else {
            return false;
        }
    }

    /**
     * 复制商品图片
     *
     * @param string $image
     * @return mixed|string|void
     */
    public function copyImg($image = '')
    {
        if (stripos($image, "http://") !== false || stripos($image, "https://") !== false) {//外链图片
            return $image;
        }
        $newname = '';
        if ($image) {
            $img = storage_public($image);
            $pos = strripos(basename($img), '.');
            $newname = dirname($img) . '/' . app(Image::class)->random_filename() . substr(basename($img), $pos);
            //开启OSS 则先下载图片 否则拷贝图片
            if ($GLOBALS['_CFG']['open_oss'] == 1) {
                $bucket_info = $this->dscRepository->getBucketInfo();
                $url = $bucket_info['endpoint'] . $image;
                //如果目标目录不存在，则创建它
                if (!file_exists(dirname($img))) {
                    make_dir(dirname($img));
                }
                @get_http_basename($url, $newname, 1);
            } elseif (!@copy($img, $newname)) {
                return;
            }
        }

        $new_name = str_replace(storage_public(), '', $newname);
        $this->dscRepository->getOssAddFile([$new_name]);

        return $new_name;
    }

    public function libIsMer($goods_id)
    {
        $one = GoodsLib::where('goods_id', $goods_id)->value('user_id');
        $one = $one ? $one : 0;
        if ($one == 0) {
            return false;
        } else {
            return $one;
        }
    }
}
