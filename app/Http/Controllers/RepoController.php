<?php

namespace App\Http\Controllers;

use App\Aspects\Logger;
use App\Http\Requests\FileRequest\ValidateRepoRequest;
use App\Http\Requests\RepoRequest\AddDeleteUserToRepoRequesrt;
use App\Http\Requests\RepoRequest\RepoStoreRequest;
use App\Http\Resources\RepoResource;
use App\Http\Resources\UserResource;
use App\Models\Repo;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

#[Logger]
class RepoController extends Controller
{
    public function create(RepoStoreRequest $request)
    {
        $user = $request->user();
        $repo = $user->repo();
        $repository = new Repo();
        $repository->name = $request->get('name');
        $repository->save();
        $user->repo()->attach($repository, ['is_admin' => true]);

        return  $this->get( $request);
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
                    $repo->files()->delete();
                    $repo->delete();
                    return $this->success(message: 'repo is deleted successfully');
                }

                return $this->error(message: 'repo is existing but you have no permissions');
            }
            return $this->error(message: 'repo is not existing');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function get(Request $request)
    {

            $repos = $request->user()->repo()->withCount('users')->paginate(9);
            if ($request->user()->repo()->exists()) {
//            return $repos;
                if ($repos->isNotEmpty()) {
                    $repoResourceCollection = RepoResource::collection($repos);
                    $firstPage = $repos->currentPage();
                    $lastPage = $repos->lastPage();

                    return $this->success([
                        'data' => $repoResourceCollection,
                        'current_page' => $firstPage,
                        'last_page' => $lastPage,
                    ]);
                }
            }
            return $this->success(message: 'repo is not existing ');

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
                        return $this->success(message: 'User added successfully');
                    }

                    $anotherUser->repo()->detach($repoId);
                    return $this->success(message: 'User deleted from repo');
                }

                return $this->error('User not found');
            }

            return $this->error('You have no permissions');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function getUsersRepo(ValidateRepoRequest $request): \Illuminate\Http\JsonResponse
    {
        $repo = Repo::where('id', $request['repo_id'])->with('users')->first();
        return $this->success(UserResource::collection($repo->users));
    }
}
