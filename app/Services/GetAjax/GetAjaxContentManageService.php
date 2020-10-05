<?php

namespace App\Services\GetAjax;


use App\Libraries\Image;
use App\Models\Attribute;
use App\Models\FloorContent;
use App\Models\Goods;
use App\Models\GoodsArticle;
use App\Models\SellerShopwindow;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Category\CategoryService;
use App\Services\Merchant\MerchantCommonService;


/**
 *
 * Class GetAjaxContentManageService
 * @package App\Services\GetAjax
 */
class GetAjaxContentManageService
{

    protected $categoryService;
    protected $baseRepository;
    protected $dscRepository;
    protected $merchantCommonService;

    public function __construct(
        CategoryService $categoryService,
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService
    )
    {
        $this->categoryService = $categoryService;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
    }


    //获取橱窗商品
    public function getWinGoods($id)
    {
        $adminru = get_admin_ru_id();

        $res = SellerShopwindow::where('id', $id)->where('ru_id', $adminru['ru_id']);
        $win_info = $this->baseRepository->getToArrayFirst($res);

        if ($win_info['id'] > 0) {
            $goods_ids = $win_info['win_goods'];
            $goods = [];
            if ($goods_ids) {
                $goods_ids = $this->baseRepository->getExplode($goods_ids);
                $res = Goods::select('goods_id', 'goods_name')->where('user_id', $adminru['ru_id'])->whereIn('goods_id', $goods_ids);
                $goods = $this->baseRepository->getToArrayGet($res);
            }
            //return $goods;
            $opt = []; //by wu

            foreach ($goods as $val) {
                $opt[] = ['value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => ''];
            }
            return $opt;
        } else {
            return 'no_cc';
        }
    }

    /**
     * 获取商品类型属性
     *
     * @param int $cat_id 商品类型ID
     *
     * @return array
     */
    public function getAttributes($cat_id = 0)
    {
        $res = Attribute::select('attr_id', 'cat_id', 'attr_name');

        if (!empty($cat_id)) {
            $cat_id = intval($cat_id);
            $res = $res->where('cat_id', $cat_id);
        }
        $attributes = [];

        $res = $res->orderBy('cat_id', 'ASC')->orderBy('attr_id', 'ASC');
        $query = $this->baseRepository->getToArrayGet($res);

        foreach ($query as $row) {
            $attributes[$row['attr_id']] = $row['attr_name'];
        }
        return $attributes;
    }

    public function getGloorContent($curr_template, $filename, $id = 0, $region = '')
    {
        $res = FloorContent::where('filename', $filename)->where('theme', $curr_template);
        if (!empty($id)) {
            $res = $res->where('id', $id);
        }
        if (!empty($region)) {
            $res = $res->where('region', $region);
        }

        $row = $this->baseRepository->getToArrayGet($res);
        return $row;
    }

    /* 上传文件 */
    public function uploadArticleFile($upload, $file = '')
    {
        $file_dir = storage_public(DATA_DIR . "/gallery_album/");
        if (!file_exists($file_dir)) {
            if (!make_dir($file_dir)) {
                /* 创建目录失败 */
                return false;
            }
        }
        $filename = app(Image::class)->random_filename() . substr($upload['name'], strpos($upload['name'], '.'));
        $path = storage_public(DATA_DIR . "/gallery_album/" . $filename);
        if (move_upload_file($upload['tmp_name'], $path)) {
            return DATA_DIR . "/gallery_album/" . $filename;
        } else {
            return false;
        }
    }

    /* 取得文章关联商品 */
    public function getArticleGoods($article_id)
    {
        $res = GoodsArticle::select('goods_id')->where('article_id', $article_id);
        $res = $res->with(['getGoods' => function ($query) {
            $query->select('goods_id', 'goods_name');
        }]);
        $list = $this->baseRepository->getToArrayGet($res);
        return $list;
    }
}
