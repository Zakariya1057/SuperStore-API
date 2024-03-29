<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
class CreateStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->text('description')->nullable();

            $table->string('manager')->nullable();
            $table->string('telephone')->nullable();

            $table->text('store_image')->nullable();

            $table->text('google_url')->nullable();
            $table->text('uber_url')->nullable();
            $table->text('url')->nullable();

            $table->timestamp('last_checked')->useCurrent();

            $table->string('site_store_id')->unique();

            $table->unsignedBigInteger('supermarket_chain_id');

            $table->boolean('enabled')->default(1);
            
            $table->index('name');
            $table->index('supermarket_chain_id');

            $table->foreign('supermarket_chain_id')->references('id')->on('supermarket_chains');
            
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
        Schema::dropIfExists('stores');
    }
}
