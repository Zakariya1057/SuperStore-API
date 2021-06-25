<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateChildNutritions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('child_nutritions', function (Blueprint $table) {
            $table->id();
            
            $table->string('name');
            $table->string('grams')->nullable();
            $table->string('percentage')->nullable();

            $table->unsignedBigInteger('parent_nutrition_id')->nullable();

            $table->unique(['parent_nutrition_id', 'name']);

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
        Schema::dropIfExists('child_nutritions');
    }
}
