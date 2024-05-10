<?php

use ManoCode\AIHelper\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::get('ai-helper', [Controllers\AiHelperController::class, 'index']);
Route::post('ai-helper/gen', [Controllers\AiHelperController::class, 'gen']);
Route::post('ai-helper/import', [Controllers\AiHelperController::class, 'import']);
