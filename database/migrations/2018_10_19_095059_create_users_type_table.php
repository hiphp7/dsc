<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUsersTypeTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'users_type';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('user_id');
            $table->boolean('enterprise_personal');
            $table->string('companyname');
            $table->string('contactname');
            $table->string('companyaddress');
            $table->integer('industry')->unsigned();
            $table->string('surname', 150);
            $table->string('givenname', 150);
            $table->boolean('agreement');
            $table->integer('country')->unsigned()->index('country');
            $table->integer('province')->unsigned()->index('province');
            $table->integer('city')->unsigned()->index('city');
            $table->integer('district')->unsigned()->index('district');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '此表已废弃'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users_type');
    }
}
