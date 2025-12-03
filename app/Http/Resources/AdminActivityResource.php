<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminActivityResource extends JsonResource
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
            'action' => $this->action,
            'details' => $this->details,
            'user_name' => $this->user?->name,
            'user_role' => $this->user?->role,
            'created_at' => $this->created_at->toIso8601String(),

        ];
    }
}
