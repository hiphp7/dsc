<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCronsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'crons';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('cron_id')->comment('自增ID');
            $table->string('cron_code', 20)->index('cron_code')->comment('该插件文件在相应路径下的不包括\'\'.php\'\'部分的文件名，运行该插件将通过该字段的值寻找将运行的文件');
            $table->string('cron_name', 120)->comment('计划任务的名称');
            $table->text('cron_desc')->nullable()->comment('计划任务的描述');
            $table->boolean('cron_order')->default(0)->comment('应该是用了设置计划任务执行的顺序的，即当同时触发2个任务时先执行哪一个，如果一样应该是id在前的先执行暂不确定');
            $table->text('cron_config')->comment('对每次处理的数据的数量的值，类型，名称序列化；比如删几天的日志，每次执行几个商品或文章的处理');
            $table->integer('thistime')->default(0)->comment('该计划任务上次被执行的时间');
            $table->integer('nextime')->index('nextime')->comment('该计划任务下次被执行的时间');
            $table->boolean('day')->comment('如果该字段有值，则计划任务将在每月的这一天执行该计划任务');
            $table->string('week', 1)->comment('如果该字段有值，则计划任务将在每周的这一天执行该计划任务');
            $table->string('hour', 2)->comment('如果该字段有值，则该计划任务将在每天的这个小时段执行该计划任务');
            $table->string('minute')->comment('如果该字段有值，则该计划任务将在每小时的这个分钟段执行该计划任务，该字段的值可以多个，用空格间隔');
            $table->boolean('enable')->default(1)->index('enable')->comment('该计划任务是否开启；0，关闭；1，开启');
            $table->boolean('run_once')->default(0)->comment('执行后是否关闭，这个关闭的意思还得再研究下');
            $table->string('allow_ip', 100)->default('')->comment('允许运行该计划人物的服务器ip');
            $table->string('alow_files')->comment('运行触发该计划人物的文件列表可多个值，为空代表所有许可的文件都可以');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '计划任务'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crons');
    }
}
