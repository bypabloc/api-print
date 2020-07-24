<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
Route::post('imprimir/documento_fiscal', 'ImprimirController@documento_fiscal');
Route::post('imprimir/cuentaRapida', 'ImprimirController@cuentaRapida');
Route::post('imprimir/data_impresion', 'ImprimirController@data_impresion');
Route::post('imprimir/comanda', 'ImprimirController@comanda');

Route::post('imprimir/delivery/comanda', 'ImprimirController@comandaDelivery');
Route::post('imprimir/delivery/motorizado', 'ImprimirController@motorizadoDelivery');
Route::post('imprimir/delivery/cuentaRapida', 'ImprimirController@cuentaRapidaDelivery');
Route::post('imprimir/delivery/documento_fiscal', 'ImprimirController@documento_fiscalDelivery');

Route::post('imprimir/venta_regular/documento_fiscal', 'ImprimirController@documento_fiscalRegular');
Route::post('imprimir/venta_regular/cuentaRapida', 'ImprimirController@cuentaRapidaRegular');
Route::post('imprimir/venta_regular/data_impresion', 'ImprimirController@data_impresionRegular');
Route::post('imprimir/venta_regular/comanda', 'ImprimirController@comandaRegular');

Route::post('imprimir/vale/ticket', 'ImprimirController@ticketVale');

Route::post('imprimir', 'ImprimirController@imprimir');
Route::post('imprimir2', 'ImprimirController@imprimir2');
Route::post('imprimir3', 'ImprimirController@imprimir3');

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
