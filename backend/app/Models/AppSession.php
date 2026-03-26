<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppSession extends Model
{
    use HasFactory;
    protected $table = 'app_sessions';

    protected $fillable = ['event_id', 'date', 'time', 'price', 'venue_config'];

    protected $casts = [
        'date' => 'date',
        'price' => 'decimal:2',
        'venue_config' => 'array',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function occupiedSeats(): HasMany
    {
        return $this->hasMany(OccupiedSeat::class);
    }

    public function occupiedZones(): HasMany
    {
        return $this->hasMany(OccupiedZone::class);
    }
}
