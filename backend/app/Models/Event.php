<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = ['title', 'description', 'type', 'image'];

    protected $casts = [
        'type' => 'string',
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(AppSession::class);
    }
}
