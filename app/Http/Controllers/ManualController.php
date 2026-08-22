<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

class ManualController extends Controller
{
    public function index(): Response
    {
        $rutaManual = base_path('MANUAL_USUARIO.md');

        return Inertia::render('manual/Index', [
            'contenido' => File::exists($rutaManual) ? File::get($rutaManual) : '',
        ]);
    }
}
