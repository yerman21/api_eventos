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

Route::post("user/register", "_Api\UserController@register");

Route::group(['middleware' => 'auth:api'], function(){
    Route::post("user/cambiar", "_Api\UserController@updateUser");
    Route::get("user/logout", "_Api\UserController@logout");
    
    Route::post("evento", "_Api\EventoController@store");
    Route::put("evento/{id}", "_Api\EventoController@update");
    Route::get("evento/{opcion}", "_Api\EventoController@listarEventos");
    Route::delete("evento/{id}", "_Api\EventoController@destroy");

    Route::post("asistencia_evento", "_Api\AsistenciaEventoController@apuntarseEvento");
    // Route::get("asistencia_evento/mi_evento/{id}/asistentes", "_Api\AsistenciaEventoController@asistents_my_event");
    // Route::get("asistencia_evento/{id}/asistentes", "_Api\AsistenciaEventoController@asistents_to_event");
    Route::get("asistencia_evento/{id}/asistentes", "_Api\AsistenciaEventoController@whoAsistentEvent");    
    Route::delete("asistencia_evento/{id}", "_Api\AsistenciaEventoController@desapuntarceOfEvento");
    Route::put("asistencia_evento/pasar_asistencia", "_Api\AsistenciaEventoController@pasarLista");
    Route::put("asistencia_evento/confirmar_asistencia/{idd}", "_Api\AsistenciaEventoController@confirmarAsistencia");
});


Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
