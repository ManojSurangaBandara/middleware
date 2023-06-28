<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserCheckController;


Route::get('/check/{email}', [UserCheckController::class, 'checkEmailExists']);
Route::get('/reset/{email}/{newpassword}', [UserCheckController::class, 'resetPassword']);
Route::get('/check2/{email}/{newpassword}', [UserCheckController::class, 'changeADUserPassword']);
