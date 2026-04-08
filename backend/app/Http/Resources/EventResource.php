<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $nextSession = $this->sessions->sortBy('date')->first();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'image' => $this->image,
            'date' => $nextSession?->date,
            'sessions' => SessionResource::collection($this->whenLoaded('sessions')),
        ];
    }
}
