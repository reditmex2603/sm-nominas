<?php

namespace App\Http\Controllers;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController extends Controller
{
    /** Módulos del sistema y su etiqueta. Cada uno exige su permiso para acceder. */
    public const MODULOS = [
        'validacion' => 'Panel Validación',
        'colaboradores' => 'Colaboradores',
        'eventos' => 'Eventos',
        'transportes' => 'Transportes',
        'anticipos' => 'Anticipos',
        'prestamos' => 'Préstamos',
        'servicios-profesionales' => 'Servicios Profesionales',
        'viaticos' => 'Viáticos',
        'historial' => 'Historial',
        'registro-asistencia' => 'Registro de Asistencia',
        'nomina' => 'Nómina y Jornadas',
        'manual' => 'Manual de usuario',
    ];

    public function index(): Response
    {
        return Inertia::render('parametros/Usuarios', [
            'usuarios' => User::orderBy('name')->get(['id', 'name', 'email', 'rol', 'permisos']),
            'modulos' => self::MODULOS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'string', Password::defaults()],
            'rol' => 'required|in:supervisor,capturista',
            'permisos' => 'array',
            'permisos.*' => Rule::in(array_keys(self::MODULOS)),
        ]);

        $usuario = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        // rol/permisos no están en $fillable (defensa contra mass-assignment); se asignan
        // explícitamente aquí, siempre validados contra la whitelist de módulos y roles.
        $usuario->forceFill([
            'rol' => $validated['rol'],
            'permisos' => array_values(array_unique($validated['permisos'] ?? [])),
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario creado.']);

        return back();
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $this->authorizeNoAdmin($usuario);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'password' => ['nullable', 'string', Password::defaults()],
            'rol' => 'required|in:supervisor,capturista',
            'permisos' => 'array',
            'permisos.*' => Rule::in(array_keys(self::MODULOS)),
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (! empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $usuario->update($data);

        // rol/permisos no están en $fillable (defensa contra mass-assignment); se asignan
        // explícitamente aquí, siempre validados contra la whitelist de módulos y roles.
        $usuario->forceFill([
            'rol' => $validated['rol'],
            'permisos' => array_values(array_unique($validated['permisos'] ?? [])),
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario actualizado.']);

        return back();
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        $this->authorizeNoAdmin($usuario);

        if ($usuario->id === $request->user()->id) {
            abort(403, 'No puedes eliminar tu propio usuario.');
        }

        $usuario->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario eliminado.']);

        return back();
    }

    /** No se permite editar ni eliminar a otros admins (solo hay un super admin). */
    private function authorizeNoAdmin(User $usuario): void
    {
        if ($usuario->rol === RolUsuario::Admin) {
            abort(403, 'No puedes gestionar a otros administradores.');
        }
    }
}
