<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'status',
        'description',
        'operator',
        'address',
        'latitude',
        'longitude',
        'responsible',
        'phone',
        'start_date',
        'end_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public static function statuses(): array
    {
        return [
            'active' => 'Ativo',
            'inactive' => 'Inativo',
            'completed' => 'Concluído',
            'suspended' => 'Suspenso',
        ];
    }

    public static function types(): array
    {
        return [
            'zte_claro_wl' => 'ZTE Claro WL',
            'zte_claro' => 'ZTE Claro',
            'zte_vivo' => 'ZTE Vivo',
            'huawei_claro' => 'Huawei Claro',
            'huawei_vivo' => 'Huawei Vivo',
            'nokia_tim' => 'Nokia TIM',
            'other' => 'Outro',
        ];
    }

    public static function operators(): array
    {
        return [
            'CLARO' => 'Claro',
            'VIVO' => 'Vivo',
            'TIM' => 'TIM',
            'OTHER' => 'Outra',
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
