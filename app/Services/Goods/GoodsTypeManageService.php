<?php

namespace App\Services\Goods;

use App\Models\Attribute;
use App\Models\GoodsType;
use App\Repositories\Common\BaseRepository;
use App\Services\Merchant\MerchantCommonService;

class GoodsTypeManageService
{
    protected $baseRepository;
    protected $merchantCommonService;

    public function __construct(
        BaseRepository $baseRepository,
        MerchantCommonService $merchantCommonService

    )
    {
        $this->baseRepository = $baseRepository;
        $this->merchantCommonService = $merchantCommonService;
    }

    /**
     * 获得所有商品类型
     *
     * @access  public
     * @return  array
     */
    public function getGoodsType($ru_id = 0)
    {
        //ecmoban模板堂 --zhuo start
        $res = GoodsType::whereRaw(1);

        if ($GLOBALS['_CFG']['attr_set_up'] == 0) {
            if ($ru_id > 0) {
                $res = $res->where('user_id', 0);
            }
        } elseif ($GLOBALS['_CFG']['attr_set_up'] == 1) {
            if ($ru_id > 0) {
                $res = $res->where('user_id', $ru_id);
            }
        }
        //ecmoban模板堂 --zhuo end

        /* 过滤信息 */
        if (!empty($_GET['is_ajax']) && $_GET['is_ajax'] == 1) {
            $_REQUEST['keyword'] = json_str_iconv($_REQUEST['keyword']);
        }
        $filter['cat_id'] = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
        $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : -1;
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'cat_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);
        $filter['seller_list'] = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? intval($_REQUEST['seller_list']) : 0;  //商家和自营订单标识

        if ($filter['cat_id'] > 0) {
            $cat_keys = get_type_cat_arr($filter['cat_id'], 1, 1);

            $cat_keys = $this->baseRepository->getExplode($cat_keys);
            $res = $res->whereIn('c_id', $cat_keys);
        }
        if ($filter['keyword']) {
            $res = $res->where('cat_name', 'LIKE', '%' . mysql_like_quote($filter['keyword']) . '%');
        }

        if ($filter['merchant_id'] > -1) {
            $res = $res->where('user_id', $filter['merchant_id']);
        }

        if ($filter['seller_list'] == 2) {
            //区分商家和自营
            $res = $res->where('user_id', 0)->where('suppliers_id' > 0);
        } else {
            //区分商家和自营
            if (!empty($filter['seller_list'])) {
                $res = $res->where('user_id', '>', 0)->where('suppliers_id', 0);
            } else {
                $res = $res->where('user_id', 0)->where('suppliers_id', 0);
            }
        }

        /* 记录总数以及页数 */
        $filter['record_count'] = $res->count();

        $filter = page_and_size($filter);

        /* 查询记录 */
        $res = $res->withCount('getGoodsAttribute as attr_count');

        $res = $res->with(['getGoodsTypeCat' => function ($query) {
            $query->select('cat_id', 'cat_name');
        }]);

        $res = $res->groupBy('cat_id')
            ->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size']);
        $all = $this->baseRepository->getToArrayGet($res);

        if ($all) {
            foreach ($all as $key => $val) {
                $val['gt_cat_name'] = '';
                if (isset($val['get_goods_type_cat']) && !empty($val['get_goods_type_cat'])) {
                    $val['gt_cat_name'] = $val['get_goods_type_cat']['cat_name'];
                }

                $val['attr_group'] = strtr($val['attr_group'], ["\r" => '', "\n" => ", "]);
                if ($val['suppliers_id'] > 0) {
                    $val['user_name'] = get_table_date('suppliers', "suppliers_id='" . $val['suppliers_id'] . "'", ['suppliers_name'], 2);
                } else {
                    $val['user_name'] = $this->merchantCommonService->getShopName($val['user_id'], 1);
                }
                $all[$key] = $val;
            }
        }

        return ['type' => $all, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }


    /**
     * 获得指定的商品类型的详情
     *
     * @param integer $cat_id 分类ID
     *
     * @return  array
     */
    public function getGoodstypeInfo($cat_id)
    {
        $res = GoodsType::where('cat_id', $cat_id);
        $res = $this->baseRepository->getToArrayFirst($res);
        return $res;
    }

    /**
     * 更新属性的分组
     *
     * @param integer $cat_id 商品类型ID
     * @param integer $old_group
     * @param integer $new_group
     *
     * @return  void
     */
    public function updateAttributeGroup($cat_id, $old_group, $new_group)
    {
        $data = ['attr_group' => $new_group];
        Attribute::where('cat_id', $cat_id)->where('attr_group', $old_group)->update($data);
    }
}
