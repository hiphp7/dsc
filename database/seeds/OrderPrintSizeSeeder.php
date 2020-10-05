<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderPrintSizeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->updateSpecification();
    }

    /**
     * 打印规格设置 A4规格统一
     */
    private function updateSpecification()
    {
        DB::table('order_print_size')->where('specification', 'A4纸张')->update([
            'specification' => 'A4'
        ]);
    }

}
