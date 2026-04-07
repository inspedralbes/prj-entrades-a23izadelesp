<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Zone extends Model
{
    use HasFactory;
    protected $fillable = ['session_id', 'name', 'price', 'capacity', 'available', 'color'];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AppSession::class);
    }
}