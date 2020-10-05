<?php

namespace App\Exports;

use App\Models\Suppliers;
use App\Models\WholesaleOrderInfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Wholesale\ReturnOrderService;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Class WholesaleOrderExport
 * @package App\Exports
 */
class WholesaleOrderExport implements FromCollection
{
    /**
     * @var Application|mixed
     */
    protected $baseRepository;

    /**
     * @var Application|mixed
     */
    protected $timeRepository;

    /**
     * @var Application|mixed
     */
    protected $dscRepository;

    /**
     * @var Application|mixed
     */
    protected $returnOrderService;

    /**
     * WholesaleOrderExport constructor.
     */
    public function __construct()
    {
        $this->baseRepository = app(BaseRepository::class);
        $this->timeRepository = app(TimeRepository::class);
        $this->dscRepository = app(DscRepository::class);
        $this->returnOrderService = app(ReturnOrderService::class);
    }

    /**
     * @return Collection
     * @throws Exception
     */
    public function collection()
    {
        $composite_status = isset($_REQUEST['composite_status']) ? trim($_REQUEST['composite_status']) : -1;
        $suppliers_id = isset($_REQUEST['suppliers_id']) ? trim($_REQUEST['suppliers_id']) : -1;

        $res = WholesaleOrderInfo::select('order_id', 'order_sn', 'add_time', 'order_status', 'consignee', 'address', 'email', 'mobile', 'order_amount', 'pay_id', 'pay_time', 'pay_status', 'user_id', 'suppliers_id', 'shipping_status', 'province', 'city', 'district', 'street')->where('main_order_id', '>', 0);

        if ($suppliers_id > 0) {
            $res = $res->where('suppliers_id', $suppliers_id);
        }

        if ($composite_status == 100) {

            $res = $res->where('order_status', 0)->where('pay_status', 0)->whereIn('shipping_status', ['1', '2', '0']);

        } elseif ($composite_status == 101) {

            $res = $res->where('order_status', 0)->whereIn('pay_status', ['1', '2'])->whereIn('shipping_status', ['0', '3', '5']);

        } elseif ($composite_status == 102) {

            $res = $res->whereIn('order_status', ['1'])->whereIn('pay_status', ['1', '2'])->whereIn('shipping_status', ['1', '2']);

        } elseif ($composite_status == 104) {

            $res = $res->whereIn('order_status', ['4'])->whereIn('pay_status', ['4', '2', '5'])->whereIn('shipping_status', ['1', '0']);
        }
        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        $lang_goods = lang('admin/common.download_goods');

        if (isset($res) && !empty($res)) {
            foreach ($res as $key => $val) {
                $res[$key]['suppliers_name'] = Suppliers::where('suppliers_id', $val['suppliers_id'])->value('suppliers_name');

                $goods_list = $this->returnOrderService->getwholesalegoodsorder($val['order_id']);

                // 订单商品信息
                $goods_info = '';
                if (!empty($goods_list['goods_list'])) {
                    foreach ($goods_list['goods_list'] as $j => $g) {
                        if (!empty($g['goods_attr'])) {
                            $g['goods_unit'] = isset($g['goods_unit']) ?? '';
                            $goods_info .= $g['goods_name'] . "(" . $lang_goods['export_prize'] . $g['goods_price'] . " " . $lang_goods['export_num'] . $g['goods_number'] . $g['goods_unit'] . ' ' . rtrim($g['goods_attr']) . ")" . "\r\n";
                        } else {
                            $g['goods_unit'] = isset($g['goods_unit']) ?? '';
                            $goods_info .= $g['goods_name'] . "(" . $lang_goods['export_prize'] . $g['goods_price'] . " " . $lang_goods['export_num'] . $g['goods_number'] . $g['goods_unit'] . ")" . "\r\n";
                        }
                    }
                    $goods_info = rtrim($goods_info); // 去除最末位换行符
                    $res[$key]['goods_info'] = "\"$goods_info\""; // 转义字符是关键 不然不是表格内换行
                }

                $res[$key]['order_status'] = $GLOBALS['_LANG']['ps'][$val['pay_status']];
            }
        }

        $cellData = [
            [
                $GLOBALS['_LANG']['suppliers_name'],
                $GLOBALS['_LANG']['goods_name'],
                $GLOBALS['_LANG']['order_sn'],
                $GLOBALS['_LANG']['consignee'],
                $GLOBALS['_LANG']['address'],
                $GLOBALS['_LANG']['label_mobile'],
                $GLOBALS['_LANG']['amount_label'],
                $GLOBALS['_LANG']['all_status'],
            ]
        ];

        if ($res) {
            $idx = 1;
            foreach ($res as $k => $row) {
                $cellData[$idx]['shop_name'] = $row['suppliers_name'];
                $cellData[$idx]['goods_name'] = $row['goods_info'];
                $cellData[$idx]['order_sn'] = $row['order_sn'];
                $cellData[$idx]['goods_num'] = $row['consignee'];
                $cellData[$idx]['sales_price'] = $row['address'];
                $cellData[$idx]['mobile'] = $row['mobile'];
                $cellData[$idx]['amount_label'] = $row['order_amount'];
                $cellData[$idx]['all_status'] = $row['order_status'];
                $idx++;
            }
        }

        return collect($cellData);
    }
}
