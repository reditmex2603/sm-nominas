<?php

namespace App\Http\Requests;

use App\Enums\TipoColaborador;
use App\Models\Colaborador;
use Illuminate\Foundation\Http\FormRequest;

class UpdateColaboradorNominaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Las reglas dependen del tipo del colaborador (Base, Conductor Base o Freelance),
     * por eso se construyen dinámicamente según el modelo de la ruta. Si el tipo no
     * tiene campos editables, las reglas quedan vacías y update([]) no hace nada.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Colaborador $colaborador */
        $colaborador = $this->route('colaborador');

        $rules = [];

        if ($colaborador->tipo === TipoColaborador::Base) {
            $rules['sueldo_diario'] = 'nullable|numeric|min:0';
            $rules['categoria'] = 'required|in:Encargado de área,Técnico,Stagehand SM';
            $rules['nivel'] = 'required|integer|in:1,2';
            $rules['compensacion_pct'] = 'nullable|integer|min:0|max:100';
        }

        if ($colaborador->tipo === TipoColaborador::ConductorBase) {
            $rules['sueldo_diario'] = 'required|numeric|min:0';
            $rules['compensacion_pct'] = 'nullable|integer|min:0|max:100';
        }

        if ($colaborador->tipo === TipoColaborador::Freelance) {
            $rules['extra_dia_adicional'] = 'nullable|numeric|min:0';
        }

        return $rules;
    }
}
