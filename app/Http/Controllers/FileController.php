<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\FileRequest\CheckInOutRequest;
use App\Http\Requests\FileRequest\ValidateFileRequest;
use App\Http\Requests\FileRequest\FileStoreRequest;
use App\Http\Requests\FileRequest\ValidateRepoRequest;
use App\Http\Resources\RepoResource;
use App\Models\File;
use App\Models\Repo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Enums\OperationEnum;
use App\Enums\StatusEnum;
use Mockery\Exception;
use Pest\Expectation;

class FileController extends Controller
{
    private $register;

    public function __construct()
    {
        $this->register = RegisterController::getInstance();
    }

    public function create(FileStoreRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = $request->user();
            $repo = $user->repo()->when($request['repo_id'], function ($query, $repoId) {
                return $query->where('repos.id', $repoId);
            })->first();

            if ($repo->pivot->is_admin) {
                $uploadedFile = $request->file('file');
                $fileName = $uploadedFile->getClientOriginalName();
                $extension = $uploadedFile->getClientOriginalExtension();
                $fileNameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);
                $newFileName = $fileNameWithoutExtension . '_' . time() . '.' . $extension;
                $path = $uploadedFile->storeAs('uploads', $newFileName);
                $file = $repo->files()->create([
                    'name' => $fileName,
                    'path' => $path,
                    'status' => 'free',
                ]);
                $fileId = $file->id; // Get the file ID from the created file entity
                $this->register->addOperation($request['repo_id'], $fileId, $user->id, OperationEnum::CREATE);
                return $this->success(message: 'create file successfully');
            }
            return $this->error('you do not have permeation');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

    }

    public function get(ValidateRepoRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $repo_id = $request['repo_id'];
        $repo = $user->repo()->when($repo_id, function ($query, $repoId) {
            return $query->where('repos.id', $repoId);
        })->with('files')->first();
        return $this->success(new RepoResource($repo));
    }


    public function update(Request $request, $fileId): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $file = $user->repo()->findOrFail($fileId)->files->first();

        if ($file) {
            $uploadedFile = $request->file('file');
            $fileName = $uploadedFile->getClientOriginalName();
            $extension = $uploadedFile->getClientOriginalExtension();
            $fileNameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);
            $newFileName = $fileNameWithoutExtension . '_' . time() . '.' . $extension;
            $path = $uploadedFile->storeAs('uploads', $newFileName);

            $existingFileHash = hash_file('md5', Storage::path($file->path));
            $newFileHash = hash_file('md5', $uploadedFile->path());

            if ($existingFileHash !== $newFileHash) {
                Storage::delete($file->path);

                $file->update([
                    'name' => $fileName,
                    'path' => $path,
                    'status' => 'free',
                ]);
                $this->register->addOperation($request['repo_id'], $fileId, $user->id, OperationEnum::UPDATE);

                return response()->json(['message' => 'File updated successfully']);
            } else {
                return response()->json(['message' => 'File contents are the same']);
            }
        }

        return response()->json(['error' => 'You do not have permission to update this file']);
    }

    public function delete(ValidateFileRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = $request->user();
            $fileId = $request['file_id'];
            $file = File::where('id', $fileId)->with('repo')->first();

            $isUserAdmin = $user->repo()->when($file->repo->id, function ($query) use ($file, $user) {
                return $query->where('repo_users.repo_id', $file->repo->id)
                    ->where('repo_users.user_id', $user->id)
                    ->where('repo_users.is_admin', true);
            })->exists();

            if ($isUserAdmin) {
                Storage::delete($file->path);
                $file->delete();
                return $this->success(message: 'File deleted successfully');
            }
            return $this->error('You do not have permission to delete this file');
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

    }

    public function checkin(CheckInOutRequest $request)
    {
        try {
            $user = $request->user();
            $repo = $user->repo->where('id', $request['repo_id'])->first();
            if ($repo) {
                $fileIds = $request->input('file_id');
                $files = $repo->files()->whereIn('id', $fileIds);
                if(!$files->exists()){
                    return $this->error('the files not exists ');
                }
                $files = $files->get();
                DB::beginTransaction();
                foreach ($files as $file) {
                    if($file->status === StatusEnum::BLOCK){
                        return $this->error('the files not available ');
                    }
                    $file->status = StatusEnum::BLOCK;
                    $file->save();
                    $this->register->addOperation($request['repo_id'], $file->id, $user->id, OperationEnum::CHECKIN);
                }
            }
            DB::commit();
            return $this->success(message: 'checkin successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }

    public function checkout(CheckInOutRequest $request)
    {
        try {
            DB::beginTransaction();
            $fileIds = $request->input('file_id');
            $user = $request->user();
            $repo = $user->repo->where('id', $request['repo_id'])->first();
            foreach ($fileIds as $fileId) {
                $lastOperation = $this->register->getLastOperation($fileId, OperationEnum::CHECKIN);
                $file = $repo->files()->find($fileId);
                if ($repo) {
                    if ($lastOperation && $lastOperation->operation === OperationEnum::CHECKIN &&
                        $lastOperation->user_id === $user->id && !($file->status === StatusEnum::FREE)) {
                        $file->status = StatusEnum::FREE;
                        $file->save();
                        $this->register->addOperation($request['repo_id'], $file->id, $user->id, OperationEnum::CHECKOUT);

                    } else {
                        DB::rollBack();
                        return $this->error('the file : ' . $fileId . ' you do not checkin ');
                    }
                }
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
        return $this->success(message: 'checkout successfully');
    }

    public function download(ValidateFileRequest $request)
    {

        $fileId = $request['file_id'];
        try {
            $file = File::find($fileId);
            $fileContents = Storage::get($file->path);
            $extension = pathinfo($file->path, PATHINFO_EXTENSION);
            $base64File = base64_encode($fileContents);
            $fileData = [
                'extension' => $extension,
                'content' => $base64File
            ];

            ++$file->download_count;
            $file->save();
            $this->register->addOperation($file->repo->id, $file->id, $request->user()->id, OperationEnum::DOWNLOAD);

            return response()->json($fileData);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

