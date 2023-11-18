<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest\LoginRequest;
use App\Http\Requests\AuthRequest\RegisterRequest;
use App\Http\Requests\AuthRequest\VerifyRequest;
use App\Mail\verifyMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    function register(RegisterRequest $request): \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Foundation\Application|\Illuminate\Http\Response
    {
        try {
            $existing = User::where('email', '=', $request->email);
            if (!$existing->exists()) {
                $user = User::Create([
                    'name' => $request['name'],
                    'email' => $request['email'],
                    'password' => bcrypt($request['password'])
                ]);
                Mail::to($request->email)->send(new verifyMail($user));
            } else {
                $user = User::where('email', '=', $request->email)->first();
                Mail::to($request->email)->send(new verifyMail($user));
            }
            return response(['message:' => 'send code'], 200);
        } catch (\Exception $e) {
            return response(['message:' => $e->getMessage()]);
        }
    }

    function verify(VerifyRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = User::where('email', $request->email)->first();
        if ($user->code == $request->code) {
            $user->update([
                'code' => null,
                'verify' => true
            ]);
            $token = $user->createToken('apiToken')->plainTextToken;
            return response()->json(['token' => $token]);
        }
        return response()->json(['message' => 'Otp wrong ']);
    }

    function login(LoginRequest $request): \Illuminate\Foundation\Application|\Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        $user = User::where('email', $request['email'])->first();
        if (!$user || !Hash::check($request['password'], $user->password) || !$user->verify) {
            return response([
                'msg' => 'incorrect username or password or not verification'
            ], 401);
        }
        $token = $user->createToken('apiToken')->plainTextToken;
        $res = [
            'user' => $user,
            'token' => $token
        ];
        return response($res, 201);
    }

    function logout(Request $request): array
    {
        if (Auth::user() !== null) {
            Auth::user()->tokens()->delete();
            return [
                'message' => 'User logged out',
            ];
        } else {
            return [
                'message' => 'User not logged in',
            ];
        }
    }
}
