<?php

namespace App\Modules\Suppliers\Controllers;

use App\Libraries\Image;
use App\Models\AdminUser;
use App\Models\ShopConfig;
use App\Models\Wholesale;
use App\Models\WholesaleOrderInfo;
use App\Repositories\Common\BaseRepository;
use App\Services\Common\CommonManageService;

/**
 * 记录管理员操作日志
 */
class IndexController extends InitController
{
    protected $baseRepository;
    protected $image;

    public function __construct(
        BaseRepository $baseRepository,
        Image $image
    )
    {
        $this->baseRepository = $baseRepository;
        $this->image = $image;
    }

    public function index()
    {
        $adminru = get_admin_ru_id();

        if ($_REQUEST['act'] == '') {
            $_REQUEST['act'] = 'index';
        }

        /* ------------------------------------------------------ */
        //-- 框架
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'index') {
            //商家信息
            $suppliers_info = get_suppliers_info($adminru['suppliers_id']);
            $suppliers_info['last_login'] = local_date($GLOBALS['_CFG']['time_format'], session('supply_last_check'));
            $suppliers_info['supply_name'] = session('supply_name');
            $this->smarty->assign('suppliers_info', $suppliers_info);

            //获取商家为处理信息
            $order_handle = $this->get_order_handlearr();
            $this->smarty->assign('order_handle', $order_handle);

            /* 单品销售数量排名 */
            $goods_info = Wholesale::where('suppliers_id', $adminru['suppliers_id'])
                ->where('is_delete', 0)
                ->where('enabled', 1)
                ->orderBy('sales_volume', 'desc')
                ->take(10);
            $goods_info = $this->baseRepository->getToArrayGet($goods_info);

            $this->smarty->assign('goods_info', $goods_info);

            return $this->smarty->display('index.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 清除缓存
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'clear_cache') {
            ShopConfig::where('code', 'is_downconfig')
                ->update([
                    'value' => 0
                ]);

            cache()->flush();

            clear_all_files('', 'suppliers');
            return sys_msg($GLOBALS['_LANG']['caches_cleared']);
        }

        /* ------------------------------------------------------ */
        //-- 设置主页面统计图表
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'set_statistical_chart') {
            $type = empty($_REQUEST['type']) ? '' : trim($_REQUEST['type']);
            $date = empty($_REQUEST['date']) ? '' : trim($_REQUEST['date']);

            //格林威治时间与本地时间差
            $timezone = session()->has('timezone') ? session('timezone') : $GLOBALS['_CFG']['timezone'];
            $time_diff = $timezone * 3600;

            $data = array();

            if ($date == 'week') {
                $day_num = 7;
            }
            if ($date == 'month') {
                $day_num = 30;
            }
            if ($date == 'year') {
                $day_num = 180;
            }

            $date_end = local_mktime(0, 0, 0, local_date('m'), local_date('d') + 1, local_date('Y')) - 1;
            $date_start = $date_end - 3600 * 24 * $day_num;

            //获取系统数据 start
            $no_main_order = " AND (SELECT count(*) FROM " . $GLOBALS['dsc']->table('wholesale_order_info') . " AS oi2 WHERE oi2.main_order_id = oi.order_id) = 0 ";  //主订单下有子订单时，则主订单不显示    if ($adminru['ru_id'] > 0) {
            $sql = 'SELECT DATE_FORMAT(FROM_UNIXTIME(oi.add_time + ' . $time_diff . '),"%y-%m-%d") AS day,COUNT(*) AS count,SUM(oi.order_amount) AS money FROM ' . $this->dsc->table('wholesale_order_info') . " AS oi" . ' WHERE oi.pay_status = 2 AND oi.add_time BETWEEN ' . $date_start . ' AND ' . $date_end . $no_main_order . ' AND oi.suppliers_id = "' . $adminru['suppliers_id'] . '" GROUP BY day ORDER BY day ASC ';
            $result = $this->db->getAll($sql);

            $orders_series_data = [];
            $sales_series_data = [];
            if ($result) {
                foreach ($result as $key => $row) {
                    $orders_series_data[$row['day']] = intval($row['count']);
                    $sales_series_data[$row['day']] = floatval($row['money']);
                }
            }

            for ($i = 1; $i <= $day_num; $i++) {
                $day = local_date("y-m-d", local_strtotime(" - " . ($day_num - $i) . " days"));
                if (empty($orders_series_data[$day])) {
                    $orders_series_data[$day] = 0;
                    $sales_series_data[$day] = 0;
                }
                //输出时间
                $day = local_date("m-d", local_strtotime($day));
                $orders_xAxis_data[] = $day;
                $sales_xAxis_data[] = $day;
            }

            //获取系统数据 end
            //图表公共数据 start
            $toolbox = array(
                'show' => true,
                'orient' => 'vertical',
                'x' => 'right',
                'y' => '60',
                'feature' => array(
                    'magicType' => array(
                        'show' => true,
                        'type' => array('line', 'bar')
                    ),
                    'saveAsImage' => array(
                        'show' => true
                    )
                )
            );
            $tooltip = array('trigger' => 'axis',
                'axisPointer' => array(
                    'lineStyle' => array(
                        'color' => '#6cbd40'
                    )
                )
            );
            $xAxis = array(
                'type' => 'category',
                'boundaryGap' => false,
                'axisLine' => array(
                    'lineStyle' => array(
                        'color' => '#ccc',
                        'width' => 0
                    )
                ),
                'data' => array());
            $yAxis = array(
                'type' => 'value',
                'axisLine' => array(
                    'lineStyle' => array(
                        'color' => '#ccc',
                        'width' => 0
                    )
                ),
                'axisLabel' => array(
                    'formatter' => ''));
            $series = array(
                array(
                    'name' => '',
                    'type' => 'line',
                    'itemStyle' => array(
                        'normal' => array(
                            'color' => '#6cbd40',
                            'lineStyle' => array(
                                'color' => '#6cbd40'
                            )
                        )
                    ),
                    'data' => array(),
                    'markPoint' => array(
                        'itemStyle' => array(
                            'normal' => array(
                                'color' => '#6cbd40'
                            )
                        ),
                        'data' => array(
                            array(
                                'type' => 'max',
                                'name' => lang('suppliers/index.max_value')),
                            array(
                                'type' => 'min',
                                'name' => lang('suppliers/index.min_value'))
                        )
                    )
                ),
                array(
                    'type' => 'force',
                    'name' => '',
                    'draggable' => false,
                    'nodes' => array(
                        'draggable' => false
                    )
                )
            );
            $calculable = true;
            $legend = array('data' => array());
            //图表公共数据 end
            //订单统计
            if ($type == 'order') {
                $xAxis['data'] = $orders_xAxis_data;
                $yAxis['formatter'] = lang('suppliers/index.individual');
                ksort($orders_series_data);
                $series[0]['name'] = lang('suppliers/index.order_number');
                $series[0]['data'] = array_values($orders_series_data);
                $data['series'] = $series;
            }

            //销售统计
            if ($type == 'sale') {
                $xAxis['data'] = $sales_xAxis_data;
                $yAxis['formatter'] = lang('suppliers/index.element');
                ksort($sales_series_data);
                $series[0]['name'] = lang('suppliers/index.sales_volume');
                $series[0]['data'] = array_values($sales_series_data);
                $data['series'] = $series;
            }

            //整理数据
            $data['tooltip'] = $tooltip;
            $data['legend'] = $legend;
            $data['toolbox'] = $toolbox;
            $data['calculable'] = $calculable;
            $data['xAxis'] = $xAxis;
            $data['yAxis'] = $yAxis;

            //输出数据
            return response()->json($data);
        }

        /* ------------------------------------------------------ */
        //-- 管理员头像上传
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'upload_store_img') {
            $result = array("error" => 0, "message" => "", "content" => "");
            $admin_id = get_admin_id();

            if ($_FILES['img']['name']) {
                $dir = 'store_user';

                $img_name = $this->image->upload_image($_FILES['img'], $dir);

                if ($img_name) {
                    $result['error'] = 1;
                    $result['content'] = get_image_path($img_name);
                    //删除原图片

                    $store_user_img = AdminUser::where('user_id', $admin_id)->value('admin_user_img');
                    dsc_unlink(storage_public($store_user_img));

                    //插入新图片
                    AdminUser::where('user_id', $admin_id)->update([
                        'admin_user_img' => $img_name
                    ]);
                }
            }

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 登录状态
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'login_status') {
            $status = app(CommonManageService::class)->loginStatus();
            return response()->json(['status' => $status]);
        }
    }

    /**
     * 获取供应商未处理信息
     *
     * @return array
     */
    private function get_order_handlearr()
    {
        $adminru = get_admin_ru_id();

        //待付款订单 no_pay
        //待发货 no_shipping
        //未退货未退款 no_return
        $order_info = WholesaleOrderInfo::mainOrderCount()
            ->selectRaw('SUM(CASE WHEN pay_status = 0 THEN 1 ELSE 0 END) AS no_pay, SUM(CASE WHEN shipping_status = 0 AND pay_status = 2 THEN 1 ELSE 0 END) AS no_shipping, SUM(CASE WHEN order_status = 4 AND pay_status = 2 THEN 1 ELSE 0 END) AS no_return')
            ->where('suppliers_id', $adminru['suppliers_id']);
        $order_info = $this->baseRepository->getToArrayFirst($order_info);

        //出售中的商品 is_enabled
        $is_enabled = "SUM(CASE WHEN enabled = 1 AND review_status = 3 AND is_delete = 0 THEN 1 ELSE 0 END) AS is_enabled, ";

        //待审核未审核商品商品
        $is_review_status = "SUM(CASE WHEN review_status != 3 AND is_delete = 0 THEN 1 ELSE 0 END) AS is_review_status, ";

        //回收站商品
        $is_delete = "SUM(CASE WHEN is_delete = 1 THEN 1 ELSE 0 END) AS is_delete, ";

        //已下架的商品
        $is_on_sale = "SUM(CASE WHEN enabled = 0 THEN 1 ELSE 0 END) AS is_on_sale, ";

        //库存警告
        $is_warn = "SUM(CASE WHEN goods_number <= warn_number THEN 1 ELSE 0 END) AS is_warn";

        $goods_info = Wholesale::selectRaw($is_enabled . $is_review_status . $is_delete . $is_on_sale . $is_warn)
            ->where('suppliers_id', $adminru['suppliers_id']);
        $goods_info = $this->baseRepository->getToArrayFirst($goods_info);

        $arr = $this->baseRepository->getArrayMerge($order_info, $goods_info);

        return $arr;
    }
}
