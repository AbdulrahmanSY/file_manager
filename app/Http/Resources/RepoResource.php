<?php

namespace App\Http\Resources;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RepoResource extends JsonResource
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
            'user_id' => $this->pivot->user_id,
            'name' => $this->name,
            'user_count' => $this->users_count,
            'is_admin' => $this->pivot->is_admin,
            'files'=>FileResource::collection($this->whenLoaded('files'))
        ];
    }
}
