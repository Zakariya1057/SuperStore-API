<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    // id INT NOT NULL AUTO_INCREMENT,
    // text VARCHAR(5000) NOT NULL,
    // title VARCHAR(100) NULL DEFAULT NULL,
    // rating int null DEFAULT NULL,
    // product_id BIGINT NOT NULL,
    // user_id BIGINT NOT NULL,
    // site_review_id BIGINT NOT NULL,
    // created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    // updated_at TIMESTAMP NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    // PRIMARY KEY (id),
    // UNIQUE (site_review_id)

    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->longText('text');
            $table->string('title');
            $table->integer('rating');

            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('user_id');

            $table->unsignedBigInteger('site_review_id');

            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('user_id')->references('id')->on('users');

            $table->index('product_id');
            $table->index('user_id');

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
        Schema::dropIfExists('reviews');
    }
}
