<?php

namespace App\Patch;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Migration_v1_1_8
{
    public function run()
    {
        $name = 'order_return_extend';
        if (!Schema::hasColumn($name, 'aftersn')) {
            Schema::table($name, function (Blueprint $table) {
                $table->string('aftersn', 60)->default('');
            });
        }

        $name = 'templates_left';
        if (Schema::hasColumn($name, 'img_file')) {
            Schema::table($name, function (Blueprint $table) {
                //修改字段结构
                $table->string('img_file', 255)->default('')->change();
            });
        }
    }
}
