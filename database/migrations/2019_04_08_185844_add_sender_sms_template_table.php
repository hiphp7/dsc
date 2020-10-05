<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSenderSmsTemplateTable extends Migration
{
    protected $table;

    public function __construct()
    {
        $this->table = 'sms_template';
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->table) && !Schema::hasColumn($this->table, 'sender')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->string('sender', 30)->default('')->comment('短信通道号');;
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->dropColumn('sender');
        });
    }
}
