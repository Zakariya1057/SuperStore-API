<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
class CreateBarcodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create('barcodes', function (Blueprint $table) {
            $table->id();

            $table->string('type')->nullable();
            $table->string('value')->nullable();

            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('store_type_id');

            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('store_type_id')->references('id')->on('store_types');

            $table->unique(['type', 'value', 'store_type_id', 'product_id'], 'unique_barcode');

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
        Schema::dropIfExists('barcodes');
    }
}
