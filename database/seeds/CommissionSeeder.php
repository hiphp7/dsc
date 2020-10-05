<?php

use Illuminate\Database\Seeder;
use App\Console\Commands\CommissionServer;

class CommissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->settlement();
    }

    public function settlement()
    {
        $file = Storage::disk('local')->exists('seeder/commission_order.lock.php');
        if (!$file) {

            app(CommissionServer::class)->commissionOrderSettlement(1);

            $data = '大商创x https://www.dscmall.cn/';
            Storage::disk('local')->put('seeder/commission_order.lock.php', $data);
        }
    }
}