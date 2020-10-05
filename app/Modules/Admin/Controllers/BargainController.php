<?php

namespace App\Modules\Admin\Controllers;

use App\Models\ActivityGoodsAttr;
use App\Models\Attribute;
use App\Models\BargainGoods;
use App\Models\BargainStatistics;
use App\Models\Goods;
use App\Models\RegionWarehouse;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Bargain\BargainManageService;
use App\Services\Common\CommonManageService;
use App\Services\Goods\GoodsAttrService;

/**
 * 砍价模块
 * Class BargainController
 * @package App\Modules\Admin\Controllers
 */
class BargainController extends BaseController
{
    // 分页数量
    protected $page_num = 1;
    protected $timeRepository;
    protected $baseRepository;
    protected $dscRepository;
    protected $bargainManageService;

    public function __construct(
        TimeRepository $timeRepository,
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        BargainManageService $bargainManageService
    )
    {
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->bargainManageService = $bargainManageService;
    }

    protected function initialize()
    {
        parent::initialize();

        L(require(resource_path('lang/' . config('shop.lang') . '/admin/bargain.php')));
        $this->assign('lang', array_change_key_case(L()));

        $files = [
            'order',
            'clips',
            'payment',
            'transaction',
            'ecmoban'
        ];
        load_helper($files);

        // 初始化 每页分页数量
        $this->init_params();
    }

    /**
     * 处理公共参数
     */
    private function init_params()
    {
        $page_num = request()->cookie('page_size');
        $this->page_num = is_null($page_num) ? 10 : $page_num;
        $this->assign('page_num', $this->page_num);
    }

    /**
     * 砍价商品列表
     */
    public function index()
    {
        if (request()->isMethod('POST')) {
            //修改每页数量
            $page_num = request()->has('page_num') ? request()->input('page_num') : 0;
            if ($page_num > 0) {
                cookie()->queue('page_size', $page_num, 24 * 60 * 30);
                return response()->json(['status' => 1]);
            }
        }

        $this->admin_priv('bargain_manage');

        $goods_name = html_in(request()->input('keyword', ''));
        $audit = request()->input('is_audit', 3);

        $filter = [
            'goods_name' => $goods_name,
            'audit' => $audit
        ];

        $offset = $this->pageLimit(route('admin/bargain/index', $filter), $this->page_num);

        $result = $this->bargainManageService->getBargainGoodsList($filter, $offset);

        $list = $result['list'] ?? [];
        $total = $result['total'] ?? 0;

        $this->assign('audit', $audit);
        $this->assign('list', $list);

        $this->assign('page', $this->pageShow($total));
        return $this->display();
    }

    /**
     * 添加砍价商品
     */
    public function addgoods()
    {
        $this->admin_priv('bargain_manage');
        if (request()->isMethod('POST')) {
            $data = request()->input('data');

            $id = request()->input('id', '');
            $target_price = request()->input('target_price', '');//目标价格

            $product_id = request()->input('product_id', '');//货品ID
            $activity_goods_attr = request()->input('bargain_id', '');//活动商品属性列表id

            if ($data['min_price'] < 1) {
                return response()->json(['status' => 'n', 'info' => lang('admin/bargain.bargain_section_less_than_zero')]);
            }

            if ($data['min_price'] > $data['max_price']) {
                return response()->json(['status' => 'n', 'info' => lang('admin/bargain.small_cant_greater_than_big')]);
            }

            if ($data['target_price'] > $data['goods_price']) {
                return response()->json(['status' => 'n', 'info' => lang('admin/bargain.target_price_greater_than_goods_price')]);
            }

            $data['start_time'] = $this->timeRepository->getLocalStrtoTime($data['start_time']);
            $data['end_time'] = $this->timeRepository->getLocalStrtoTime($data['end_time']);
            $data['target_price'] = $data['target_price'] ?? 0;
            $data['bargain_desc'] = $data['bargain_desc'] ?? '';
            if (!$id) {//添加
                $count = BargainGoods::where(['goods_id' => $data['goods_id'], 'status' => '0'])->count();
                if ($count >= 1) {
                    return response()->json(['status' => 'n', 'info' => lang('admin/bargain.cant_add_activity')]);
                }

                $data['add_time'] = $this->timeRepository->getGmTime();
                $bargain_id = BargainGoods::insertGetId($data);

                if ($bargain_id) {
                    if ($product_id) {
                        foreach ($product_id as $key => $value) {
                            $attr_data['bargain_id'] = $bargain_id;
                            $attr_data['goods_id'] = $data['goods_id'];
                            $attr_data['product_id'] = $value;
                            $attr_data['target_price'] = $target_price[$key];
                            $attr_data['type'] = 'bargain';
                            ActivityGoodsAttr::create($attr_data);
                        }
                    }

                    return response()->json(['status' => 'y', 'info' => lang('admin/bargain.add_success'), 'url' => route('admin/bargain/index')]);
                } else {
                    return response()->json(['status' => 'n', 'info' => lang('admin/bargain.add_failure')]);
                }
            } else {
                //修改
                if ($data['is_audit'] != 1) {
                    $data['isnot_aduit_reason'] = '';
                }
                BargainGoods::where(['id' => $id])->update($data);

                if ($product_id) {
                    foreach ($product_id as $key => $value) {
                        $attr_data['target_price'] = $target_price[$key];
                        if (!empty($activity_goods_attr[$key])) {
                            ActivityGoodsAttr::where(['id' => $activity_goods_attr[$key], 'goods_id' => $data['goods_id'], 'product_id' => $value])->update($attr_data);
                        } else {
                            $attr_data['bargain_id'] = $id;
                            $attr_data['goods_id'] = $data['goods_id'];
                            $attr_data['product_id'] = $value;
                            $attr_data['target_price'] = $target_price[$key];
                            $attr_data['type'] = 'bargain';
                            ActivityGoodsAttr::create($attr_data);
                        }
                    }
                }

                return response()->json(['status' => 'y', 'info' => lang('admin/bargain.update_success'), 'url' => route('admin/bargain/index')]);
            }
        }
        $nowtime = $this->timeRepository->getGmTime();
        $info = [];
        $id = request()->input('id', '');
        if ($id) {
            //砍价商品信息
            $model = BargainGoods::with([
                'getGoods' => function ($query) {
                    $query->select('goods_id', 'user_id as ru_id', 'goods_name')
                        ->where('is_delete', 0);
                }
            ]);
            $info = $model->where(['id' => $id])
                ->first();
            $info = $info ? $info->toArray() : [];

            $info = collect($info)->merge($info['get_goods'])->except('get_goods')->all();
            $info['start_time'] = isset($info['start_time']) ? $this->timeRepository->getLocalDate('Y-m-d H:i:s', $info['start_time']) : $this->timeRepository->getLocalDate('Y-m-d H:i:s', $nowtime);
            $info['end_time'] = isset($info['end_time']) ? $this->timeRepository->getLocalDate('Y-m-d H:i:s', $info['end_time']) : $this->timeRepository->getLocalDate('Y-m-d H:i:s', $this->timeRepository->getLocalStrtoTime("+1 months"));
        } else {
            // 默认开始与结束时间
            $info = [
                'start_time' => $this->timeRepository->getLocalDate('Y-m-d H:i:s', $nowtime),
                'end_time' => $this->timeRepository->getLocalDate('Y-m-d H:i:s', $this->timeRepository->getLocalStrtoTime("+1 months")),
                'min_price' => 1,
                'max_price' => 10
            ];
        }

        $this->assign('info', $info);

        $filter = set_default_filter_new(); //设置默认 分类，品牌列表 筛选

        $this->assign('filter_category_level', 1); //分类等级 默认1
        $this->assign('filter_category_navigation', $filter['filter_category_navigation']);
        $this->assign('filter_category_list', $filter['filter_category_list']);
        $this->assign('filter_brand_list', $filter['filter_brand_list']);

        return $this->display();
    }

    /**
     * 搜索商品
     */
    public function searchgoods()
    {
        $cat_id = request()->input('cat_id', 0);
        $brand_id = request()->input('brand_id', 0);
        $keywords = html_in(request()->input('keyword', ''));

        $model = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->where('review_status', '>', 2)
            ->where('user_id', 0);

        if ($cat_id > 0) {
            $cat_arr = get_children_new($cat_id);
            $model = $model->whereIn('cat_id', $cat_arr);
        }
        if ($brand_id > 0) {
            $model = $model->where('brand_id', $brand_id);
        }
        if ($keywords) {
            $model = $model->where('goods_name', 'like', '%' . $keywords . '%')
                ->orWhere('goods_sn', 'like', '%' . $keywords . '%')
                ->orWhere('keywords', 'like', '%' . $keywords . '%');
        }

        $row = $model->select('goods_id', 'goods_name', 'shop_price')
            ->limit(50)
            ->orderBy('goods_id', 'DESC')
            ->get();
        $row = $row ? $row->toArray() : [];

        return response()->json(['content' => array_values($row)]);
    }

    /**
     * 获取选中商品属性组
     */
    public function goodsinfo(CommonManageService $commonManageService)
    {
        $goods_id = request()->input('goods_id', 0);

        $row = Goods::select('shop_price', 'goods_type', 'model_attr')
            ->where('goods_id', $goods_id)
            ->first();
        $row = $row ? $row->toArray() : [];

        $goods_type = isset($row['goods_type']) ? intval($row['goods_type']) : '';
        $goods_model = isset($row['model_attr']) ? $row['model_attr'] : '';

        $attribute_list = $this->bargainManageService->getAttributeList($goods_id, $goods_type);

        $lang = lang('admin/bargain');
        $data = compact('goods_id', 'goods_model', 'attribute_list', 'lang');
        $result['goods_attribute'] = view('admin.bargain.bargain_goods_attribute', $data)->render();

        $result['goods_id'] = $goods_id;
        $result['shop_price'] = $row['shop_price'] ?? '';

        return response()->json($result);
    }

    /**
     *  设置属性表格
     */
    public function setattributetable(GoodsAttrService $goodsAttrService)
    {
        $bargain_id = request()->input('bargain_id', 0);
        $goods_id = request()->input('goods_id', 0);
        $goods_type = request()->input('goods_type', 0);
        $attr_id_arr = request()->input('attr_id', '');
        $attr_value_arr = request()->input('attr_value', 0);
        $goods_model = request()->input('goods_model', 0);//商品模式
        $region_id = request()->input('region_id', 0); //地区id

        $result = ['error' => 0, 'message' => '', 'content' => ''];

        //商品模式
        if ($goods_model == 0) {
            $model_name = "";
        } elseif ($goods_model == 1) {
            $model_name = lang('admin/bargain.warehouse');
        } elseif ($goods_model == 2) {
            $model_name = lang('admin/bargain.region');
        }
        $region_name = RegionWarehouse::where('region_id', $region_id)->value('region_name');
        $region_name = $region_name ? $region_name : '';

        //商品基本信息
        $goods_info = Goods::select('market_price', 'shop_price', 'model_attr')->where('goods_id', $goods_id)->first();
        $goods_info = $goods_info ? $goods_info->toArray() : [];

        $attr_arr = [];
        //将属性归类
        if ($attr_id_arr) {
            foreach ($attr_id_arr as $key => $val) {
                $attr_arr[$val][] = $attr_value_arr[$key];
            }
        }

        $attr_spec = [];
        $attribute_array = [];
        $attr_group = [];
        if (count($attr_arr) > 0) {
            //属性数据
            $i = 0;
            foreach ($attr_arr as $key => $val) {
                $attr_info = Attribute::select('attr_name', 'attr_type')->where('attr_id', $key)->first();
                $attr_info = $attr_info ? $attr_info->toArray() : [];

                $attribute_array[$i]['attr_id'] = $key;
                $attribute_array[$i]['attr_name'] = $attr_info['attr_name'];
                $attribute_array[$i]['attr_value'] = $val;
                /* 处理属性图片 start */
                $attr_values_arr = [];
                foreach ($val as $k => $v) {
                    $where_select = [
                        'attr_id' => $key,
                        'attr_value' => $v,
                        'goods_id' => $goods_id
                    ];

//                    $data = bargain_get_goods_attr_id($where_select, ['ga.*, a.attr_type'], [1, 2], 1);
                    $data = $goodsAttrService->getGoodsAttrId($where_select, [1, 2], 1);

                    $data['attr_id'] = $key;
                    $data['attr_value'] = $v;
                    $data['is_selected'] = 1;
                    $attr_values_arr[] = $data;
                }

                $attr_spec[$i] = $attribute_array[$i];
                $attr_spec[$i]['attr_values_arr'] = $attr_values_arr;

                $attribute_array[$i]['attr_values_arr'] = $attr_values_arr;

                if ($attr_info['attr_type'] == 2) {
                    unset($attribute_array[$i]);
                }
                /* 处理属性图片 end */
                $i++;
            }

            //删除复选属性后重设键名
            $new_attribute_array = [];
            foreach ($attribute_array as $key => $val) {
                $new_attribute_array[] = $val;
            }
            $attribute_array = $new_attribute_array;
            //删除复选属性
            $attr_arr = get_goods_unset_attr($goods_id, $attr_arr);
            //将属性组合
            if (count($attr_arr) == 1) {
                foreach (reset($attr_arr) as $key => $val) {
                    $attr_group[][] = $val;
                }
            } else {
                $attr_group = attr_group($attr_arr);
            }

            //取得组合补充数据
            foreach ($attr_group as $key => $val) {
                $group = [];

                //货品信息
                //$product_info = get_product_info_by_attr_bargain($bargain_id, $goods_id, $val, $goods_model, $region_id);
                $product_info = $this->bargainManageService->get_product_info_by_attr_bargain($bargain_id, $goods_id, $val, $goods_model, $region_id);

                if (!empty($product_info)) {
                    $group = $product_info;
                }
                //组合信息
                foreach ($val as $k => $v) {
                    if ($v) {
                        $group['attr_info'][$k]['attr_id'] = $attribute_array[$k]['attr_id'];
                        $group['attr_info'][$k]['attr_value'] = $v;
                    }
                }

                if ($group) {
                    $attr_group[$key] = $group;
                } else {
                    $attr_group = [];
                }
            }
        }

        $group_attr = isset($result['group_attr']) ? $result['group_attr'] : '';
        $goods_attr_price = config('shop.goods_attr_price');
        $lang = lang('admin/bargain');
        $data = compact('region_name', 'goods_model', 'model_name', 'goods_info', 'attr_group', 'attribute_array', 'goods_type', 'goods_id', 'goods_attr_price', 'group_attr', 'lang');
        $result['content'] = view('admin.bargain.attribute_table', $data)->render();

        return response()->json($result);
    }

    /**
     * ajax 点击分类获取下级分类列表
     */
    public function filtercategory()
    {
        $result = ['error' => 0, 'message' => '', 'content' => ''];

        $cat_id = request()->input('cat_id', 0);

        //上级分类列表
        $parent_cat_list = get_select_category($cat_id, 1, true);
        $filter_category_navigation = get_array_category_info($parent_cat_list);
        $cat_nav = "";
        if ($filter_category_navigation) {
            foreach ($filter_category_navigation as $key => $val) {
                if ($key == 0) {
                    $cat_nav .= $val['cat_name'];
                } elseif ($key > 0) {
                    $cat_nav .= " > " . $val['cat_name'];
                }
            }
        } else {
            $cat_nav = lang('admin/bargain.choose_category');
        }
        $result['cat_nav'] = $cat_nav;

        //分类级别
        $filter_category_level = count($parent_cat_list);
        if ($filter_category_level <= 3) {
            $filter_category_list = get_category_list($cat_id, 2);
        } else {
            $filter_category_list = get_category_list($cat_id, 0);
            $filter_category_level -= 1;
        }
        $this->assign('filter_category_level', $filter_category_level); //分类等级
        $this->assign('filter_category_navigation', $filter_category_navigation);
        $this->assign('filter_category_list', $filter_category_list);

        $lang = lang('admin/bargain');
        $data = compact('filter_category_navigation', 'filter_category_list', 'filter_category_level', 'lang');
        $result['content'] = view('admin.bargain.filter_bargain_category', $data)->render();


        return response()->json($result);
    }

    /**
     * ajax 点击获取品牌列表
     */
    public function searchbrand()
    {
        $result = ['error' => 0, 'message' => '', 'content' => ''];

        $goods_id = request()->input('goods_id', 0);

        $filter_brand_list = search_brand_list($goods_id);
        $this->assign('filter_brand_list', $filter_brand_list);
        $lang = lang('admin/bargain');
        $data = compact('filter_brand_list', 'lang');
        $result['content'] = view('admin.bargain.bargain_brand_list', $data)->render();

        return response()->json($result);
    }

    /**
     * ajax 修改活动热销状态
     */
    public function editgoods()
    {
        $result = [
            'error' => 0,
            'message' => '',
            'content' => lang('admin/bargain.update_failure')
        ];

        $id = request()->input('id', 0);
        if ($id) {
            $model = BargainGoods::where('id', $id);
            $is_hot = $model->value('is_hot');
            $is_hot = $is_hot ? $is_hot : 0;
            if ($is_hot == 1) {
                $model->update(['is_hot' => 0]);
            } else {
                $model->update(['is_hot' => 1]);
            }
            $result = [
                'error' => 2,
                'message' => '',
                'content' => lang('admin/bargain.update_success')
            ];
        }

        return response()->json($result);
    }

    /**
     * 关闭，删除砍价商品
     */
    public function removegoods()
    {
        $this->admin_priv('bargain_manage');

        $id = request()->input('id', 0);
        $type = request()->input('type', 0);
        if (empty($id)) {
            return $this->message(L('select_shop'), null, 2);
        }
        if ($type == 'status') {
            BargainGoods::where('id', $id)->update(['status' => 1]);
        } else {
            BargainGoods::where('id', $id)->update(['is_delete' => 1]);
        }
        return redirect()->route('admin/bargain/index');
    }


    /**
     * 参与砍价活动列表
     */
    public function bargainlog()
    {
        $this->admin_priv('bargain_manage');
        $bargain_id = request()->input('bargain_id', 1);
        $status = request()->input('status', 1);

        $offset = $this->pageLimit(route('admin/bargain/bargainlog', ['bargain_id' => $bargain_id, 'status' => $status]), $this->page_num);

        $where = [
            'bargain_id' => $bargain_id,
            'time' => $this->timeRepository->getGmTime(),
            'status' => $status
        ];

        $result = $this->bargainManageService->getBargainlog($where, $offset);

        $list = $result['list'] ?? [];
        $total = $result['total'] ?? 0;

        $this->assign('page', $this->pageShow($total));
        $this->assign('bargain_id', $bargain_id);
        $this->assign('list', $list);
        $this->assign('status', $status);

        return $this->display();
    }

    /**
     * 亲友帮列表
     */
    public function bargain_statistics()
    {
        $this->admin_priv('bargain_manage');

        $id = request()->input('id', 0);

        $list = BargainStatistics::where('bs_id', $id);

        $list = $list->with([
            'getUsers' => function ($query) {
                $query->select('user_id', 'user_name', 'nick_name');
            }
        ]);

        $list = $list->orderBy('add_time', 'DESC');

        $list = $this->baseRepository->getToArrayGet($list);

        if ($list) {
            foreach ($list as $key => $val) {
                $val = $val['get_users'] ? array_merge($val, $val['get_users']) : $val;
                $list[$key]['add_time'] = $this->timeRepository->getLocalDate(config('shop.time_format'), $val['add_time']);
                $list[$key]['subtract_price'] = $this->dscRepository->getPriceFormat($val['subtract_price']);
                $list[$key]['user_name'] = $val['nick_name'] ? $val['nick_name'] : $val['user_name'];

                if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                    $list[$key]['user_name'] = $this->dscRepository->stringToStar($list[$key]['user_name']);
                }
            }
        }

        $this->assign('list', $list);
        return $this->display();
    }
}
