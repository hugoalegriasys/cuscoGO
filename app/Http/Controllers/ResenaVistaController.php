<?php

namespace App\Http\Controllers;

use App\Models\Sitio;
use Illuminate\View\View;

class ResenaVistaController extends Controller
{
    public function index(): View
    {
        $sitios = Sitio::query()->orderBy('nombre')->get();

        return view('resenas.index', ['sitios' => $sitios]);
    }
}
