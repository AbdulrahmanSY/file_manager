<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\RegisterController;
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
    Route::get('get-user',[AuthController::class,'get']);

    Route::prefix('repo')->group(function () {
        Route::post('create-repository',[RepoController::class,'create']);
        Route::delete('delete-repository/{id}',[RepoController::class,'delete']);
        Route::post('add-delete-user-to-repo',[RepoController::class,'addDeleteUserToRepo']);
        Route::get('get-repository',[RepoController::class,'get']);
        Route::post('get-report',[RegisterController::class,'getReport']);
        Route::post('get-users-repo',[RepoController::class,'getUsersRepo']);
    });
    Route::prefix('file')->group(function () {
        Route::post('create-file',[FileController::class,'create']);
        Route::post('delete-file',[FileController::class,'delete']);
        Route::post('update-file',[FileController::class,'update']);
        Route::Post('get-file',[FileController::class,'get']);
        Route::Post('download-file',[FileController::class,'download'])->middleware('check-file-status');
        Route::Post('check-in',[FileController::class,'checkin']);
        Route::Post('check-out',[FileController::class,'checkout']);
    });

});
