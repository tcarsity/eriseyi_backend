<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{

        public function toArray(Request $request): array
        {
            return [
                'id' => $this->id,
                'name' => $this->name,
                'phone' => $this->phone,
                'address' => $this->address,
                'gender' => $this->gender,
                'birth_month' => $this->birth_month,
                'birth_day' => $this->birth_day,
                'created_at' => $this->created_at,
                'created_by' => $this->created_by
            ];
        }

        public function with($request)
        {
            return [
                'status' => 'success',
            ];
        }
    }

