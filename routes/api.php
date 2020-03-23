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
Route::post("login", "_Api\AuthController@login");
Route::post("refreshToken", "_Api\AuthController@refreshToken");
Route::post("user/register", "_Api\UserController@register");
Route::get("user/logout", "_Api\UserController@logout");

Route::group(['middleware' => 'auth:api'], function(){
    Route::post("user/cambiar", "_Api\UserController@updateUser");
    // Route::get("user/logout", "_Api\UserController@logout");
    Route::get("user/data/{id?}", "_Api\UserController@getUserData");    
    
    Route::post("evento", "_Api\EventoController@store");
    Route::post("evento/edit/{id}", "_Api\EventoController@update");
    Route::get("evento/edit/{id}", "_Api\EventoController@show");
    Route::get("evento/{opcion}", "_Api\EventoController@listarEventos");
    Route::get("evento/{opcion}/perfil/{idUsuario}", "_Api\EventoController@listarEventosByUsaurio");
    Route::delete("evento/{id}", "_Api\EventoController@destroy");

    Route::post("asistencia_evento", "_Api\AsistenciaEventoController@apuntarseEvento");
    // Route::get("asistencia_evento/mi_evento/{id}/asistentes", "_Api\AsistenciaEventoController@asistents_my_event");
    // Route::get("asistencia_evento/{id}/asistentes", "_Api\AsistenciaEventoController@asistents_to_event");
    Route::get("asistencia_evento/{id}/asistentes", "_Api\AsistenciaEventoController@whoAsistentEvent");    
    Route::delete("asistencia_evento/{id}", "_Api\AsistenciaEventoController@desapuntarceOfEvento");
    Route::put("asistencia_evento/pasar_asistencia", "_Api\AsistenciaEventoController@pasarLista");
    Route::put("asistencia_evento/confirmar_asistencia/{idd}", "_Api\AsistenciaEventoController@confirmarAsistencia");
    Route::put("asistencia_evento/quitar/check-control", "_Api\AsistenciaEventoController@quitarCheckControl");
});


// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });
