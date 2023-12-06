<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\FileRequest\FileStoreRequest;
use App\Http\Requests\FileRequest\GetFileRepoRequest;
use App\Http\Resources\RepoResource;
use Illuminate\Http\Request;

class FileController extends Controller
{
    public function create(FileStoreRequest $request)
    {
        $user = $request->user();
        $exists = $user->repo()->when($request['repo_id'], function ($query, $repoId) {
            return $query->where('repos.id', $repoId);
        });
        if(!$exists->exists()){
            return $this->error(message:'not found repo or you do not added to it');

        }
        $repo = $exists->first();
        if ($repo->pivot->is_admin) {
            $uploadedFile = $request->file('file');
            $fileName = $uploadedFile->getClientOriginalName();
            $extension = $uploadedFile->getClientOriginalExtension();
            $fileNameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);
            $newFileName = $fileNameWithoutExtension . '_' . time() . '.' . $extension;
            $path = $uploadedFile->storeAs('uploads', $newFileName);
            $repo->files()->create([
                'name' => $fileName,
                'path' => $path,
                'status' => 'free',
            ]);
            return $this->success(message:'create file successfully');
        }
        return $this->error('you do not have permeation');
    }
    public function get(GetFileRepoRequest $request)
    {
        $user = $request->user();
        $repo_id = $request['repo_id'];

        $exists = $user->repo()->when($repo_id, function ($query, $repoId) {
            return $query->where('repos.id', $repoId);
        });
        if(!$exists->exists()){
            return $this->error(message:'not found repo or you do not added to it');
        }
        $repo = $exists->with('files')->first();

        return $this->success(new RepoResource($repo ));
    }

}
