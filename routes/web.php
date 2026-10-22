<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\GameRoomController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JoinController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\RemoteStyleController;
use App\Http\Controllers\ResultsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/join', [JoinController::class, 'create'])->name('join');
Route::post('/join', [JoinController::class, 'store'])->middleware('throttle:10,1')->name('join.store');
Route::get('/join/{player}', [JoinController::class, 'show'])->name('join.lobby');
Route::get('/join/{player}/state', [JoinController::class, 'state'])->middleware('throttle:60,1')->name('join.state');
Route::post('/join/{player}/advance', [JoinController::class, 'advance'])->middleware('throttle:60,1')->name('join.advance');
Route::post('/join/{player}/answer', [JoinController::class, 'answer'])->middleware('throttle:60,1')->name('join.answer');
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'create'])->name('admin');
    Route::get('/concursos', [AdminController::class, 'concursos'])->name('admin.concursos');
    Route::get('/concursos/create', [AdminController::class, 'createConcursos'])->name('admin.concursos.create');
    Route::post('/concursos', [AdminController::class, 'storeConcursos'])->name('admin.concursos.store');
    Route::get('/concursos/{quiz}/edit', [AdminController::class, 'editConcurso'])->name('admin.concursos.edit');
    Route::put('/concursos/{quiz}', [AdminController::class, 'updateConcurso'])->name('admin.concursos.update');
    Route::delete('/concursos/{quiz}', [AdminController::class, 'destroyConcurso'])->name('admin.concursos.destroy');
    Route::post('/concursos/{quiz}/status', [AdminController::class, 'toggleConcursoStatus'])->name('admin.concursos.status');
    Route::get('/preguntas', [AdminController::class, 'preguntas'])->name('admin.preguntas');
    Route::get('/concursos/{quiz}/preguntas', [QuestionController::class, 'index'])->name('admin.concursos.preguntas.index')->scopeBindings();
    Route::get('/concursos/{quiz}/preguntas/create', [QuestionController::class, 'create'])->name('admin.concursos.preguntas.create')->scopeBindings();
    Route::post('/concursos/{quiz}/preguntas', [QuestionController::class, 'store'])->name('admin.concursos.preguntas.store')->scopeBindings();
    Route::get('/concursos/{quiz}/preguntas/{question}/edit', [QuestionController::class, 'edit'])->name('admin.concursos.preguntas.edit')->scopeBindings();
    Route::put('/concursos/{quiz}/preguntas/{question}', [QuestionController::class, 'update'])->name('admin.concursos.preguntas.update')->scopeBindings();
    Route::delete('/concursos/{quiz}/preguntas/{question}', [QuestionController::class, 'destroy'])->name('admin.concursos.preguntas.destroy')->scopeBindings();
    Route::post('/concursos/{quiz}/preguntas/{question}/move', [QuestionController::class, 'move'])->name('admin.concursos.preguntas.move')->scopeBindings();
    Route::get('/salas', [GameRoomController::class, 'index'])->name('admin.salas');
    Route::get('/salas/create', [GameRoomController::class, 'create'])->name('admin.salas.create');
    Route::post('/salas', [GameRoomController::class, 'store'])->name('admin.salas.store');
    Route::get('/salas/{gameRoom}', [GameRoomController::class, 'show'])->name('admin.salas.show');
    Route::delete('/salas/{gameRoom}', [GameRoomController::class, 'destroy'])->name('admin.salas.destroy');
    Route::post('/salas/{gameRoom}/start', [GameRoomController::class, 'start'])->name('admin.salas.start');
    Route::post('/salas/{gameRoom}/advance', [GameRoomController::class, 'advance'])->name('admin.salas.advance');
    Route::get('/partidas', [GameRoomController::class, 'matches'])->name('admin.partidas');
    Route::get('/resultados', [ResultsController::class, 'index'])->name('admin.resultados');
    Route::get('/resultados/{gameRoom}', [ResultsController::class, 'show'])->name('admin.resultados.show');
});

Route::get('/emisor', [RemoteStyleController::class, 'emisor'])->name('emisor');
Route::post('/emitir', [RemoteStyleController::class, 'emitir'])->name('emitir');

Route::get('/receptor', [RemoteStyleController::class, 'receptor'])->name('receptor');
