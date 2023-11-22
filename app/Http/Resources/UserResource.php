<?php

namespace App\Http\Resources;

use App\Http\Resources\RepoResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'id'=>$this->id,
            'name'=>$this->name,
            'email'=>$this->email,
            'verify'=>$this->verify,
            'repo'=> RepoResource::collection($this->whenLoaded('repo')),

        ];
    }
}
