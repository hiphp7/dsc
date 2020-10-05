<?php

namespace App\Services\Sale;


use App\Models\Category;
use App\Models\Goods;
use App\Models\OrderGoods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Merchant\MerchantCommonService;


class SaleGeneralManageService
{

    protected $baseRepository;
    protected $commonService;
    protected $timeRepository;
    protected $merchantCommonService;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository
    )
    {

        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;

    }

    public function getDataList()
    {

        /* 过滤信息 */
        $filter['keyword'] = !isset($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (!empty($_GET['is_ajax']) && $_GET['is_ajax'] == 1) {
            $filter['keyword'] = !empty($filter['keyword']) ? json_str_iconv($filter['keyword']) : '';
        }

        $filter['goods_sn'] = !isset($_REQUEST['goods_sn']) ? '' : trim($_REQUEST['goods_sn']);
        if (!empty($_GET['is_ajax']) && $_GET['is_ajax'] == 1) {
            $filter['goods_sn'] = !empty($filter['goods_sn']) ? json_str_iconv($filter['goods_sn']) : '';
        }

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'goods_number' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $filter['time_type'] = isset($_REQUEST['time_type']) ? intval($_REQUEST['time_type']) : 1;
        $filter['date_start_time'] = !empty($_REQUEST['start_date']) ? trim($_REQUEST['start_date']) : '';
        $filter['date_end_time'] = !empty($_REQUEST['end_date']) ? trim($_REQUEST['end_date']) : '';

        $filter['cat_name'] = !empty($_REQUEST['cat_name']) ? trim($_REQUEST['cat_name']) : '';

        $filter['order_status'] = isset($_REQUEST['order_status']) ? $_REQUEST['order_status'] : -1;
        $filter['shipping_status'] = isset($_REQUEST['shipping_status']) ? $_REQUEST['shipping_status'] : -1;

        //卖场 start
        $filter['rs_id'] = empty($_REQUEST['rs_id']) ? 0 : intval($_REQUEST['rs_id']);
        $adminru = get_admin_ru_id();
        if ($adminru['rs_id'] > 0) {
            $filter['rs_id'] = $adminru['rs_id'];
        }
        //卖场 end
        $g_res = Goods::whereRaw(1);
        $cat_id = 0;
        if (!empty($filter['cat_name'])) {
            $cat_id = Category::where('cat_name', $filter['cat_name'])->value('cat_id');
            $cat_id = $cat_id ? $cat_id : 0;

            $g_res = $g_res->where('cat_id', $cat_id);
        }

        if ($filter['date_start_time'] == '' && $filter['date_end_time'] == '') {
            $start_time = $this->timeRepository->getLocalMktime(0, 0, 0, $this->timeRepository->getLocalDate('m'), 1, $this->timeRepository->getLocalDate('Y')); //本月第一天
            $end_time = $this->timeRepository->getLocalMktime(0, 0, 0, $this->timeRepository->getLocalDate('m'), $this->timeRepository->getLocalDate('t'), $this->timeRepository->getLocalDate('Y')) + 24 * 60 * 60 - 1; //本月最后一天
        } else {
            $start_time = $this->timeRepository->getLocalStrtoTime($filter['date_start_time']);
            $end_time = $this->timeRepository->getLocalStrtoTime($filter['date_end_time']);
        }

        //查询count的SQL
        $g_res = $g_res->where(function ($query) use ($start_time, $end_time, $filter, $cat_id) {
            $query->whereHas('getOrderGoods', function ($query) use ($start_time, $end_time, $filter, $cat_id) {

                if (!empty($filter['cat_name'])) {
                    $query = $query->where(function ($query) use ($cat_id) {
                        $query->whereHas('getGoods', function ($query) use ($cat_id) {
                            $query->where('cat_id', $cat_id);
                        });
                    });
                }

                if (!empty($filter['goods_sn'])) {
                    $query = $query->where('goods_sn', 'like', '%' . $filter['goods_sn'] . '%');
                }

                $query->whereHas('getOrder', function ($query) use ($start_time, $end_time, $filter) {
                    if ($filter['time_type'] == 1) {
                        $query = $query->where('add_time', '>=', $start_time)
                            ->where('add_time', '<=', $end_time);
                    } else {
                        $query = $query->where('shipping_time', '>=', $start_time)
                            ->where('shipping_time', '<=', $end_time);
                    }
                    if ($filter['order_status'] > -1) {
                        $order_status = $this->baseRepository->getExplode($filter['order_status']);
                        $query = $query->whereIn('order_status', $order_status);
                    }
                    if ($filter['shipping_status'] > -1) {
                        $shipping_status = $this->baseRepository->getExplode($filter['shipping_status']);
                        $query = $query->whereIn('shipping_status', $shipping_status);
                    }
                    $query = $query->where('main_count', 0);
                    if ($GLOBALS['_CFG']['region_store_enabled']) {
                        $query = $query->where(function ($query) {
                            $query->whereHas('getOrderGoods');
                        });
                        $this->dscRepository->getWhereRsid($query, 'ru_id', $filter['rs_id']);
                    }
                });
            });
        });

        $og_res = OrderGoods::whereRaw(1);

        if (!empty($filter['goods_sn'])) {
            $og_res = $og_res->where('goods_sn', 'like', '%' . $filter['goods_sn'] . '%');
        }

        if (!empty($filter['cat_name'])) {
            $og_res = $og_res->where(function ($query) use ($cat_id) {
                $query->whereHas('getGoods', function ($query) use ($cat_id) {
                    $query->where('cat_id', $cat_id);
                });
            });
        }
        $og_res = $og_res->whereHas('getOrder', function ($query) use ($start_time, $end_time, $filter) {
            if ($filter['time_type'] == 1) {
                $query = $query->where('add_time', '>=', $start_time)
                    ->where('add_time', '<=', $end_time);
            } else {
                $query = $query->where('shipping_time', '>=', $start_time)
                    ->where('shipping_time', '<=', $end_time);
            }
            if ($filter['order_status'] > -1) { //多选
                $order_status = $this->baseRepository->getExplode($filter['order_status']);
                $query = $query->whereIn('order_status', $order_status);
            }
            if ($filter['shipping_status'] > -1) { //多选
                $shipping_status = $this->baseRepository->getExplode($filter['shipping_status']);
                $query = $query->whereIn('shipping_status', $shipping_status);
            }

            //主订单下有子订单时，则主订单不显示
            $query = $query->where('main_count', 0);

            if ($GLOBALS['_CFG']['region_store_enabled']) {
                //卖场
                $query = $query->where(function ($query) {
                    $query->whereHas('getOrderGoods');
                });

                $this->dscRepository->getWhereRsid($query, 'ru_id', $filter['rs_id']);
            }
        });

        $filter['record_count'] = $g_res->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        $og_res = $og_res->selectRaw('goods_id,order_id,goods_name,ru_id,goods_sn,goods_price,SUM(goods_price * goods_number) AS total_fee, SUM(goods_number) AS goods_number');
        $og_res = $og_res->with(['getOrder' => function ($query) {
            $query->selectRaw('order_id,add_time,shipping_time');
        }]);
        $og_res = $og_res->groupBy('goods_id')
            ->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset(($filter['page'] - 1) * $filter['page_size'])
            ->limit($filter['page_size']);
        $data_list = $this->baseRepository->getToArrayGet($og_res);

        /* 记录总数 */
        $filter['record_count'] = count($data_list);
        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        for ($i = 0; $i < count($data_list); $i++) {

            $data_list[$i]['add_time'] = $data_list[$i]['get_order']['add_time'] ?: '';
            $data_list[$i]['shipping_time'] = $data_list[$i]['get_order']['shipping_time'] ?: '';

            $data_list[$i]['order_id'] = OrderGoods::selectRaw('GROUP_CONCAT(order_id) AS order_id')
                ->where('goods_id', $data_list[$i]['goods_id'])
                ->groupBy('goods_id')
                ->value('order_id');
            $data_list[$i]['order_id'] = $data_list[$i]['order_id'] ? $data_list[$i]['order_id'] : '';

            $data_list[$i]['order_id'] = explode(",", $data_list[$i]['order_id']);
            $data_list[$i]['order_id'] = array_unique($data_list[$i]['order_id']);

            $data_list[$i]['shop_name'] = $this->merchantCommonService->getShopName($data_list[$i]['ru_id'], 1); //ecmoban模板堂 --zhuo

            $goods_id = $data_list[$i]['goods_id'];
            $data_list[$i]['cat_name'] = Category::whereHas('getGoods', function ($query) use ($goods_id) {
                $query->where('goods_id', $goods_id);
            })->value('cat_name');
            $data_list[$i]['cat_name'] = $data_list[$i]['cat_name'] ? $data_list[$i]['cat_name'] : '';

            if ($filter['time_type'] == 1) {
                $data_list[$i]['add_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $data_list[$i]['add_time']);
            } else {
                $data_list[$i]['shipping_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $data_list[$i]['shipping_time']);
            }
        }

        $arr = ['data_list' => $data_list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;

    }

    /**
     * 取得状态列表
     * @param string $type 类型：all | order | shipping | payment
     */
    public function getStatusList($type = 'all')
    {
        $list = [];

        if ($type == 'all' || $type == 'order') {
            $pre = $type == 'all' ? 'os_' : '';
            foreach ($GLOBALS['_LANG']['os'] as $key => $value) {
                $list[$pre . $key] = $value;
            }
        }

        if ($type == 'all' || $type == 'shipping') {
            $pre = $type == 'all' ? 'ss_' : '';
            foreach ($GLOBALS['_LANG']['ss'] as $key => $value) {
                $list[$pre . $key] = $value;
            }
        }

        if ($type == 'all' || $type == 'payment') {
            $pre = $type == 'all' ? 'ps_' : '';
            foreach ($GLOBALS['_LANG']['ps'] as $key => $value) {
                $list[$pre . $key] = $value;
            }
        }
        return $list;
    }
}
