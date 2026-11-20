<?php

namespace App\Http\Controllers;

use App\Models\Sitio;
use Illuminate\View\View;

class AforoVistaController extends Controller
{
    public function index(): View
    {
        $sitios = Sitio::query()->orderBy('nombre')->get();

        return view('aforo.index', ['sitios' => $sitios]);
    }
}