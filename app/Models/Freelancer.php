<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Freelancer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'email', 'phone', 'cpf_cnpj', 'specialization', 'status', 'hourly_rate', 'address', 'notes'];

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
        ];
    }

    public static function statuses(): array
    {
        return [
            'active' => 'Ativo',
            'inactive' => 'Inativo',
        ];
    }

    public static function specializations(): array
    {
        return [
            'electrical' => 'Elétrica',
            'mechanical' => 'Mecânica',
            'civil' => 'Civil',
            'telecommunications' => 'Telecomunicações',
            'automation' => 'Automação',
            'it' => 'TI',
            'other' => 'Outra',
        ];
    }
}
