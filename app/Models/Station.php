<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Station extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['site_id', 'end_id', 'operadora', 'description', 'address', 'latitude', 'longitude', 'status', 'type', 'responsible', 'phone'];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    public static function statuses(): array
    {
        return [
            'active' => 'Ativa',
            'inactive' => 'Inativa',
            'maintenance' => 'Manutenção',
        ];
    }

    public static function types(): array
    {
        return [
            'substation' => 'Subestação',
            'distribution' => 'Distribuição',
            'transmission' => 'Transmissão',
            'generation' => 'Geração',
        ];
    }

    public static function operadoras(): array
    {
        return [
            'TIM' => 'TIM',
            'VIVO' => 'VIVO',
            'CLARO' => 'CLARO',
            'Outra' => 'Outra',
        ];
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
