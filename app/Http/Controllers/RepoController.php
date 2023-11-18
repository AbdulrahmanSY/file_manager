<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\RepoRequest\RepoStoreRequest;
use Illuminate\Http\Request;

class RepoController extends Controller
{
    function createRepository(RepoStoreRequest  $request){
        $user= $request->user();
        return $user;
    }
}
