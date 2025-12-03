<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErrorResource extends JsonResource
{

    public function __construct($message, $errors = null){
        parent::__construct([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ]);
    }

    public function toArray($request)
    {
        return [
            'status' => $this->resource['status'],
            'message' => $this->resource['message'],
            'errors' => $this->resource['errors']
        ];
    }
}
