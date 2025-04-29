<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public $status;
    public $message;
    public $resource;
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */

    public function __construct($resource, $status = true, $message = "Operation successful")
    {
        parent::__construct($resource);
        $this->status  = $status;
        $this->message = $message;
    }


    public function toArray(Request $request): array
    {
        return [
            'success'   => $this->status,
            'message'   => $this->message,
            'data'      => $this->resource
        ];
    }
}
