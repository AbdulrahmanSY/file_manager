<?php

namespace App\Http\Controllers;

use App\Aspects\Logger;
use App\Http\Requests\AuthRequest\LoginRequest;
use App\Http\Requests\AuthRequest\RegisterRequest;
use App\Http\Requests\AuthRequest\VerifyRequest;
use App\Http\Resources\UserResource;
use App\Jobs\SendMailJob;
use App\Mail\verifyMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

//#[Logger]
class AuthController extends Controller
{
    function register(RegisterRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $existing = User::where('email', '=', $request->email);
            if (!$existing->exists()) {
                $user = User::Create([
                    'name' => $request['name'],
                    'email' => $request['email'],
                    'password' => bcrypt($request['password'])
                ]);
                SendMailJob::dispatch($user);
            } else {
                $user = User::where('email', '=', $request->email)->first();
                SendMailJob::dispatch($user);
            }
            return $this->success(message: 'send code');
        } catch (\Exception $e) {
            return $this->error(['message:' => $e->getMessage()]);
        }
    }

    public function verify(VerifyRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = User::where('email', $request->email)->first();
        if ($user->code == $request->code) {
            $user->update([
                'code' => null,
                'verify' => true
            ]);
            $token = $user->createToken('apiToken')->plainTextToken;
            $res = [
                'user' => $user,
                'token' => $token
            ];

            return $this->success(data: $res);
        }
        return $this->success(message: 'Otp wrong ');
    }

    public function login(LoginRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = User::where('email', $request['email'])->first();
        if (!$user || !Hash::check($request['password'], $user->password) || !$user->verify) {
            return $this->error(message: 'incorrect username or password or not verification'
            );
        }
        $token = $user->createToken('apiToken')->plainTextToken;
        $res = [
            'user' => $user,
            'token' => $token
        ];
        return $this->success($res);
    }

    public function logout(Request $request): array
    {
        if (Auth::user() !== null) {
            Auth::user()->tokens()->delete();
            return [
                'message' => 'User logged out',
            ];
        }

        return [
            'message' => 'User not logged in',
        ];
    }

    public function get(Request $request)
    {
        $user = Auth::user();
        $users = User::where('id', '!=', $user->id)->get();
        return $this->success(UserResource::collection($users));
    }

}
