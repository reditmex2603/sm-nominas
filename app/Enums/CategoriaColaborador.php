<?php

namespace App\Enums;

/** Categorías del personal Base (colaboradores.categoria). */
enum CategoriaColaborador: string
{
    case EncargadoDeArea = 'Encargado de área';
    case Tecnico = 'Técnico';
    case Stagehand = 'Stagehand SM';
}
