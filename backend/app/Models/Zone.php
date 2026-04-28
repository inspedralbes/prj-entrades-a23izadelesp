<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zone extends Model
{
    use HasFactory;
    protected $fillable = [
        'session_id',
        'key',
        'name',
        'zone_type',
        'price',
        'capacity',
        'available',
        'color',
        'sort_order',
        'seat_layout',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'seat_layout' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AppSession::class);
    }

    public function occupiedZoneSeats(): HasMany
    {
        return $this->hasMany(OccupiedZoneSeat::class);
    }
}