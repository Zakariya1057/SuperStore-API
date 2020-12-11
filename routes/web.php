<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('home', ['title' => 'Home - SuperStore', 'description' => '', 'keywords' => '']);
});

Route::get('/terms-conditions', function () {
    return view('terms', ['title' => 'Terms And Condition - SuperStore', 'description' => '', 'keywords' => '']);
});

Route::get('/privacy-policy', function () {
    return view('privacy', ['title' => 'Privacy Policy - SuperStore', 'description' => '', 'keywords' => '']);
});

// Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
