<?php

use App\Models\OrderInfo;
use Illuminate\Database\Seeder;

class OrderInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->updateOrderStatus();
    }

    /**
     * 更新订单状态
     */
    private function updateOrderStatus()
    {
        $res = OrderInfo::where('main_count', '>', 0)
            ->where('order_status', OS_UNCONFIRMED)
            ->where('shipping_status', SS_UNSHIPPED)
            ->where('pay_status', PS_UNPAYED);

        $res = $res->get();
        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $key => $row) {
                $row = collect($row)->toArray();
                OrderInfo::where('order_id', $row['order_id'])->update([
                    'order_status' => OS_CONFIRMED
                ]);
            }
        }
    }
}