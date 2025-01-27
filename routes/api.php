<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FilesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::post('/registration', [AuthController::class, 'reg']);
Route::post('/authorization', [AuthController::class, 'aut']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'out']);
    Route::post('/files', [FilesController::class, 'store']);
    Route::post('/files/{file_id}', [FilesController::class, 'edit']);
    Route::post('/files/{file_id}/access', [FilesController::class, 'access_add']);
    Route::get('/files/disk', [FilesController::class, 'access_show']);
    Route::get('/shared', [FilesController::class, 'access_user']);
    Route::delete('/files/{file_id}/access', [FilesController::class, 'access_del']);
    Route::delete('/files/{file_id}', [FilesController::class, 'delete']);
    Route::get('/files/{file_id}', [FilesController::class, 'download']);
});
