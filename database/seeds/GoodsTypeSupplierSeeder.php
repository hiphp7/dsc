<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GoodsTypeSupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->goodsType();
    }

    public function goodsType()
    {
        $admin_list = DB::table('admin_user')->where('suppliers_id', '>', 0)->get();

        if ($admin_list) {
            foreach ($admin_list as $key => $row) {
                DB::table('goods_type')->where('suppliers_id', $row->suppliers_id)
                    ->update([
                        'user_id' => $row->ru_id
                    ]);

                DB::table('goods_type_cat')->where('suppliers_id', $row->suppliers_id)
                    ->update([
                        'user_id' => $row->ru_id
                    ]);
            }
        }
    }
}