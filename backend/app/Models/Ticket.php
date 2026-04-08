<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasFactory;
    protected $fillable = ['booking_id', 'row', 'col', 'zone_id', 'qr_code', 'status'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }



    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
}