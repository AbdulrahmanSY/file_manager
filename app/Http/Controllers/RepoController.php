<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\RepoRequest\RepoStoreRequest;
use App\Http\Resources\RepoResource;
use App\Http\Resources\UserResource;
use App\Models\Repo;
use Illuminate\Http\Request;

class RepoController extends Controller
{
    function create(RepoStoreRequest  $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $repository = new Repo();
        $repository->name = $request->get('name');
        $repository->save();

        // Associate the repository with the user
        $user->repo()->attach($repository,['is_admin' => true]);
        return $this->success(new UserResource($user->load('repo')));
    }
    function delete(Request $request){

    }

}
