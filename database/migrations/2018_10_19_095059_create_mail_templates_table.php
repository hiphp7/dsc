<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMailTemplatesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'mail_templates';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('template_id')->comment('自增ID');
            $table->string('template_code', 30)->default('')->unique('template_code')->comment('模板字符串名称，主要用于插件言语包时匹配语言包文件等用途');
            $table->boolean('is_html')->default(0)->comment('邮件是否是html格式；0，否；1，是');
            $table->string('template_subject', 200)->default('')->comment('该邮件模板的邮件主题');
            $table->text('template_content')->nullable()->comment('邮件模板的内容');
            $table->integer('last_modify')->unsigned()->default(0)->comment('最后一次修改模板的时间');
            $table->integer('last_send')->unsigned()->default(0)->comment('最近一次发送的时间，好像仅在杂志才记录');
            $table->string('type', 10)->default('0')->index('type')->comment('该邮件模板的邮件类型；共2个类型；magazine，杂志订阅；template，关注订阅');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '邮件模板'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('mail_templates');
    }
}
