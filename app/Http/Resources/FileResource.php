<?php

namespace App\Http\Resources;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class FileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $file = File::find($this->id);
        $fileContents = Storage::get($file->path);
        $extension = pathinfo($file->path, PATHINFO_EXTENSION);
        $base64File = base64_encode($fileContents);
        $file->save();
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'download_count' => $this->download_count,
            'file_content' => [
                'extension' => $extension,
                'content' => $base64File,
            ],
        ];
    }
}
