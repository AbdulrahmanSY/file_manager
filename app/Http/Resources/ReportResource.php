<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'repo_id'=>$this->repo_id,
            'file_id'=>$this->file_id,
            'user_name'=>$this->user->name,
            'file_name'=>$this->file->name,
            'operation'=>$this->operation,
            'time'=>$this->created_at->format('H:i:s'),
            'date'=>$this->created_at->format('Y-m-d'),

        ];
    }
}
