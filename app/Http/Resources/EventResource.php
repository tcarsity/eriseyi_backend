<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'event_date' => $this->event_date,
            'event_time' => $this->event_time,
            'image' => $this->image
             ? url($this->image)
             : null,
            'creator' => new UserResource($this->creator),
            'created_at' => $this->created_at->format('y-m-d H:i'),
            'updated_at' => $this->updated_at->format('y-m-d H:i'),
        ];
    }
}
