<?php

namespace App\Http\Requests;

use App\Models\Colaborador;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAsistenciaPublicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $colaborador = Colaborador::where('token', $this->route('token'))->first();

        $tiposPermitidos = $colaborador?->tipo->actividadesPermitidas() ?? [];

        return [
            'tipo_actividad' => ['required', Rule::in($tiposPermitidos)],
            'actividad' => ['nullable', 'required_if:tipo_actividad,Bodega', 'string', 'max:500'],
            'evento_raw' => ['nullable', 'required_if:tipo_actividad,Evento', 'string', 'max:255'],
            'etapa' => 'nullable|string|max:100',
            'vehiculo' => ['nullable', 'required_if:tipo_actividad,Transporte', 'string', 'max:255'],
            'distancia' => ['nullable', 'required_if:tipo_actividad,Transporte', 'string', 'max:255'],
            'transporte_unidad_id' => ['nullable', 'required_if:tipo_actividad,Transporte', 'exists:transporte_unidades,id'],
            'origen' => ['nullable', 'required_if:tipo_actividad,Transporte', 'string', 'max:255'],
            'destino' => ['nullable', 'required_if:tipo_actividad,Transporte', 'string', 'max:255'],
            'extras' => 'nullable|string|max:2000',
            'evidencia' => 'required|file|image|max:5120',
            'comentarios' => 'nullable|string|max:1000',
            'fecha' => 'required|date',
            'hora' => 'required|date_format:H:i',
            'hora_salida' => 'nullable|date_format:H:i',
        ];
    }
}
