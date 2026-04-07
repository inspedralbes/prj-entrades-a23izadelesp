<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Seat extends Model
{
    use HasFactory;
    protected $fillable = ['session_id', 'row', 'number', 'price', 'status'];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AppSession::class);
    }
}