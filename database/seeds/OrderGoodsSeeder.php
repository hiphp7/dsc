<?php

use App\Models\OrderInfo;
use \App\Models\OrderGoods;
use Illuminate\Database\Seeder;

class OrderGoodsSeeder extends Seeder
{
    private $prefix;

    public function __construct()
    {
        $this->prefix = config('database.connections.mysql.prefix');
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->getOrderList();
    }

    /**
     * 获取订单列表
     */
    private function getOrderList()
    {
        $order_list = OrderInfo::where('main_count', 0)
            ->with([
                'getOrderGoodsList'
            ])
            ->get();

        if ($order_list) {
            foreach ($order_list as $key => $val) {

                $goods_list = collect($val->getOrderGoodsList)->toArray() ?? [];

                if ($goods_list) {
                    foreach ($goods_list as $idx => $row) {
                        if ($val['bonus'] > 0) {
                            $goods_bonus = ($row['goods_price'] * $row['goods_number']) / $val['goods_amount'] * $val['bonus'];

                            OrderGoods::where('rec_id', $row['rec_id'])->update([
                                'goods_bonus' => $goods_bonus
                            ]);

                            var_dump("订单商品ID：" . $row['rec_id'] . "，订单ID：" . $val['order_id'] . "，订单红包总额：" . $val['bonus'] . "，均摊红包：" . $goods_bonus);
                        }
                    }
                }
            }
        }
    }
}