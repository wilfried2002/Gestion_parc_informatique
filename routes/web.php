<?php

use Illuminate\Support\Facades\Route;

// Redirige la racine vers la page de connexion
Route::get('/', fn () => redirect('/login'));

// Page de connexion
Route::get('/login', fn () => view('login'))->name('login');

// Application principale (SPA – l'auth est gérée côté JS via JWT + localStorage)
Route::get('/app', fn () => view('app'))->name('app');
