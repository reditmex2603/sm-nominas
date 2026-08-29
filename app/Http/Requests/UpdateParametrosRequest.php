<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateParametrosRequest extends FormRequest
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
            'pago_default_chico' => 'required|numeric|min:0',
            'pago_default_mediano' => 'required|numeric|min:0',
            'pago_default_grande' => 'required|numeric|min:0',
            'dias_bono_septimo' => 'required|integer|min:1|max:6',
            'bono_evento_encargado_nivel1_mediano' => 'required|numeric|min:0',
            'bono_evento_encargado_nivel1_grande' => 'required|numeric|min:0',
            'bono_evento_encargado_nivel2_mediano' => 'required|numeric|min:0',
            'bono_evento_encargado_nivel2_grande' => 'required|numeric|min:0',
            'bono_evento_tecnico_nivel1_mediano' => 'required|numeric|min:0',
            'bono_evento_tecnico_nivel1_grande' => 'required|numeric|min:0',
            'bono_evento_tecnico_nivel2_mediano' => 'required|numeric|min:0',
            'bono_evento_tecnico_nivel2_grande' => 'required|numeric|min:0',
            'bono_evento_stagehand_nivel1_mediano' => 'required|numeric|min:0',
            'bono_evento_stagehand_nivel1_grande' => 'required|numeric|min:0',
            'bono_evento_stagehand_nivel2_mediano' => 'required|numeric|min:0',
            'bono_evento_stagehand_nivel2_grande' => 'required|numeric|min:0',
        ];
    }
}
