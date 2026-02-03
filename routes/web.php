<?php

use App\Http\Controllers\TransferController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TransferController::class, 'index']);
Route::get('/herd-projects', [TransferController::class, 'herdProjects']);
Route::post('/browse', [TransferController::class, 'browse']);
Route::post('/preview', [TransferController::class, 'preview']);
Route::post('/execute', [TransferController::class, 'execute']);
