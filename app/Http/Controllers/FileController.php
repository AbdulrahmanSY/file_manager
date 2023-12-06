<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FileController extends Controller
{
    public function create(FileStoreRequest $request)
    {
        $user = $request->user();
        $uploadedFile = $request->file('file');
        $fileName = $uploadedFile->getClientOriginalName();
        $extension = $uploadedFile->getClientOriginalExtension();
        $fileNameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);
        $newFileName = $fileNameWithoutExtension . '_' . time() . '.' . $extension;
        $path = $uploadedFile->storeAs('uploads', $newFileName);

        $repo = $user->repo()->when($request['repo_id'], function ($query, $repoId) {
            return $query->where('repos.id', $repoId);
        })->first();

        $repo->files()->create([
            'name' => $fileNameWithoutExtension,
            'url' => $path,
            'status' => 'free',
        ]);

        return $this->success();
    }
}
