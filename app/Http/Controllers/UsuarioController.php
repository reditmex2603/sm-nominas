<?php

namespace App\Http\Controllers;

use App\Enums\RolUsuario;
use App\Http\Requests\StoreUsuarioRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use App\Models\User;
use App\Support\Modulos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('parametros/Usuarios', [
            'usuarios' => User::orderBy('name')->get(['id', 'name', 'email', 'rol', 'permisos']),
            'modulos' => Modulos::MODULOS,
        ]);
    }

    public function store(StoreUsuarioRequest $request): RedirectResponse
    {
        $validated = $request->validated();

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

    public function update(UpdateUsuarioRequest $request, User $usuario): RedirectResponse
    {
        $this->authorizeNoAdmin($usuario);

        $validated = $request->validated();

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
