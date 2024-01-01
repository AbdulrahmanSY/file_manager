<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileRequest\ValidateRepoRequest;
use App\Models\Register;
use Illuminate\Http\Request;

class RegisterController extends Controller
{

    private static $instance = null;

    private function __construct()
    {
    }

    public static function getInstance(): ?RegisterController
    {
        if (self::$instance === null) {
            self::$instance = new RegisterController();
        }
        return self::$instance;
    }

    public function addOperation(int $file_id,int $user_id,string $operation): void
    {
        $r=Register::create([
            'user_id'=>$user_id,
            'file_id'=>$file_id,
            'operation'=>$operation,
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
    }
}
