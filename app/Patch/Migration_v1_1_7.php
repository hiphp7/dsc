<?php

namespace App\Patch;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Migration_v1_1_7
{
    public function run()
    {
        // 判断 action_code 表字段是否存在添加
        if (Schema::hasColumn('admin_action', 'action_code')) {
            Schema::table('admin_action', function (Blueprint $table) {
                $table->string('action_code', 50)->change();
            });
        }
    }
}
