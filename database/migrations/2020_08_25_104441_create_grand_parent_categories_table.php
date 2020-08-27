<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGrandParentCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    // CREATE TABLE grand_parent_categories (
    //     id INT NOT NULL AUTO_INCREMENT,
    //     name VARCHAR(255) NOT NULL,
    //     site_category_id INT UNSIGNED NOT NULL,
    //     site_type_id TINYINT UNSIGNED NOT NULL,
    //     PRIMARY KEY (id),
    //     UNIQUE (site_category_id)
    // ) ENGINE=INNODB;
    
    // CREATE TABLE parent_categories (
    //     id INT NOT NULL AUTO_INCREMENT,
    //     name VARCHAR(255) NOT NULL,
    //     site_category_id INT UNSIGNED NOT NULL,
    //     parent_id INT UNSIGNED NOT NULL,
    //     site_type_id TINYINT UNSIGNED NOT NULL,
    //     PRIMARY KEY (id),
    //     UNIQUE (site_category_id)
    // ) ENGINE=INNODB;
    
    // CREATE TABLE child_categories (
    //     id INT NOT NULL AUTO_INCREMENT,
    //     name VARCHAR(255) NOT NULL,
    //     site_category_id INT UNSIGNED NOT NULL,
    //     parent_id INT UNSIGNED NOT NULL,
    //     site_type_id TINYINT UNSIGNED NOT NULL,
    //     PRIMARY KEY (id),
    //     UNIQUE (site_category_id)
    // ) ENGINE=INNODB;

    public function up()
    {
        Schema::create('grand_parent_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();

            $table->unsignedBigInteger('site_category_id')->unique();
            $table->unsignedBigInteger('store_type_id');

            $table->unsignedBigInteger('store_id')->nullable();
            
            $table->foreign('store_type_id')->references('id')->on('store_types');
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
        Schema::dropIfExists('grand_parent_categories');
    }
}
