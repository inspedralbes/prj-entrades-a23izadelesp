<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'guest_email', 'identifier', 'session_id', 'status', 'total'];

    protected $casts = [
        'total' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AppSession::class);
    }

    public function occupiedSeats(): HasMany
    {
        return $this->hasMany(OccupiedSeat::class);
    }

    public function occupiedZones(): HasMany
    {
        return $this->hasMany(OccupiedZone::class);
    }

    public function occupiedZoneSeats(): HasMany
    {
        return $this->hasMany(OccupiedZoneSeat::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function scopeGuest($query)
    {
        return $query->whereNull('user_id')->whereNotNull('guest_email');
    }

    public function scopeUser($query)
    {
        return $query->whereNotNull('user_id');
    }

    public function isGuest(): bool
    {
        return is_null($this->user_id) && !is_null($this->guest_email);
    }
}
