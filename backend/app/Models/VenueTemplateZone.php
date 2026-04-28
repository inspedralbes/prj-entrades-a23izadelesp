<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VenueTemplateZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'venue_template_id',
        'key',
        'name',
        'zone_type',
        'capacity',
        'price',
        'color',
        'sort_order',
        'seat_layout',
        'shape',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'seat_layout' => 'array',
        'shape' => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(VenueTemplate::class, 'venue_template_id');
    }
}
