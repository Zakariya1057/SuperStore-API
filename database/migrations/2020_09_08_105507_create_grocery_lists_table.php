<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
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
            $table->unsignedBigInteger('user_id');
            $table->string('identifier')->unique();

            $table->string('status')->default('Not Started');
            
            $table->integer('ticked_off_items');
            $table->integer('total_items');

            $table->decimal('total_price', 7,2)->default(0);
            $table->decimal('old_total_price', 7,2)->nullable()->default(0);

            $table->foreign('user_id')->references('id')->on('users');

            $table->index('user_id');
            $table->index('name');

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
        Schema::dropIfExists('lists');
    }
}
