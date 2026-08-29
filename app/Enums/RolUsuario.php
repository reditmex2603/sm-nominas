<?php

namespace App\Enums;

/** Rol de usuario del sistema (users.rol). */
enum RolUsuario: string
{
    case Admin = 'admin';
    case Supervisor = 'supervisor';
    case Capturista = 'capturista';
}
