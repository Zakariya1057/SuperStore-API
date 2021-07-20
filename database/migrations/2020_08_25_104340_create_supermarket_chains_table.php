<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSupermarketChainsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('supermarket_chains', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            
            $table->text('description')->nullable();
            
            $table->string('currency');

            $table->string('large_logo')->nullable();
            $table->string('small_logo')->nullable();
            
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('company_id');

            $table->boolean('enabled')->default(1);
            
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('user_id')->references('id')->on('users');

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
        Schema::dropIfExists('supermarket_chains');
    }
}
