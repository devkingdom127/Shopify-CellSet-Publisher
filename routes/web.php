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

Route::group(['middleware'=>'shop_check'],function(){
    Route::get('/', function () {
        //return view('welcome');
        return view('layout.app');
    })->middleware(['auth.shopify'])->name('home');
});

//This will redirect user to login page.
Route::get('/login', function () {
    if (Auth::user()) {
        return redirect()->route('home');
    }
    return view('login');
})->name('login');
/*
Route::get('/', function () {
    return view('layout.app');
})->middleware(['auth.shopify'])->name('home');
*/
