<?php

namespace App\Http\Controllers;

use App\Aspects\Logger;
use App\Http\Controllers\Controller;
use App\Http\Requests\FileRequest\CheckInOutRequest;
use App\Http\Requests\FileRequest\FileUpdateRequest;
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

#[Logger]
class FileController extends Controller
{
    private ?RegisterController $register;

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

    public function update(FileUpdateRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $file = File::where('id', $request['file_id'])->with('repo')->first();
        $repo = $file->repo;

        if ($user->can('has', $repo)) {
            $content = $request['content'];
            $existingFileHash = hash_file('md5', Storage::path($file->path));
            $newFileHash = hash('md5', $content);
            if ($existingFileHash !== $newFileHash) {
                $existingFileName = pathinfo($file->path, PATHINFO_FILENAME);
                $existingExtension = pathinfo($file->path, PATHINFO_EXTENSION);
                $newFileName = 'uploads/' . $existingFileName . '.' . $existingExtension;
                Storage::put($newFileName, $content);
                $file->path = $newFileName;
                $file->save();
                $this->register->addOperation($repo->id, $file->id, $user->id, OperationEnum::UPDATE);
                return response()->json(['message' => 'File updated successfully']);
            }
            return response()->json(['message' => 'File contents are the same']);
        }
        return response()->json(['error' => 'You do not have permission to update this file']);
    }

    public function delete(ValidateFileRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = $request->user();
            $fileId = $request['file_id'];
            $file = File::where('id', $fileId)->with('repo')->first();
            if ($user->can('is_admin', $file->repo)) {
                Storage::delete($file->path);

                $file->delete();
                $this->register->addOperation($file->repo->id, $file->id, $user->id, OperationEnum::DELETE);

                return $this->success(message: 'File deleted successfully');
            }
            return $this->error('You do not have permission to delete this file');
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

    }

    public function checkin(CheckInOutRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = $request->user();
            $repo = $user->repo()->lockForUpdate()->find($request['repo_id']);
            DB::beginTransaction();

            if ($repo) {
                $fileIds = $request->input('file_id');
                $files = $repo->files()->whereIn('id', $fileIds);
                if (!$files->exists()) {
                    return $this->error('the file not exists ');
                }
                $files = $files->get();
                foreach ($files as $file) {
                    if ($file->status === StatusEnum::BLOCK) {
                        return $this->error('the file : '. $file->name . ' not available ');
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
    public function checkout(CheckInOutRequest $request): \Illuminate\Http\JsonResponse
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

    public function download(ValidateFileRequest $request): \Illuminate\Http\JsonResponse
    {
        $fileId = $request['file_id'];
        try {
            $file = File::find($fileId);
            $fileContents = Storage::get($file->path);
            $extension = pathinfo($file->path, PATHINFO_EXTENSION);
            $base64File = base64_encode($fileContents);
            $fileData = [
                'name' => $file->name,
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

