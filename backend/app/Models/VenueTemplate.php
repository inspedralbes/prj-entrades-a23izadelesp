<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VenueTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'template_type', 'metadata'];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function zones(): HasMany
    {
        return $this->hasMany(VenueTemplateZone::class)->orderBy('sort_order');
    }
}
