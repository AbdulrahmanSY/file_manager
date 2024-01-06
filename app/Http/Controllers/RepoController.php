<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileRequest\ValidateRepoRequest;
use App\Http\Requests\RepoRequest\AddDeleteUserToRepoRequesrt;
use App\Http\Requests\RepoRequest\RepoStoreRequest;
use App\Http\Resources\RepoResource;
use App\Http\Resources\UserResource;
use App\Models\Repo;
use App\Models\User;
use Illuminate\Http\Request;

class RepoController extends Controller
{
    public function create(RepoStoreRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $repository = new Repo();
        $repository->name = $request->get('name');
        $repository->save();
        $user->repo()->attach($repository, ['is_admin' => true]);
        return $this->success(new UserResource($user->load('repo')));
    }

    public function delete(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = $request->user();
            $repo_id = $request->id;
            if ($user->repo()->where('repo_id', $repo_id)->exists()) {
                $repo = $user->repo()->where('repo_id', $repo_id)->first();
                if ($repo->pivot->is_admin) {
                    $user->repo()->detach($repo);
                    $repo->delete();
                    return $this->success(message: 'repo is deleted successfully');
                } else {
                    return $this->error(message: 'repo is existing but you have no permissions');
                }
            }
            return $this->error(message: 'repo is not existing');
        } catch (\Exception $e) {
            return $this->error($e);
        }
    }

    public function get(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = $request->user();
            $repos = $user->repo()->with('users')->get();
            if ($user->repo()->exists()) {
                return $this->success(data:RepoResource::collection($repos));
            } else
                return $this->success(message: 'repo is not existing ');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function addDeleteUserToRepo(AddDeleteUserToRepoRequesrt $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = $request->user();
            $repoId = $request->repo_id;
            $anotherUserId = $request->user_id;
            $repo = $user->repo()->where('repo_id', $repoId)->first();

            if ($repo->pivot->is_admin) {
                $anotherUser = User::find($anotherUserId);
                if ($anotherUser) {
                    if (!$anotherUser->repo()->where('repo_id', $repoId)->exists()) {
                        $anotherUser->repo()->attach($repoId);
                        return $this->success(message:'User added successfully');
                    } else {
                        $anotherUser->repo()->detach($repoId);
                        return $this->success(message:'User deleted from repo');
                    }
                } else {
                    return $this->error('User not found');
                }
            } else {
                return $this->error('You have no permissions');
            }
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function getUsersRepo(ValidateRepoRequest $request){

        $repo = Repo::where('id',$request['repo_id'])->with('users')->first();
        return $this->success(UserResource::collection($repo->users));
    }
}
