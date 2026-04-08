<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController
{
    public function index(Request $request): JsonResponse
    {
        $query = Event::with('sessions');

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        $events = $query->get();

        return EventResource::collection($events)->response();
    }

    public function show(Event $event): JsonResponse
    {
        $event->load('sessions');

        return (new EventResource($event))->response();
    }
}
