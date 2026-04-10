<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OccupiedZoneSeat extends Model
{
    use HasFactory;

    protected $fillable = ['booking_id', 'session_id', 'zone_id', 'row', 'col'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AppSession::class, 'session_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
}
