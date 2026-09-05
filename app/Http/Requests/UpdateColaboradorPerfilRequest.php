<?php

namespace App\Http\Requests;

use App\Rules\Telefono;
use Illuminate\Foundation\Http\FormRequest;

class UpdateColaboradorPerfilRequest extends FormRequest
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
            // Datos personales (fecha de ingreso, teléfono y WhatsApp son obligatorios)
            'alias' => 'nullable|string|max:255',
            'fotografia' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'fecha_ingreso' => 'required|date',
            'correo' => 'nullable|email|max:255',
            'telefono' => ['required', new Telefono],
            'whatsapp' => ['required', new Telefono],
            'redes_sociales' => 'nullable|string|max:500',
            'domicilio' => 'nullable|string|max:2000',
            'genero' => 'nullable|in:Masculino,Femenino',
            'ubicacion_maps' => 'nullable|string|max:1000',
            'fecha_nacimiento' => 'nullable|date',
            // Datos de emergencia
            'tipo_sangre' => 'nullable|string|max:10',
            'alergias' => 'nullable|string|max:2000',
            'padecimientos_cronicos' => 'nullable|string|max:2000',
            'numero_seguro_social' => 'nullable|string|max:50',
            // Contactos de emergencia
            'contacto_emergencia_1_nombre' => 'nullable|string|max:255',
            'contacto_emergencia_1_parentesco' => 'nullable|string|max:255',
            'contacto_emergencia_1_telefono' => ['nullable', new Telefono],
            'contacto_emergencia_2_nombre' => 'nullable|string|max:255',
            'contacto_emergencia_2_parentesco' => 'nullable|string|max:255',
            'contacto_emergencia_2_telefono' => ['nullable', new Telefono],
            // Datos bancarios (1 o más registros). Los campos sueltos (banco/beneficiario/
            // clave_interbancaria) se conservan solo por retrocompatibilidad con impresiones.
            'banco' => 'nullable|string|max:255',
            'beneficiario' => 'nullable|string|max:255',
            'clave_interbancaria' => 'nullable|string|max:50',
            'datos_bancarios' => 'nullable|array',
            'datos_bancarios.*.id' => 'nullable|integer',
            'datos_bancarios.*.banco' => 'nullable|string|max:255',
            'datos_bancarios.*.beneficiario' => 'nullable|string|max:255',
            'datos_bancarios.*.clave_interbancaria' => 'nullable|string|max:50',
            'datos_bancarios.*.numero_tarjeta' => 'nullable|string|max:50',
            'datos_bancarios.*.alias' => 'nullable|string|max:255',
            'datos_bancarios.*.comentario' => 'nullable|string|max:2000',
            // Documentos de identificación
            'seguro_social_documento' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'ine_documento' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'curp_documento' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'comprobante_domicilio_documento' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'licencia_conducir_documento' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }
}
