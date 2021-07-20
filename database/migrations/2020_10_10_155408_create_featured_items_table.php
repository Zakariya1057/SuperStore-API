<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateFeaturedItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('featured_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('featured_id');
            $table->string('type');

            $table->unsignedBigInteger('region_id');
            $table->unsignedBigInteger('company_id');

            $table->integer('week');
            $table->integer('year');

            $table->index('week');
            $table->index('year');
            $table->index('type');

            $table->foreign('region_id')->references('id')->on('regions');
            $table->foreign('company_id')->references('id')->on('companies');
            $table->unique(['company_id', 'featured_id', 'type', 'region_id']);

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
        Schema::dropIfExists('featured_items');
    }
}
