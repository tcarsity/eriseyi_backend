<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->when(auth()->check() && auth()->user()->role === 'superadmin', $this->email),
            'role' => $this->role,
            'status' => $this->status,
            'last_seen' => $this->last_seen,
            'is_active' => $this->last_seen
                ? Carbon::parse($this->last_seen)->gt(now()->subSeconds(65))
                : false,
        ];
    }

    public function with($request)
    {
        return [
            'status' => 'success'
        ];
    }
}
