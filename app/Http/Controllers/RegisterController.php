<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileRequest\ValidateRepoRequest;
use App\Http\Resources\ReportResource;
use App\Models\Register;
use Illuminate\Http\Request;

class RegisterController extends Controller
{

    private static $instance = null;

//    private function __construct()
//    {
//    }

    public static function getInstance(): ?RegisterController
    {
        if (self::$instance === null) {
            self::$instance = new RegisterController();
        }
        return self::$instance;
    }

    public function addOperation(int $repo_id,int $file_id,int $user_id,string $operation): void
    {
        $r=Register::create([
            'user_id'=>$user_id,
            'file_id'=>$file_id,
            'operation'=>$operation,
            'repo_id'=>$repo_id
        ]);
    }
    public function getLastOperation(int $file_id,string $operation)
    {
        $lastOperation = Register::where('file_id', $file_id)
            ->where('operation', $operation)
            ->orderBy('created_at', 'desc')
            ->first();

        return $lastOperation ?: null;
    }
    public function getReport(ValidateRepoRequest $request)
    {
        $repo = Register::where('repo_id',$request['repo_id'])
            ->with('file','user')
            ->orderBy('created_at', 'desc')
            ->get();
        return $this->success(ReportResource::collection($repo));
    }
}
