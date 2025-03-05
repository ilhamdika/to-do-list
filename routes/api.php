<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TodoController;
use Symfony\Component\HttpFoundation\Response;

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

Route::post('/todos', [TodoController::class, 'createTodo']);
Route::get('/export', [TodoController::class, 'export']);
Route::get('/chart', [TodoController::class, 'getChart']);


Route::any('{any}', function () {
    return response()->json([
        'message' => 'Method salah'
    ], Response::HTTP_METHOD_NOT_ALLOWED);
})->where('any', '.*');