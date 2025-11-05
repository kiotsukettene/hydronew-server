<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Home\HomeController;


Route::get('/', [HomeController::class, 'index']);
Route::get('/about-us', [HomeController::class, 'aboutUs']);
Route::get('/download', [HomeController::class, 'download']);

