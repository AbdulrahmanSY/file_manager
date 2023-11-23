<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\RepoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('register',[AuthController::class,'register']);
Route::post('verify',[AuthController::class,'verify']);
Route::post('login',[AuthController::class,'login']);

Route::middleware('auth:sanctum')->group( function () {
    Route::get('logout',[AuthController::class,'logout']);

    Route::prefix('repo')->group(function () {
        Route::post('create-repository',[RepoController::class,'create']);
        Route::delete('delete-repository/{id}',[RepoController::class,'delete']);
//        Route::put('update-repository',[RepoController::class,'update']);
        Route::get('get-repository',[RepoController::class,'get']);
    });
    Route::prefix('file')->group(function () {
        Route::post('create-file',[FileController::class,'create']);
        Route::delete('delete-file/{id}',[FileController::class,'delete']);
        Route::put('update-file',[FileController::class,'update']);
        Route::get('get-file',[FileController::class,'get']);
    });

});
