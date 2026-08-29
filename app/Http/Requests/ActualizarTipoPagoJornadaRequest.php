<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarTipoPagoJornadaRequest extends FormRequest
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
            'tipo_pago' => 'required|in:JORNADA_COMPLETA,JORNADA_COMPLETA + EVENTO,TRASLAPE,SIN_PAGO,ERROR_EVENTO',
            'traslape_pct' => 'required_if:tipo_pago,TRASLAPE|integer|min:1|max:99',
        ];
    }
}
