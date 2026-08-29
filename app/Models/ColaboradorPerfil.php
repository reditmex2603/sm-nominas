<?php

namespace App\Models;

use App\Casts\EncryptedOrDefault;
use Database\Factories\ColaboradorPerfilFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $colaborador_id
 * @property string|null $alias
 * @property string|null $fotografia_path
 * @property string|null $fecha_ingreso
 * @property string|null $correo
 * @property string|null $telefono
 * @property string|null $whatsapp
 * @property string|null $redes_sociales
 * @property string|null $domicilio
 * @property string|null $genero
 * @property string|null $ubicacion_maps
 * @property string|null $fecha_nacimiento
 * @property string|null $tipo_sangre
 * @property string|null $alergias
 * @property string|null $padecimientos_cronicos
 * @property string|null $numero_seguro_social
 * @property string|null $contacto_emergencia_1_nombre
 * @property string|null $contacto_emergencia_1_parentesco
 * @property string|null $contacto_emergencia_1_telefono
 * @property string|null $contacto_emergencia_2_nombre
 * @property string|null $contacto_emergencia_2_parentesco
 * @property string|null $contacto_emergencia_2_telefono
 * @property string|null $seguro_social_documento_path
 * @property string|null $ine_documento_path
 * @property string|null $curp_documento_path
 * @property string|null $comprobante_domicilio_documento_path
 * @property string|null $licencia_conducir_documento_path
 * @property string|null $banco
 * @property string|null $beneficiario
 * @property string|null $clave_interbancaria
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ColaboradorPerfil extends Model
{
    /** @use HasFactory<ColaboradorPerfilFactory> */
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

    /** @return BelongsTo<Colaborador, $this> */
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
