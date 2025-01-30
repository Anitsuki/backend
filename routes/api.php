<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1/anime')->group(function () {
    Route::get('/schedule', function () {

        if (Cache::has('anime_schedule')) {
            return Cache::get('anime_schedule');
        }


        Artisan::call('app:schedule');
        return Cache::get('anime_schedule');
    });
});
