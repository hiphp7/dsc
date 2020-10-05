<?php

namespace App\Services\Sale;

use App\Models\OrderGoods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Merchant\MerchantCommonService;

class SaleListManageService
{
    protected $baseRepository;
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

    /* ------------------------------------------------------ */
    //--获取销售明细需要的函数
    /* ------------------------------------------------------ */

    /**
     * 取得销售明细数据信息
     *
     * @param bool $is_pagination
     * @return array
     * @throws \Exception
     */
    public function getSaleList($is_pagination = true)
    {

        /* 时间参数 */
        $filter['start_date'] = empty($_REQUEST['start_date']) ? $this->timeRepository->getLocalStrtoTime('-7 days') : $this->timeRepository->getLocalStrtoTime($_REQUEST['start_date']);
        $filter['end_date'] = empty($_REQUEST['end_date']) ? $this->timeRepository->getLocalStrtoTime('today') : $this->timeRepository->getLocalStrtoTime($_REQUEST['end_date']);
        $filter['goods_sn'] = empty($_REQUEST['goods_sn']) ? '' : trim($_REQUEST['goods_sn']);
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'goods_number' : trim($_REQUEST['sort_by']);

        $filter['order_status'] = isset($_REQUEST['order_status']) && !($_REQUEST['order_status'] == '') ? explode(',', $_REQUEST['order_status']) : '';
        $filter['shipping_status'] = isset($_REQUEST['shipping_status']) && !($_REQUEST['shipping_status'] == '') ? explode(',', $_REQUEST['shipping_status']) : '';
        $filter['time_type'] = !empty($_REQUEST['time_type']) ? intval($_REQUEST['time_type']) : 0;
        $filter['order_referer'] = empty($_REQUEST['order_referer']) ? '' : trim($_REQUEST['order_referer']);

        //卖场 start
        $filter['rs_id'] = empty($_REQUEST['rs_id']) ? 0 : intval($_REQUEST['rs_id']);
        $adminru = get_admin_ru_id();
        if ($adminru['rs_id'] > 0) {
            $filter['rs_id'] = $adminru['rs_id'];
        }
        //卖场 end

        /* 查询数据的条件 */
        $res = OrderGoods::whereRaw(1);

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();

        if ($adminru['ru_id'] > 0) {
            $res = $res->where('ru_id', $adminru['ru_id']);
        }

        if ($filter['goods_sn']) {
            $res = $res->where('goods_sn', $filter['goods_sn']);
        }

        $res = $res->whereHas('getOrder', function ($query) use ($filter) {
            if ($filter['time_type'] == 1) {
                $query = $query->where('add_time', '>=', $filter['start_date'])
                    ->where('add_time', '<=', $filter['end_date']);
            } else {
                $query = $query->where('shipping_time', '>=', $filter['start_date'])
                    ->where('shipping_time', '<=', $filter['end_date']);
            }
            if (!empty($filter['order_status'])) {
                $order_status = $this->baseRepository->getExplode($filter['order_status']);
                $query = $query->whereIn('order_status', $order_status);
            }
            if (!empty($filter['shipping_status'])) {
                $shipping_status = $this->baseRepository->getExplode($filter['shipping_status']);
                $query = $query->whereIn('shipping_status', $shipping_status);
            }
            //主订单下有子订单时，则主订单不显示
            $query = $query->where('main_count', 0);

            if ($filter['order_referer']) {
                if ($filter['order_referer'] == 'pc') {
                    $query = $query->whereNotIn('referer', ['mobile', 'touch', 'ecjia-cashdesk']);
                } else {
                    $query = $query->where('referer', $filter['order_referer']);
                }
            }

            if ($GLOBALS['_CFG']['region_store_enabled']) {
                //卖场
                $query = $query->where(function ($query) {
                    $query->whereHas('getOrderGoods');
                });
                $this->dscRepository->getWhereRsid($query, 'ru_id', $filter['rs_id']);
            }
        });
        //ecmoban模板堂 --zhuo end

        $filter['record_count'] = $res->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        $res = $res->selectRaw('goods_id,order_id,goods_sn,goods_name,goods_number AS goods_num,ru_id,goods_price as sales_price');
        $res = $res->with(['getOrder' => function ($query) {
            $query->selectRaw('order_id,add_time AS sales_time,order_sn,order_status,shipping_status');
        }]);
        $res = $res->orderBy($filter['sort_by'], 'DESC');

        if ($is_pagination) {
            $res = $res->offset($filter['start'])->limit($filter['page_size']);
        }

        $sale_list_data = $this->baseRepository->getToArrayGet($res);

        if (!empty($sale_list_data)) {
            foreach ($sale_list_data as $key => $item) {

                $item['sales_time'] = $item['get_order']['sales_time'] ?? '';
                $item['order_sn'] = $item['get_order']['order_sn'] ?? '';
                $item['order_status'] = $item['get_order']['order_status'] ?? '';

                $item['shipping_status'] = $item['get_order']['shipping_status'] ?? 0;
                $item['shipping_status'] = $item['shipping_status'] ? intval($item['shipping_status']) : 0;

                $item['total_fee'] = intval($item['goods_num'] * $item['sales_price']);

                $item['shop_name'] = $this->merchantCommonService->getShopName($item['ru_id'], 1); //ecmoban模板堂 --zhuo
                $item['sales_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $item['sales_time']);

                $item['order_status_format'] = trim(strip_tags($GLOBALS['_LANG']['os'][$item['order_status']]));
                $item['shipping_status_format'] = trim(strip_tags($GLOBALS['_LANG']['ss'][$item['shipping_status']]));

                $sale_list_data[$key] = $item;
            }
        }
        $arr = ['sale_list_data' => $sale_list_data, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
        return $arr;
    }
}
