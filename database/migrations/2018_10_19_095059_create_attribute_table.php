<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAttributeTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'attribute';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('attr_id')->comment('自增ID号');
            $table->integer('cat_id')->unsigned()->default(0)->index('cat_id')->comment('商品类型，同goods_type的cat_id');
            $table->string('attr_name')->default('')->index('attr_name')->comment('属性名称');
            $table->boolean('attr_cat_type')->default(0)->index('attr_cat_type')->comment('分类筛选样式（0-普通， 1-颜色）');
            $table->boolean('attr_input_type')->default(1)->comment('当添加商品时，该属性的添加类别；0，为手工输入；1，为选择输入；2，为多行文本输入');
            $table->boolean('attr_type')->default(1)->index('attr_type')->comment('属性是否多选；0，否；1，是；如果可以多选，则可以自定义属性，并且可以根据值的不同定不同的价');
            $table->text('attr_values')->nullable()->comment('如果attr_input_type为1，即选择输入，则attr_name对应的值的取值就是该字段的值');
            $table->text('color_values')->nullable()->comment('属性颜色值');
            $table->boolean('attr_index')->default(0)->comment('属性是否可以检索；0，不需要检索；1，关键字检索；2，范围检索；该属性应该是如果检索的话，可以通过该属性找到有该属性的商品');
            $table->integer('sort_order')->unsigned()->default(0)->index('sort_order')->comment('属性显示的排序');
            $table->boolean('is_linked')->default(0)->comment('是否关联；0，不关联；1，关联；如果关联，那么用户在购买该商品时，具有有该属性相同值的商品将被推荐给用户');
            $table->boolean('attr_group')->default(0)->index('attr_group')->comment('属性分组，相同的为一个属性组。该值应该取自goods_type的attr_group的值的顺序');
            $table->string('attr_input_category')->default('')->comment('废弃字段');
            $table->integer('cloud_attr_id')->unsigned()->default(0)->index()->comment('贡云属性id');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '属性类型'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('attribute');
    }
}
