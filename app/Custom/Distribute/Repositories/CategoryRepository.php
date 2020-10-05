<?php

namespace App\Custom\Distribute\Repositories;

use App\Models\Category;
use App\Models\GoodsLibCat;
use App\Models\GoodsTypeCat;
use App\Models\MerchantsCategory;
use App\Models\MerchantsShopInformation;
use App\Models\WholesaleCat;
use App\Models\ZcCategory;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;


/**
 * Class CategoryRepository
 * @package App\Custom\Distribute\Repositories
 */
class CategoryRepository
{
    protected $timeRepository;
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        TimeRepository $timeRepository,
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
    }

    /**
     * 分类信息
     * @param array $arr
     * @param string $table
     * @return bool|array
     */
    public function get_array_category_info($arr = [], $table = 'category')
    {
        if ($table == 'goods_lib_cat') {
            $table = GoodsLibCat::whereRaw(1);
        } elseif ($table == 'zc_category') {
            $table = ZcCategory::whereRaw(1);
        } elseif ($table == 'goods_type_cat') {
            $table = GoodsTypeCat::whereRaw(1);
        } elseif ($table == 'wholesale_cat') {
            $table = WholesaleCat::whereRaw(1);
        } else {
            $table = Category::whereRaw(1);
        }

        if ($arr) {
            $arr = $this->baseRepository->getExplode($arr);

            $category_list = $table->whereIn('cat_id', $arr);
            $category_list = $this->baseRepository->getToArrayGet($category_list);

            return $category_list;
        } else {
            return false;
        }
    }

    /**
     * 商家分类 获取当级分类列表
     * @param int $cat_id
     * @param int $relation 关系 0:自己 1:上级 2:下级
     * @param int $user_id
     * @return mixed
     */
    public function get_seller_category_list($cat_id = 0, $relation = 0, $user_id = 0)
    {
        if ($relation == 0 || $relation == 1) {
            $res = MerchantsCategory::where('cat_id', $cat_id);

            if ($user_id) {
                $res = $res->where('user_id', $user_id);
            }

            $parent_id = $res->value('parent_id');
        } elseif ($relation == 2) {
            $parent_id = $cat_id;
        }

        $parent_id = empty($parent_id) ? 0 : $parent_id;
        $category_list = MerchantsCategory::where('parent_id', $parent_id);

        if ($user_id) {
            $category_list = $category_list->where('user_id', $user_id);
        }

        $category_list = $this->baseRepository->getToArrayGet($category_list);

        if ($category_list) {
            foreach ($category_list as $key => $val) {
                if ($cat_id == $val['cat_id']) {
                    $is_selected = 1;
                } else {
                    $is_selected = 0;
                }
                $category_list[$key]['is_selected'] = $is_selected;
            }
        }

        return $category_list;
    }

    /**
     * 商家入驻分类
     * @param int $user_id 商家id
     * @return array
     */
    public function seller_shop_cat($user_id = 0)
    {
        $seller_shop_cat = '';
        if ($user_id) {
            $seller_shop_cat = MerchantsShopInformation::where('user_id', $user_id)->value('user_shopMain_category');
        }

        $arr = [];
        $arr['parent'] = '';
        if ($seller_shop_cat) {
            $seller_shop_cat = explode("-", $seller_shop_cat);

            foreach ($seller_shop_cat as $key => $row) {
                if ($row) {
                    $cat = explode(":", $row);
                    $arr[$key]['cat_id'] = $cat[0];
                    $arr[$key]['cat_tree'] = $cat[1];

                    $arr['parent'] .= $cat[0] . ",";

                    if ($cat[1]) {
                        $arr['parent'] .= $cat[1] . ",";
                    }
                }
            }
        }

        $arr['parent'] = substr($arr['parent'], 0, -1);

        return $arr;
    }
}
