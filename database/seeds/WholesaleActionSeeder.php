<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WholesaleActionSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->wholesaleAction();
    }

    /**
     * 供应链
     */
    protected function wholesaleAction()
    {
        $action_id = DB::table('admin_action')->where('action_code', 'merchants')->value('action_id');

        $count = DB::table('admin_action')->where('action_code', 'supplier_apply')->count();
        if ($count <= 0) {
            // 默认数据
            $other = [
                'parent_id' => $action_id,
                'action_code' => 'supplier_apply'
            ];
            DB::table('admin_action')->insert($other);
        }

        DB::table('admin_action')->where('action_code', 'suppliers_manage')->delete();

        $count = DB::table('admin_action')->where('action_code', 'suppliers_goods')->count();
        if ($count <= 0) {
            // 默认数据
            $other = [
                'action_code' => 'suppliers_goods',
                'seller_show' => 2
            ];
            $action_id = DB::table('admin_action')->insertGetId($other);
        } else {
            $action_id = DB::table('admin_action')->where('action_code', 'suppliers_goods')->value('action_id');
        }

        if ($action_id) {

            $count = DB::table('admin_action')->where('action_code', 'suppliers_goods_list')->count();
            if ($count <= 0) {
                // 默认数据
                $other = [
                    'action_code' => 'suppliers_goods_list',
                    'parent_id' => $action_id,
                    'seller_show' => 2
                ];
                DB::table('admin_action')->insert($other);
            }

            $count = DB::table('admin_action')->where('action_code', 'suppliers_goods_type')->count();
            if ($count <= 0) {
                // 默认数据
                $other = [
                    'action_code' => 'suppliers_goods_type',
                    'parent_id' => $action_id,
                    'seller_show' => 2
                ];
                DB::table('admin_action')->insert($other);
            }

            $count = DB::table('admin_action')->where('action_code', 'suppliers_attr_list')->count();
            if ($count <= 0) {
                // 默认数据
                $other = [
                    'action_code' => 'suppliers_attr_list',
                    'parent_id' => $action_id,
                    'seller_show' => 2
                ];
                DB::table('admin_action')->insert($other);
            }

            $count = DB::table('admin_action')->where('action_code', 'suppliers_gallery_album')->count();
            if ($count <= 0) {
                // 默认数据
                $other = [
                    'action_code' => 'suppliers_gallery_album',
                    'parent_id' => $action_id,
                    'seller_show' => 2
                ];
                DB::table('admin_action')->insert($other);
            }

            $count = DB::table('admin_action')->where('action_code', 'standard_goods_lib')->count();
            if ($count <= 0) {
                // 默认数据
                $other = [
                    'action_code' => 'standard_goods_lib',
                    'parent_id' => $action_id,
                    'seller_show' => 0
                ];
                DB::table('admin_action')->insert($other);
            }
        }

        $count = DB::table('admin_action')->where('action_code', 'suppliers_order_manage')->count();
        if ($count <= 0) {
            // 默认数据
            $other = [
                'action_code' => 'suppliers_order_manage',
                'seller_show' => 2
            ];
            $action_id = DB::table('admin_action')->insertGetId($other);
        } else {
            $action_id = DB::table('admin_action')->where('action_code', 'suppliers_order_manage')->value('action_id');
        }

        if ($action_id) {
            $count = DB::table('admin_action')->where('action_code', 'suppliers_order_view')->count();
            if ($count <= 0) {
                // 默认数据
                $other = [
                    'action_code' => 'suppliers_order_view',
                    'parent_id' => $action_id,
                    'seller_show' => 2
                ];
                DB::table('admin_action')->insert($other);
            }

            $count = DB::table('admin_action')->where('action_code', 'suppliers_purchase')->count();
            if ($count <= 0) {
                // 默认数据
                $other = [
                    'action_code' => 'suppliers_purchase',
                    'parent_id' => $action_id,
                    'seller_show' => 0
                ];
                DB::table('admin_action')->insert($other);
            }

            $count = DB::table('admin_action')->where('action_code', 'suppliers_order_back_apply')->count();
            if ($count <= 0) {
                // 默认数据
                $other = [
                    'action_code' => 'suppliers_order_back_apply',
                    'parent_id' => $action_id,
                    'seller_show' => 2
                ];
                DB::table('admin_action')->insert($other);
            }

            $count = DB::table('admin_action')->where('action_code', 'suppliers_delivery_view')->count();
            if ($count <= 0) {
                // 默认数据
                $other = [
                    'action_code' => 'suppliers_delivery_view',
                    'parent_id' => $action_id,
                    'seller_show' => 2
                ];
                DB::table('admin_action')->insert($other);
            }
        }

        $count = DB::table('admin_action')->where('action_code', 'suppliers')->count();
        if ($count <= 0) {
            // 默认数据
            $other = [
                'action_code' => 'suppliers',
                'seller_show' => 2
            ];
            $action_id = DB::table('admin_action')->insertGetId($other);
        } else {
            $action_id = DB::table('admin_action')->where('action_code', 'suppliers')->value('action_id');
        }

        if ($action_id) {
            $count = DB::table('admin_action')->where('action_code', 'suppliers_account')->count();
            if ($count <= 0) {
                // 默认数据
                $other = [
                    'action_code' => 'suppliers_account',
                    'parent_id' => $action_id,
                    'seller_show' => 2
                ];
                DB::table('admin_action')->insert($other);
            }

            $count = DB::table('admin_action')->where('action_code', 'suppliers_commission')->count();
            if ($count <= 0) {
                // 默认数据
                $other = [
                    'action_code' => 'suppliers_commission',
                    'parent_id' => $action_id,
                    'seller_show' => 2
                ];
                DB::table('admin_action')->insert($other);
            }

            $count = DB::table('admin_action')->where('action_code', 'suppliers_list')->count();
            if ($count <= 0) {
                // 默认数据
                $other = [
                    'action_code' => 'suppliers_list',
                    'parent_id' => $action_id,
                    'seller_show' => 0
                ];
                DB::table('admin_action')->insert($other);
            }
        }

        $count = DB::table('admin_action')->where('action_code', 'suppliers_sale_order_stats')->count();
        if ($count <= 0) {
            // 默认数据
            $other = [
                'action_code' => 'suppliers_sale_order_stats',
                'seller_show' => 2
            ];
            $action_id = DB::table('admin_action')->insertGetId($other);
        } else {
            $action_id = DB::table('admin_action')->where('action_code', 'suppliers_sale_order_stats')->value('action_id');
        }

        if ($action_id) {
            $count = DB::table('admin_action')->where('action_code', 'suppliers_stats')->count();
            if ($count <= 0) {
                // 默认数据
                $other = [
                    'action_code' => 'suppliers_stats',
                    'parent_id' => $action_id,
                    'seller_show' => 2
                ];
                DB::table('admin_action')->insert($other);
            }

            $count = DB::table('admin_action')->where('action_code', 'suppliers_sale_list')->count();
            if ($count <= 0) {
                // 默认数据
                $other = [
                    'action_code' => 'suppliers_sale_list',
                    'parent_id' => $action_id,
                    'seller_show' => 2
                ];
                DB::table('admin_action')->insert($other);
            }
        }

        $count = DB::table('admin_action')->where('action_code', 'suppliers_priv_manage')->count();
        if ($count <= 0) {
            // 默认数据
            $other = [
                'action_code' => 'suppliers_priv_manage',
                'seller_show' => 2
            ];
            $action_id = DB::table('admin_action')->insertGetId($other);
        } else {
            $action_id = DB::table('admin_action')->where('action_code', 'suppliers_priv_manage')->value('action_id');
        }

        if ($action_id) {
            $count = DB::table('admin_action')->where('action_code', 'suppliers_logs_manage')->count();
            if ($count <= 0) {
                // 默认数据
                $other = [
                    'action_code' => 'suppliers_logs_manage',
                    'parent_id' => $action_id,
                    'seller_show' => 2
                ];
                DB::table('admin_action')->insert($other);
            }

            $count = DB::table('admin_action')->where('action_code', 'suppliers_child_manage')->count();
            if ($count <= 0) {
                // 默认数据
                $other = [
                    'action_code' => 'suppliers_child_manage',
                    'parent_id' => $action_id,
                    'seller_show' => 2
                ];
                DB::table('admin_action')->insert($other);
            }

            $count = DB::table('admin_action')->where('action_code', 'suppliers_privilege')->count();
            if ($count <= 0) {
                // 默认数据
                $other = [
                    'action_code' => 'suppliers_privilege',
                    'parent_id' => $action_id,
                    'seller_show' => 2
                ];
                DB::table('admin_action')->insert($other);
            }
        }

        $count = DB::table('admin_action')->where('action_code', 'suppliers_sys_manage')->count();
        if ($count <= 0) {
            // 默认数据
            $other = [
                'action_code' => 'suppliers_sys_manage',
                'seller_show' => 2
            ];
            $action_id = DB::table('admin_action')->insertGetId($other);
        } else {
            $action_id = DB::table('admin_action')->where('action_code', 'suppliers_sys_manage')->value('action_id');
        }

        if ($action_id) {
            $count = DB::table('admin_action')->where('action_code', 'suppliers_order_print_setting')->count();
            if ($count <= 0) {
                // 默认数据
                $other = [
                    'action_code' => 'suppliers_order_print_setting',
                    'parent_id' => $action_id,
                    'seller_show' => 2
                ];
                DB::table('admin_action')->insert($other);
            }
        }

        DB::table('admin_action')->where('action_code', 'supply_and_demand')->delete();
        DB::table('admin_action')->where('action_code', 'wholesale_purchase')->delete();
        DB::table('admin_action')->where('action_code', 'wholesale_order')->delete();
        DB::table('admin_action')->where('action_code', 'whole_sale')->delete();

        DB::table('shop_config')->where('code', 'wholesale_user_rank')->update([
            'type' => 'select'
        ]);
    }
}
