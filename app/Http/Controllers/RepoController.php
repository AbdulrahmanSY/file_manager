<?php

namespace App\Http\Controllers;

use App\Http\Requests\RepoRequest\RepoStoreRequest;
use App\Http\Requests\RepoRequest\RepoUpdateRequest;
use App\Http\Resources\RepoResource;
use App\Http\Resources\UserResource;
use App\Models\Repo;
use Illuminate\Http\Request;

class RepoController extends Controller
{
    function create(RepoStoreRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $repository = new Repo();
        $repository->name = $request->get('name');
        $repository->save();
        $user->repo()->attach($repository, ['is_admin' => true]);
        return $this->success(new UserResource($user->load('repo')));
    }
    function delete(Request $request): \Illuminate\Http\JsonResponse
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

    function get(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = $request->user();
            if ($user->repo()->where('user_id', $user->id)->exists()) {
                return $this->success(data:RepoResource::collection($user->repo()->get()));
            } else
                return $this->success(message: 'repo is not existing ');
        } catch (\Exception $e) {
            return $this->error($e);
        }
    }

//    function update(RepoUpdateRequest $request): \Illuminate\Http\JsonResponse
//    {
//        try{
//            $user = $request->user();
//            $repo_id = $request->id;
//            if($user->repo()->where('repo_id', $repo_id)->exists()){
//                $repo = $user->repo()->where('repo_id', $repo_id)->first();
//                if($repo->pivot->is_admin){
//                    $repo->update([
//                        'name' =>''
//                    ]);
//                    return $this->success(message: 'repo is update successfully');
//                }else{
//                    return $this->error(message: 'repo is existing but you have no permissions');
//                }
//            }
//            return $this->error(message: 'repo is not existing');
//        }catch (\Exception $e){
//            return $this->error($e);
//        }
//    }

}
