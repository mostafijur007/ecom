<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('l5-swagger.default.api');
    // return view('welcome');
});
