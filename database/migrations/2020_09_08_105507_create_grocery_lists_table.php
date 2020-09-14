<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroceryListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create('grocery_lists', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->unsignedBigInteger('user_id')->nullable();

            $table->string('status')->default('Not Started');
            $table->decimal('total_price', 7,2)->default(0);

            $table->unsignedBigInteger('store_id');

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('store_id')->references('id')->on('stores');

            $table->index('user_id');
            $table->index('name');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lists');
    }
}
