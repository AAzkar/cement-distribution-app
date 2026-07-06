<?php

use App\Models\AppSetting;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome', ['settings' => AppSetting::current()]);
});
