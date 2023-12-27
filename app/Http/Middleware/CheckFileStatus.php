<?php

namespace App\Http\Middleware;

use App\Enums\OperationEnum;
use App\Enums\StatusEnum;
use App\Http\Controllers\RegisterController;
use App\Http\Requests\FileRequest\ValidateFileRequest;
use App\Models\File;
use App\Models\Register;
use App\Traits\ApiResponderTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFileStatus
{
    use ApiResponderTrait;
    public function __construct()
    {
        $this->register = RegisterController::getInstance();
    }
    public function handle(Request $request, Closure $next): Response
    {

        $user = $request->user();
        $file_id = $request['file_id'];
        $file = File::where('id', $file_id)->first();
        if ($file->status === StatusEnum::BLOCK) {
            $operation = $this->register->getLastOperation($file_id, OperationEnum::CHECKIN);
            if ($operation->user_id === $user->id) {
                return $next($request);
            }
            return $this->error('This file is blocked by other users.');
        }
        return $this->error('File should be checked in first.');
    }
}
