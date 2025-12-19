<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;


class SecurityLogResource extends JsonResource
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
            'user_name' => $this->user->name ?? 'Unknown',
            'action' => $this->action,
            'email' => $this->email,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
             'creator' => new UserResource($this->creator),
           'created_at' => $this->created_at
            ? Carbon::parse($this->created_at)->toIso8601String()
            : null,

        ];
    }
}
