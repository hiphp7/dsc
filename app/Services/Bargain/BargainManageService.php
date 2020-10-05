<?php

namespace App\Services\Bargain;

use App\Models\ActivityGoodsAttr;
use App\Models\Attribute;
use App\Models\BargainGoods;
use App\Models\BargainStatisticsLog;
use App\Models\Goods;
use App\Models\GoodsAttr;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\CommonManageService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Merchant\MerchantCommonService;


/**
 * 砍价
 * Class BargainManageService
 * @package App\Services
 */
class BargainManageService
{
    protected $timeRepository;
    protected $config;
    protected $dscRepository;
    protected $baseRepository;
    protected $merchantCommonService;
    protected $commonManageService;
    protected $goodsAttrService;

    public function __construct(
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        MerchantCommonService $merchantCommonService,
        CommonManageService $commonManageService,
        GoodsAttrService $goodsAttrService
    )
    {
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->baseRepository = $baseRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->commonManageService = $commonManageService;
        $this->goodsAttrService = $goodsAttrService;
    }


    /**
     * 砍价商品列表
     * @access  public
     * @param array $filter
     * @param array $offset
     * @return array
     */
    public function getBargainGoodsList($filter = [], $offset = [])
    {
        $model = BargainGoods::where('is_delete', 0);

        if (!empty($filter['goods_name'])) {
            $model = $model->whereHas('getGoods', function ($query) use ($filter) {
                $query->where('goods_name', 'like', '%' . $filter['goods_name'] . '%')
                    ->orWhere('goods_sn', 'like', '%' . $filter['goods_name'] . '%')
                    ->orWhere('keywords', 'like', '%' . $filter['goods_name'] . '%');
            });
        }

        switch ($filter['audit']) {
            // 未审核
            case '0':
                $model = $model->where('is_audit', $filter['audit']);
                break;
            // 已审核
            case '1':
                $model = $model->where('is_audit', $filter['audit']);
                break;
            // 审核未通过
            case '2':
                $model = $model->where('is_audit', $filter['audit']);
                break;
        }

        // 检测商品是否存在
        $model = $model->whereHas('getGoods', function ($query) {
            $query->where('is_alone_sale', 1)
                ->where('is_on_sale', 1)
                ->where('is_delete', 0)
                ->where('review_status', '>', 2);
        });

        $model = $model->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'user_id', 'goods_sn', 'goods_name', 'shop_price', 'market_price', 'goods_number', 'sales_volume', 'goods_img', 'goods_thumb', 'is_best', 'is_new');
            },
            'getBargainTargetPrice' => function ($query) {
                $query->select('bargain_id', 'target_price');
            }
        ]);

        $total = $model->count();

        $list = $model->offset($offset['start'])
            ->limit($offset['limit'])
            ->orderBy('id', 'DESC');

        $list = $this->baseRepository->getToArrayGet($list);

        $time = $this->timeRepository->getGmTime();
        foreach ($list as $key => $val) {
            $list[$key] = collect($val)->merge($val['get_goods'])->except('get_goods')->all();
            $list[$key]['user_name'] = $this->merchantCommonService->getShopName($list[$key]['user_id'], 1);//商家名称
            $list[$key]['shop_price'] = $this->dscRepository->getPriceFormat($list[$key]['shop_price']);
            $list[$key]['target_price'] = $this->dscRepository->getPriceFormat($val['target_price']);
            if ($val['get_bargain_target_price']) {//获取砍价商品属性最低价格
                $target_price = $this->baseRepository->getArrayMin($val['get_bargain_target_price'], 'target_price');
                if ($target_price) {
                    $list[$key]['target_price'] = $this->dscRepository->getPriceFormat($target_price);
                }
            }
            $list[$key]['goods_img'] = $this->dscRepository->getImagePath($list[$key]['goods_img']);
            $list[$key]['goods_thumb'] = $this->dscRepository->getImagePath($list[$key]['goods_thumb']);
            $list[$key]['start_time'] = $this->timeRepository->getLocalDate(config('shop.time_format'), $val['start_time']);
            $list[$key]['end_time'] = $this->timeRepository->getLocalDate(config('shop.time_format'), $val['end_time']);
            if ($val['status'] > 0) {
                $status = lang('admin/bargain.activities_closed');
            } else {
                if ($time < $val['start_time']) {
                    $status = lang('admin/bargain.activities_not_started');
                } elseif ($time > $val['start_time'] && $time >= $val['end_time']) {
                    $status = lang('admin/bargain.activities_have_expired');
                } else {
                    $status = lang('admin/bargain.activities_in_progress');
                }
            }
            $list[$key]['is_status'] = $status;
            $list[$key]['status'] = $val['status'];
            if ($val['is_audit'] == 1) {
                $is_audit = lang('admin/bargain.refuse_audit');
            } elseif ($val['is_audit'] == 2) {
                $is_audit = lang('admin/bargain.already_audit');
            } else {
                $is_audit = lang('admin/bargain.no_audit');
            }
            $list[$key]['is_audit'] = $is_audit;
        }

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 参与砍价活动列表
     * @access  public
     * @param array $where
     * @param array $offset
     * @return array
     */
    public function getBargainlog($where = [], $offset = [])
    {
        $time = $this->timeRepository->getGmTime();

        $model = BargainStatisticsLog::whereRaw(1);
        $model = $model->with([
            'getBargainGoods' => function ($query) {
                $query->select('id', 'goods_id', 'target_price', 'start_time', 'end_time');
            },
            'getUsers' => function ($query) {
                $query->select('user_id', 'user_name', 'nick_name');
            }
        ]);

        switch ($where['status']) {
            case '2':
                $model = $model->where('status', 0);// 活动进行中
                break;
            case '3':
                $model = $model->where('status', 1);// 成功活动
                break;
            case '4':
                $model = $model->where('status', 0);// 活动失败
                break;
        }

        $model = $model->whereHas('getBargainGoods', function ($query) use ($where) {
            $query->where('id', $where['bargain_id']);
            switch ($where['status']) {
                case '2':
                    $query->where('start_time', '<=', $where['time'])->where('end_time', '>=', $where['time']);// 活动进行中
                    break;
                case '4':
                    $query->where('end_time', '<', $where['time']);// 活动失败
                    break;
            }
        });

        $total = $model->count();


        $list = $model->offset($offset['start'])
            ->limit($offset['limit'])
            ->orderBy('add_time', 'DESC');

        $list = $this->baseRepository->getToArrayGet($list);

        if ($list) {
            foreach ($list as $key => $val) {
                $val = $val['get_bargain_goods'] ? array_merge($val, $val['get_bargain_goods']) : $val;
                $val = $val['get_users'] ? array_merge($val, $val['get_users']) : $val;
                $list[$key]['add_time'] = $this->timeRepository->getLocalDate(config('shop.time_format'), $val['add_time']);
                $list[$key]['final_price'] = $this->dscRepository->getPriceFormat($val['final_price']);
                //获取选中活动属性原价，底价
                if ($val['goods_attr_id']) {
                    $spec = explode(",", $val['goods_attr_id']);
                    $target_price = $this->bargainTargetPrice($val['bargain_id'], $val['goods_id'], $spec);//底价
                    $list[$key]['target_price'] = $this->dscRepository->getPriceFormat($target_price);
                } else {
                    $list[$key]['target_price'] = $this->dscRepository->getPriceFormat($val['target_price']);//底价
                }
                $list[$key]['user_name'] = $val['nick_name'] ? $val['nick_name'] : $val['user_name'];

                if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                    $list[$key]['user_name'] = $this->dscRepository->stringToStar($val['user_name']);
                }

                $list[$key]['count_num'] = $val['count_num'];
                //团状态
                if ($val['status'] == 1) {
                    $list[$key]['status'] = lang('admin/bargain.activities_success');
                } elseif ($val['status'] != 1 and $time >= $val['start_time'] and $time <= $val['end_time']) {
                    $list[$key]['status'] = lang('admin/bargain.activities_in_progress');
                } elseif ($val['status'] != 1 and $time > $val['end_time']) {
                    $list[$key]['status'] = lang('admin/bargain.activities_failure');
                }
            }
        }

        return ['list' => $list, 'total' => $total];

    }

    /**
     * 获得指定商品属性活动最低价
     * @access  public
     * @param int $bargain_id
     * @param int $goods_id
     * @param int $attr_id
     * @return array
     */
    public function bargainTargetPrice($bargain_id = 0, $goods_id = 0, $attr_id = [], $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        if (empty($attr_id)) {
            return 0;
        }

        $model_attr = Goods::where('goods_id', $goods_id)->value('model_attr');
        //商品属性价格模式,货品模式
        if ($this->config['goods_attr_price'] == 1) {
            if ($model_attr == 1) {
                $product_price = ProductsWarehouse::select('product_id', 'product_price', 'product_promote_price', 'product_market_price')
                    ->where('goods_id', $goods_id)
                    ->where('warehouse_id', $warehouse_id);
            } elseif ($model_attr == 2) {
                $product_price = ProductsArea::select('product_id', 'product_price', 'product_promote_price', 'product_market_price')
                    ->where('goods_id', $goods_id)
                    ->where('area_id', $area_id);

                if ($this->config['area_pricetype'] == 1) {
                    $product_price = $product_price->where('city_id', $area_city);
                }

            } else {
                $product_price = Products::select('product_id', 'product_price', 'product_promote_price', 'product_market_price')
                    ->where('goods_id', $goods_id);
            }

            //获取货品信息
            if ($attr_id) {
                foreach ($attr_id as $key => $val) {
                    $product_price = $product_price->whereRaw("FIND_IN_SET('$val', REPLACE(goods_attr, '|', ','))");
                }
            }
            $product_price = $this->baseRepository->getToArrayFirst($product_price);

            // 获取砍价属性底价
            if ($product_price) {
                $res = ActivityGoodsAttr::where('bargain_id', $bargain_id)
                    ->where('goods_id', $goods_id)
                    ->where('product_id', $product_price['product_id']);
                $res = $this->baseRepository->getToArrayFirst($res);
                return $res['target_price'] ?? 0;
            } else {
                return 0;
            }
        }
    }

    /**
     * 砍价商品属性组
     * @access  public
     * @param integer $goods_id
     * @param integer $goods_type
     * @return array
     */
    public function getAttributeList($goods_id = 0, $goods_type = 0)
    {

        //获取属性列表
        $attribute_list = Attribute::select('attr_id', 'attr_name', 'attr_input_type', 'attr_type', 'attr_values')
            ->where('cat_id', $goods_type)
            ->where('cat_id', '<>', 0)
            ->orderBy('sort_order', 'DESC')
            ->orderBy('attr_type', 'ASC')
            ->orderBy('attr_id', 'ASC')
            ->get();
        $attribute_list = $attribute_list ? $attribute_list->toArray() : [];

        //获取商品属性
        $attr_list = GoodsAttr::select('goods_attr_id', 'attr_id', 'attr_value', 'attr_price', 'attr_sort', 'attr_checked', 'attr_img_flie', 'attr_gallery_flie')
            ->where('goods_id', $goods_id)
            ->orderBy('attr_sort', 'ASC')
            ->orderBy('goods_attr_id', 'ASC')
            ->get();
        $attr_list = $attr_list ? $attr_list->toArray() : [];

        foreach ($attribute_list as $key => $val) {
            $is_selected = 0; //属性是否被选择
            $this_value = ""; //唯一属性的值

            if ($val['attr_type'] > 0) {
                if ($val['attr_values']) {
                    $attr_values = preg_replace(['/\r\n/', '/\n/', '/\r/'], ",", $val['attr_values']); //替换空格回车换行符为英文逗号
                    $attr_values = explode(',', $attr_values);
                } else {
                    $attr_values = GoodsAttr::select('attr_value')
                        ->where('goods_id', $goods_id)
                        ->where('attr_id', $val['attr_id'])
                        ->orderByRaw('attr_sort, goods_attr_id ASC');
                    $attr_values = $this->baseRepository->getToArrayGet($attr_values);

                    $values_list = $this->baseRepository->getKeyPluck($attr_values, 'attr_value');

                    $attribute_list[$key]['attr_values'] = $values_list;
                    $attr_values = $attribute_list[$key]['attr_values'];
                }

                $attr_values_arr = [];
                if ($attr_values) {
                    for ($i = 0; $i < count($attr_values); $i++) {
                        $attr_value = isset($attr_values[$i]) ? $attr_values[$i] : '';
                        $goods_attr = GoodsAttr::select('goods_attr_id', 'attr_price', 'attr_sort')->where('goods_id', $goods_id)
                            ->where('attr_value', $attr_value)
                            ->where('attr_id', $val['attr_id'])
                            ->first();
                        $goods_attr = $goods_attr ? $goods_attr->toArray() : [];
                        $attr_values_arr[$i] = [
                            'is_selected' => 0,
                            'goods_attr_id' => isset($goods_attr['goods_attr_id']) ? $goods_attr['goods_attr_id'] : '',
                            'attr_value' => isset($attr_values[$i]) ? $attr_values[$i] : '',
                            'attr_price' => isset($goods_attr['attr_price']) ? $goods_attr['attr_price'] : '',
                            'attr_sort' => isset($goods_attr['attr_sort']) ? $goods_attr['attr_sort'] : ''
                        ];
                    }
                }
                $attribute_list[$key]['attr_values_arr'] = $attr_values_arr;
            }

            foreach ($attr_list as $k => $v) {
                if ($val['attr_id'] == $v['attr_id']) {
                    $is_selected = 1;
                    //unset($k);
                    if ($val['attr_type'] == 0) {
                        $this_value = $v['attr_value'];
                    } else {
                        foreach ($attribute_list[$key]['attr_values_arr'] as $a => $b) {
                            if ($goods_id) {
                                if ($b['attr_value'] == $v['attr_value']) {
                                    $attribute_list[$key]['attr_values_arr'][$a]['is_selected'] = 1;
                                }
                            } else {
                                if ($b['attr_value'] == $v['attr_value']) {
                                    $attribute_list[$key]['attr_values_arr'][$a]['is_selected'] = 1;
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            $attribute_list[$key]['is_selected'] = $is_selected;
            $attribute_list[$key]['this_value'] = $this_value;
            if ($val['attr_input_type'] == 1) {
                $attr_values = preg_replace(['/\r\n/', '/\n/', '/\r/'], ",", $val['attr_values']); //替换空格回车换行符为英文逗号
                $attribute_list[$key]['attr_values'] = explode(',', $attr_values);
            }
        }

        $attribute_list = $this->commonManageService->getNewGoodsAttr($attribute_list);

        $attribute_list = $attribute_list['spec'];

        return $attribute_list;
    }

    //

    /**
     * 通过一组属性获取货品的相关信息
     * @access  public
     * @param int $bargain_id
     * @return array
     */
    public function get_product_info_by_attr_bargain($bargain_id = 0, $goods_id = 0, $attr_arr = [], $goods_model = 0, $region_id = 0)
    {
        if (!empty($attr_arr)) {

            if ($goods_model == 1) {
                $res = ProductsWarehouse::where('goods_id', $goods_id)
                    ->where('warehouse_id', $region_id);
            } elseif ($goods_model == 2) {
                $res = ProductsArea::where('goods_id', $goods_id)
                    ->where('area_id', $region_id);
            } else {
                $res = Products::where('goods_id', $goods_id);
            }

            $where_select = ['goods_id' => $goods_id];

            //获取属性组合
            $attr = [];
            foreach ($attr_arr as $key => $val) {
                $where_select['attr_value'] = $val;
                $goods_attr_id = $this->goodsAttrService->getGoodsAttrId($where_select, 1);
                if ($goods_attr_id) {
                    $attr[] = $goods_attr_id['goods_attr_id'];
                }
            }

            //获取货品信息
            foreach ($attr as $key => $val) {
                $res = $res->whereRaw("FIND_IN_SET('$val', REPLACE(goods_attr, '|', ','))");
            }
            $res = $res->first();
            $product_info = $res ? $res->toArray() : [];

            if ($bargain_id > 0) {
                $attr_info = ActivityGoodsAttr::where('bargain_id', $bargain_id)
                    ->where('goods_id', $goods_id)
                    ->where('product_id', $product_info['product_id'])
                    ->first();
                $attr_info = $attr_info ? $attr_info->toArray() : [];
                $product_info['goods_attr_id'] = $attr_info['id'] ?? 0;
                $product_info['target_price'] = $attr_info['target_price'] ?? 0;
            }

            return $product_info;

        } else {
            return false;
        }
    }


}
