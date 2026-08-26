<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        'lugar',
        'direccion',
        'latitud',
        'longitud',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'responsable_id',
        'created_by',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'latitud'      => 'decimal:7',
        'longitud'     => 'decimal:7',
    ];

    const ESTADOS = ['planificado', 'en_curso', 'finalizado', 'cancelado'];

    public function responsable(): BelongsTo { return $this->belongsTo(User::class, 'responsable_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function leads(): HasMany { return $this->hasMany(EventLead::class); }

    public function tieneUbicacion(): bool
    {
        return $this->latitud !== null && $this->longitud !== null;
    }

    public function estadoBadgeClass(): string
    {
        return match ($this->estado) {
            'planificado' => 'bg-sky-500/10 text-sky-400 ring-1 ring-sky-500/20',
            'en_curso'    => 'bg-emerald-500/10 text-emerald-400 ring-1 ring-emerald-500/20',
            'finalizado'  => 'bg-slate-700 text-slate-400',
            'cancelado'   => 'bg-red-500/10 text-red-400 ring-1 ring-red-500/20',
            default       => 'bg-slate-700 text-slate-400',
        };
    }

    public function estadoLabel(): string
    {
        return match ($this->estado) {
            'planificado' => 'Planificado',
            'en_curso'    => 'En curso',
            'finalizado'  => 'Finalizado',
            'cancelado'   => 'Cancelado',
            default       => ucfirst($this->estado),
        };
    }
}
