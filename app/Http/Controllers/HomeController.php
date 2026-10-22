<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Muestra la pantalla principal de QUIZ ARENA.
     */
    public function index(): View
    {
        return view('home');
    }
}
