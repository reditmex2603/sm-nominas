<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegistroAsistenciaRequest extends FormRequest
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
        return [
            'colaborador_id' => 'required|exists:colaboradores,id',
            'tipo_actividad' => 'required|in:Bodega,Evento,Transporte',
            'actividad' => 'nullable|string|max:500',
            'evento_raw' => 'nullable|string|max:255',
            'etapa' => 'nullable|string|max:100',
            'vehiculo' => 'nullable|string|max:255',
            'distancia' => 'nullable|string|max:255',
            'transporte_unidad_id' => ['nullable', 'required_if:tipo_actividad,Transporte', 'exists:transporte_unidades,id'],
            'origen' => 'nullable|string|max:255',
            'destino' => 'nullable|string|max:255',
            'extras' => 'nullable|string|max:2000',
            'evidencia' => 'nullable|file|image|max:5120',
            'comentarios' => 'nullable|string|max:1000',
            'fecha' => 'required|date',
            'hora' => 'required|date_format:H:i',
            'hora_salida' => 'nullable|date_format:H:i',
        ];
    }
}
