<?php

namespace App\Models;

use App\Casts\EncryptedOrDefault;
use Database\Factories\ColaboradorDatoBancarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $colaborador_id
 * @property string|null $banco
 * @property string|null $beneficiario
 * @property string|null $clave_interbancaria
 * @property string|null $numero_tarjeta
 * @property string|null $alias
 * @property string|null $comentario
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ColaboradorDatoBancario extends Model
{
    /** @use HasFactory<ColaboradorDatoBancarioFactory> */
    use HasFactory;

    protected $table = 'colaborador_datos_bancarios';

    protected $fillable = [
        'colaborador_id',
        'banco',
        'beneficiario',
        'clave_interbancaria',
        'numero_tarjeta',
        'alias',
        'comentario',
    ];

    protected function casts(): array
    {
        return [
            'clave_interbancaria' => EncryptedOrDefault::class,
            'numero_tarjeta' => EncryptedOrDefault::class,
        ];
    }

    /** @return BelongsTo<Colaborador, $this> */
    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class)->withTrashed();
    }
}
