<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateFlyersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('flyers', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('week')->nullable();

            $table->string('url');

            $table->unsignedBigInteger('store_id');

            $table->date('valid_from');
            $table->date('valid_to');

            $table->string('site_flyer_id');

            $table->unique(['name', 'site_flyer_id', 'store_id', 'valid_from', 'valid_to'],'flyer_unique');

            $table->foreign('store_id')->references('id')->on('stores');

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
        Schema::dropIfExists('flyers');
    }
}
