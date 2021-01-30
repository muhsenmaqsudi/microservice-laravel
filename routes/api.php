<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Services\NestJsService;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/greeting', function (Request $request, NestJsService $nestService) {
    $name = $request->get('name');
    $nestResponse = $nestService->send(['cmd' => 'greeting'], $name);
    return $nestResponse->first();
});

Route::get('/observable', function (NestJsService $nestService) {
    $nestResponse = $nestService->send(['cmd' => 'observable']);
    return $nestResponse->sum();
});
