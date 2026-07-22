<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'os_number',
        'title',
        'description',
        'status',
        'priority',
        'responsible',
        'address',
        'scheduled_date',
        'completed_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'completed_date' => 'date',
        ];
    }

    public static function statuses(): array
    {
        return [
            'open' => 'Aberta',
            'in_progress' => 'Em Andamento',
            'completed' => 'Concluída',
            'cancelled' => 'Cancelada',
        ];
    }

    public static function priorities(): array
    {
        return [
            'low' => 'Baixa',
            'medium' => 'Média',
            'high' => 'Alta',
            'urgent' => 'Urgente',
        ];
    }

    public static function generateOsNumber(): string
    {
        $last = static::withoutGlobalScopes()
            ->orderByDesc('id')
            ->value('os_number');

        if ($last && preg_match('/OS(\d+)/', $last, $matches)) {
            $next = (int) $matches[1] + 1;
        } else {
            $next = 1;
        }

        return 'OS'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
