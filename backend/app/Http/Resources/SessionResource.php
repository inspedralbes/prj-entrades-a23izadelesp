<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'date' => $this->date->format('Y-m-d'),
            'time' => $this->time,
            'price' => $this->price,
            'venue_config' => $this->venue_config,
            'event' => new EventResource($this->whenLoaded('event')),
        ];
    }
}
