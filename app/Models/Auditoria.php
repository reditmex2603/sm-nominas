<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $evento
 * @property string|null $modelo
 * @property int|null $modelo_id
 * @property array<string, mixed>|null $detalles
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Auditoria extends Model
{
    protected $table = 'auditorias';

    protected $fillable = ['user_id', 'evento', 'modelo', 'modelo_id', 'detalles'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'detalles' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Registra un evento de auditoría con el usuario autenticado actual.
     *
     * @param  array<string, mixed>  $detalles
     */
    public static function registrar(
        string $evento,
        ?string $modelo = null,
        ?int $modeloId = null,
        array $detalles = [],
    ): void {
        static::create([
            'user_id' => auth()->id(),
            'evento' => $evento,
            'modelo' => $modelo,
            'modelo_id' => $modeloId,
            'detalles' => $detalles !== [] ? $detalles : null,
        ]);
    }
}
