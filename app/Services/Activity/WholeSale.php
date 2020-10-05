<?php

namespace App\Services\Activity;

use App\Models\Attribute;
use App\Models\Goods;
use App\Models\GoodsAttr;
use App\Models\Users;
use App\Models\Wholesale;
use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsMobileService;
use Illuminate\Support\Facades\Cache;

/**
 * 批发
 * Class Collect
 * @package App\Services
 */
class WholeSaleService
{
    protected $config;
    protected $goodsMobileService;
    protected $storeService;
    protected $dscRepository;

    public function __construct(
        GoodsMobileService $goodsMobileService,
        DscRepository $dscRepository
    )
    {
        $this->goodsMobileService = $goodsMobileService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 批发列表
     * @param Request $request
     * @return mixed
     */
    public function getWholeSaleList($user_id = 0, $data = [])
    {
        $res = Users::select('user_rank')->where('user_id', $user_id)->first();
        $user_rank = !empty($res) ? $res->toArray() : 0;
        $user_rank = $user_rank['user_rank'];
        $param = []; // 翻页链接所带参数列表


        /* 查询条件：当前用户的会员等级（搜索关键字） */
        $row = Wholesale::where('enabled', 1)
            ->where('review_status', 3)
            ->whereRaw("CONCAT(',', rank_ids, ',') LIKE '" . '%,' . $user_rank . ',%' . "' ");

        /* 搜索 */
        $row->when($data['search_category'], function ($query) use ($data) {
            $query->whereHas('getGoods', function ($query) use ($data) {
                return $query->where('cat_id', $data['search_category']);
            });
        })
            ->when($data['search_keywords'], function ($query) use ($data) {
                $query->whereHas('getGoods', function ($query) use ($data) {
                    return $query->where('keywords', 'like', '%' . $data['search_keywords'] . '%')
                        ->orWhere('goods_name', 'like', '%' . $data['search_keywords'] . '%');
                });
            });

        /* 搜索 */
        /* 搜索类别 */
        if ($data['search_category']) {
            $param['search_category'] = $data['search_category'];
            $result['search_category'] = $data['search_category'];
        }
        /* 搜索商品名称和关键字 */
        if ($data['search_keywords']) {
            $param['search_keywords'] = $data['search_keywords'];
            $result['search_keywords'] = $data['search_keywords'];
        }
        if ($data['order']) {
            // 关联订单商品表
            $row = $row->with([
                'getGoods' => function ($query) {
                    $query->selectRaw('goods_id,goods_thumb,goods_name');
                }
            ]);
        }

        /* 取得批发商品总数 */
        $count = $row->count();
        if ($count > 0) {
            $start = ($data['page'] - 1) * $data['size'];
            $row = $row->orderBy($data['order'], $data['sort']);

            if ($start > 0) {
                $row = $row->skip($start);
            }

            if ($data['size'] > 0) {
                $row = $row->take($data['size']);
            }

            $row = $row->get();

            $row = $row ? $row->toArray() : [];

            if ($row) {
                foreach ($row as $key => $val) {
                    $res = Goods::select('shop_price')->where('goods_id', $val['goods_id'])->first();
                    $res = $res ? $res->toArray() : [];
                    $row[$key]['format_shop_price'] = $this->dscRepository->getPriceFormat($res['shop_price']);
                    if (empty($val['get_goods']['goods_thumb'])) {
                        $row[$key]['goods_thumb'] = $this->config['no_picture'];
                    } else {
                        $row[$key]['goods_thumb'] = $this->dscRepository->getImagePath($val['get_goods']['goods_thumb']);
                    }
                }

                if ($data['order'] == "prices") {
                    if ($data['sort'] == "DESC") {
                        array_multisort(array_column($row, 'qp_list_min'), SORT_DESC, $row);
                    } else {
                        array_multisort(array_column($row, 'qp_list_min'), SORT_ASC, $row);
                    }
                }
            }

            $result['wholesale_list'] = $row;
        }

        return $result;
    }

    /**
     * 批发商品详情
     * @param Request $request
     * @return mixed
     */
    public function getWholeSaleDetail($goods_id, $user_id)
    {
        $wholesale = Wholesale::select('act_id', 'goods_id', 'user_id', 'goods_name', 'prices', 'sale_num')
            ->where('act_id', $goods_id)
            ->first();
        $wholesale = $wholesale ? $wholesale->toArray() : [];
        $arr = [];
        $arr['goods_id'] = $wholesale['goods_id'];
        $goods = $this->goodsMobileService->getGoodsInfo($arr);

        $prices = unserialize($wholesale['prices']);

        $hasPrice = false;
        foreach ($prices as $k => $v) {
            //
            foreach ($v['attr'] as $kitem => $vitem) {
                $res = Attribute::select('attr_name')->where('attr_id', $kitem)->first();
                $res = $res ? $res->toArray() : [];
                $kstr = $res ? $res['attr_name'] : '';
                $ress = GoodsAttr::select('attr_value')->where('goods_attr_id', $vitem)->first();
                $ress = $ress ? $ress->toArray() : [];
                $vstr = $ress ? $ress['attr_value'] : '';
                $prices[$k]['attr'][$kstr] = $vstr;
                unset($prices[$k]['attr'][$kitem]);
                $hasPrice = true;
            }
            //
            $minNum = 0;
            foreach ($v['qp_list'] as $kitem => $vitem) {
                $prices[$k]['qp_list'][$kitem]['quantity'] = $vitem['quantity'];
                $prices[$k]['qp_list'][$kitem]['price'] = $this->dscRepository->getPriceFormat($vitem['price']);
                if ($minNum == 0 || $vitem['quantity'] < $minNum) {
                    $minNum = $vitem['quantity'];
                }
            }
            $prices[$k]['minNum'] = $minNum;
        }

        $result = [];
        $result['id'] = $wholesale['act_id'];
        $result['prices'] = $prices;
        $result['hasPrice'] = $hasPrice;
        $result['goods'] = $goods;
        $result['minNum'] = $minNum;

        return $result;
    }

    /**
     * 批发商品
     * @param Request $request
     * @return mixed
     */
    public function getWholeSaleAddCart($goods_id, $number, $user_id)
    {
        $result = ['code' => 200, 'msg' => 'success_add_to_cart'];
        /* 取批发相关数据 */
        $wholesale = $this->getWholesaleInfo($goods_id);

        //组装缓存信息
        $key = 'wholesale&' . 'user_id=' . $user_id;

        $wholeattr = [];
        foreach ($wholesale['price_list'] as $k => $v) {
            $wholeattr[$k] = $v['attr'];
        };

        /* 检查缓存中该商品，该属性是否存在 */
        $data_res = Cache::get($key);

        $goods_list = [];
        foreach ($wholeattr as $klist => $list) {
            $goods_attr = [];
            foreach ($list as $k => $v) {
                $row['attr_id'] = $k;
                $row['attr_val_id'] = $v;

                $attr_name = Attribute::where('attr_id', $k)->value('attr_name');
                $row['attr_name'] = $attr_name ? $attr_name : '';

                $attr_value = GoodsAttr::select('attr_value')->where('goods_attr_id', $v)->value('attr_value');
                $row['attr_val'] = $attr_value ? $attr_value : '';
                array_push($goods_attr, $row);
            }
            $goods_list[] = ['number' => $number, 'goods_attr' => $goods_attr];
        }

        /* 获取购买商品的批发方案的价格阶梯 （一个方案多个属性组合、一个属性组合、一个属性、无属性） */
        $attr_matching = false;
        foreach ($wholesale['price_list'] as $attr_price) {
            // 没有属性
            if (empty($attr_price['attr'])) {
                $attr_matching = true;
                $goods_list[0]['qp_list'] = $attr_price['qp_list'];
                break;
            } // 有属性
            elseif (($key = $this->isAttrMatching($goods_list, $attr_price['attr'])) !== false) {
                $attr_matching = true;
                $goods_list[$key]['qp_list'] = $attr_price['qp_list'];
            }
        }

        if (!$attr_matching) {
            //$this->ajaxReturn(['msg' => L('no_match_goods_attr')]);
        }

        /* 检查数量是否达到最低要求 */
        foreach ($goods_list as $goods_key => $goods) {
            if ($goods['number'] < $goods['qp_list'][0]['quantity']) {
                $result = ['code' => '200', 'msg' => 'dont_match_min_num'];
                return $result;
            } elseif (strlen(intval($goods['number'])) > 6) {
                $result = ['code' => 200, 'msg' => 'number_is_to_large'];
                return $result;
            } else {
                $goods_price = 0;
                foreach ($goods['qp_list'] as $qp) {
                    if ($goods['number'] >= $qp['quantity']) {
                        $goods_list[$goods_key]['goods_price'] = $qp['price'];
                    } else {
                        break;
                    }
                }
            }
        }

        /*整理需要加入缓存的信息 */
        foreach ($goods_list as $goods_key => $goods) {
            // 属性名称
            $goods_attr_name = '';
            if (!empty($goods['goods_attr'])) {
                foreach ($goods['goods_attr'] as $key => $attr) {
                    $attr['attr_name'] = htmlspecialchars($attr['attr_name']);
                    $goods['goods_attr'][$key]['attr_name'] = $attr['attr_name'];
                    $attr['attr_val'] = htmlspecialchars($attr['attr_val']);
                    $goods['goods_attr'][$key]['attr_name'] = $attr['attr_name'];
                    $goods_attr_name .= $attr['attr_name'] . '：' . $attr['attr_val'] . '&nbsp;';
                }
            }

            // 总价
            $total = $goods['number'] * $goods['goods_price'];

            $data[] = [
                'goods_id' => $wholesale['goods_id'],
                'goods_number' => $goods['number'],
                'goods_price' => $goods['goods_price'],
                'subtotal' => $total,
                'user_id' => $user_id,
                'act_id' => $wholesale['act_id'],
            ];
        }

        $arr = Cache::has($key);
        if (!$arr) {
            info('没有缓存信息');
            //保存永久缓存信息
            Cache::forever($key, $data);
        } else {
            info('有缓存信息');

            foreach ($data_res as $key => $row) {
                $arr_diff = array_diff($row, $data[0]);
                //新加入的商品和缓存中商品不同
                if ($arr_diff) {
                    //同一款批发商品，其他方面不同例如数量
                    if ($row['goods_id'] == $data[0]['goods_id']) {
                        unset($data_res[$key]);
                    }
                } else {
                    unset($data_res[$key]);
                }
            }

            $data_res = array_merge($data_res, $data);


            //删除以前的的永久缓存
            Cache::forget($key);
            Cache::get($key);

            //重新保存缓存信息
            Cache::forever($key, $data_res);
        }

        Cache::get($key);

        return $result;
    }

    /**
     * 批发信息
     * @param int $act_id 活动id
     * @return  array
     */
    private function getWholesaleInfo($act_id)
    {
        $row = Wholesale::where('act_id', $act_id)->first();
        $row = $row ? $row->toArray() : [];
        if (!empty($row)) {
            $row['price_list'] = isset($row['prices']) ? unserialize($row['prices']) : 0;
        }

        return $row;
    }

    /**
     * 商品属性是否匹配
     * @param array $goods_list 用户选择的商品
     * @param array $reference 参照的商品属性
     * @return  bool
     */
    private function isAttrMatching(&$goods_list, $reference)
    {
        foreach ($goods_list as $key => $goods) {
            // 需要相同的元素个数
            if (count($goods['goods_attr']) != count($reference)) {
                break;
            }

            // 判断用户提交与批发属性是否相同
            $is_check = true;
            if (is_array($goods['goods_attr'])) {
                foreach ($goods['goods_attr'] as $attr) {
                    if (!(array_key_exists($attr['attr_id'], $reference) && $attr['attr_val_id'] == $reference[$attr['attr_id']])) {
                        $is_check = false;
                        break;
                    }
                }
            }
            if ($is_check) {
                return $key;
                break;
            }
        }

        return false;
    }
}
