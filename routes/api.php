<?php

use Illuminate\Http\Request;

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

Route::options('{any?}', function ($any) { return response('', 200); })->where('any', '.*'); // Ruta OPTIONS atrapalotodo
Route::post('/login', 'APIController@getLogin');

Route::group(['middleware' => ['auth:api']], function() {

    Route::post('/user', 'APIController@getUser');
    Route::post('/user/edita', 'APIController@guardaUser');
    Route::post('/user/cambiaPass', 'APIController@cambiaPass');

    Route::post('inputs', 'APIController@getInputs');
    Route::post('comunes', 'APIController@getComunes');
});
