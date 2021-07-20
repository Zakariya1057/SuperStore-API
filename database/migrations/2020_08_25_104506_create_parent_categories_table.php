<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
class CreateParentCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('parent_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            $table->unsignedBigInteger('parent_category_id');

            $table->integer('index')->default(0);
            
            $table->string('site_category_id');
            $table->unsignedBigInteger('company_id');

            $table->boolean('enabled')->default(1);
            
            $table->unsignedBigInteger('store_id')->nullable();
            
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('parent_category_id')->references('id')->on('grand_parent_categories');

            $table->unique(['name', 'company_id', 'parent_category_id'], 'parent_categories_unique');
            
            $table->index('site_category_id');
            
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('parent_categories');
    }
}
