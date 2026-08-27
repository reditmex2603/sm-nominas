<?php

namespace App\Models;

use App\Casts\EncryptedOrDefault;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ColaboradorPerfil extends Model
{
    use HasFactory;

    protected $table = 'colaborador_perfiles';

    protected $fillable = [
        'colaborador_id',
        'alias',
        'fotografia_path',
        'fecha_ingreso',
        'correo',
        'telefono',
        'whatsapp',
        'redes_sociales',
        'domicilio',
        'genero',
        'ubicacion_maps',
        'fecha_nacimiento',
        'tipo_sangre',
        'alergias',
        'padecimientos_cronicos',
        'numero_seguro_social',
        'contacto_emergencia_1_nombre',
        'contacto_emergencia_1_parentesco',
        'contacto_emergencia_1_telefono',
        'contacto_emergencia_2_nombre',
        'contacto_emergencia_2_parentesco',
        'contacto_emergencia_2_telefono',
        'seguro_social_documento_path',
        'ine_documento_path',
        'curp_documento_path',
        'comprobante_domicilio_documento_path',
        'licencia_conducir_documento_path',
        'banco',
        'beneficiario',
        'clave_interbancaria',
    ];

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class)->withTrashed();
    }

    protected function casts(): array
    {
        return [
            'clave_interbancaria' => EncryptedOrDefault::class,
            'numero_seguro_social' => EncryptedOrDefault::class,
        ];
    }
}
