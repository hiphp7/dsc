<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GoodsStagesRateSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $file = Storage::disk('local')->exists('seeder/goods_stages_rate.lock.php');
        if (!$file) {

            $this->goodsStagesRate();

            $data = '大商创x https://www.dscmall.cn/';
            Storage::disk('local')->put('seeder/goods_stages_rate.lock.php', $data);
        }
    }

    /**
     * 更新白条记录的商品税率
     */
    private function goodsStagesRate()
    {

        $list = DB::table('order_info')
            ->where('extension_code', '')
            ->where('main_count', 0)
            ->get();

        if ($list) {
            foreach ($list as $key => $val) {
                $goods_id = DB::table('order_goods')
                    ->where('order_id', $val->order_id)
                    ->where('stages_qishu', '>', '-1')
                    ->value('goods_id');
                $goods_id = $goods_id ? $goods_id : 0;

                if ($goods_id > 0) {

                    $stages_rate = DB::table('goods')
                        ->where('goods_id', $goods_id)
                        ->value('stages_rate');

                    DB::table('stages')
                        ->where('order_sn', $val->order_sn)
                        ->update([
                            'stages_rate' => $stages_rate
                        ]);

                    var_dump('商品ID：' . $goods_id . "   订单号：" . $val->order_sn);
                }
            }
        }
    }
}