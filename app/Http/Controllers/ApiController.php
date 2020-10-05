<?php

namespace App\Http\Controllers;

use App\Models\OpenApi;
use App\Repositories\Common\BaseRepository;

/**
 * OPEN API统一接口
 */
class ApiController extends InitController
{
    protected $baseRepository;

    public function __construct(
        BaseRepository $baseRepository
    )
    {
        $this->baseRepository = $baseRepository;
    }

    public function index()
    {
        require(plugin_path('Dscapi/autoload.php'));

        /* 初始化基础类 */
        $base = new \App\Plugins\Dscapi\app\func\base();

        $base->get_request_filter();

        /* 获取传值 */

        //接口名称
        $method = strtolower(addslashes(request()->input('method', '')));
        //接口名称app_key
        $app_key = addslashes(request()->input('app_key', ''));
        //传输类型
        $format = strtolower(request()->input('format', 'json'));
        //接口类型
        $interface_type = strtolower(request()->input('interface_type', 0));
        //数据
        $data = addslashes_deep(request()->input('data', ''));
        //默认分页当页条数
        $page_size = intval(request()->input('page_size', 15));
        //默认第一页
        $page = intval(request()->input('page', 1));
        //排序字段
        $sort_by = $base->get_addslashes(request()->input('sort_by', ''));
        //排序（升降）
        $sort_order = $base->get_addslashes(request()->input('sort_order', 'ASC'));

        $open_api = OpenApi::where('app_key', $app_key)->where('is_open', 1);
        $open_api = $this->baseRepository->getToArrayFirst($open_api);

        if ($app_key) {
            if (!$open_api) {
                die($GLOBALS['_LANG']['not_interface_power']);
            } else {
                $action_code = isset($open_api['action_code']) && !empty($open_api['action_code']) ? explode(",", $open_api['action_code']) : [];

                if (empty($action_code)) {
                    die($GLOBALS['_LANG']['not_interface_power']);
                } elseif (!in_array($method, $action_code)) {
                    die($GLOBALS['_LANG']['not_interface_power']);
                }
            }
        } else {
            die($GLOBALS['_LANG']['secret_key_not_null']);
        }

        /* JSON或XML格式转换数组 */
        if ($format == "json" && $data) {
            if ($interface_type == 0) {
                $data = stripslashes($data);
                $data = stripslashes($data);
            }

            $data = dsc_decode($data, true);
        } else {
            $data = htmlspecialchars_decode($data);
            $data = dsc_decode(json_encode(simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        }

        /*
         * 相关接口
         *
         * 商品接口 goods
         * 订单接口 order
         * 会员接口 user
         * 地区接口 region
         * 仓库地区接口 warehouse
         * 属性接口 attribute
         * 分类接口 category
         * 品牌接口 brand
         * 快递接口 shipping
         */
        $interface = [
            'goods', 'product', 'order', 'user', 'region',
            'warehouse', 'attribute', 'category', 'brand',
            'shipping'
        ];
        $interface = $base->get_interface_file(plugin_path('Dscapi'), $interface);

        foreach ($interface as $key => $row) {
            require($row);
        }

        /* 商品 */
        if (in_array($method, $goods_action)) {
            $file_type = "goods";
        } /* 商品货品 */ elseif (in_array($method, $product_action)) {
            $file_type = "product";
        } /* 订单 */
        elseif (in_array($method, $order_action)) {
            $file_type = "order";
        } /* 订单 */
        elseif (in_array($method, $user_action)) {
            $file_type = "user";
        } /* 地区 */
        elseif (in_array($method, $region_action)) {
            $file_type = "region";
        } /* 仓库地区 */
        elseif (in_array($method, $warehouse_action)) {
            $file_type = "warehouse";
        } /* 属性 */
        elseif (in_array($method, $attribute_action)) {
            $file_type = "attribute";
        } /* 类目 */
        elseif (in_array($method, $category_action)) {
            $file_type = "category";
        } /* 类目 */
        elseif (in_array($method, $brand_action)) {
            $file_type = "brand";
        } /* 品牌 */
        elseif (in_array($method, $brand_action)) {
            $file_type = "brand";
        } /* 快递方式 */
        elseif (in_array($method, $shipping_action)) {
            $file_type = "shipping";
        } else {
            die($GLOBALS['_LANG']['illegal_entrance']);
        }

        require(plugin_path('Dscapi/view/' . $file_type . '.php'));
    }
}
