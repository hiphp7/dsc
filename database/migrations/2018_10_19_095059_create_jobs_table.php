<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateJobsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'jobs';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create('jobs', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->string('queue', 100);
            $table->text('payload');
            $table->boolean('attempts');
            $table->integer('reserved_at')->unsigned()->nullable();
            $table->integer('available_at')->unsigned();
            $table->integer('created_at')->unsigned();
            $table->index(['queue','reserved_at'], 'jobs_queue_reserved_at_index');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '队列'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('jobs');
    }
}
