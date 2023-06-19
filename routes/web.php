<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EtiquetaController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/listarEtiquetas', [EtiquetaController::class,'index']);
Route::get('/formulario', [EtiquetaController::class,'setForm']);
Route::get('/download', [EtiquetaController::class,'download']);
Route::get('/upload', [EtiquetaController::class,'upload']);
Route::get('/leitura', [EtiquetaController::class,'leitura']);

