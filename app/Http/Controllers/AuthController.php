<?php

namespace App\Http\Controllers;
use App\Services\AuthService as AuthService;
use Illuminate\Http\Request;
use App\Http\Requests\StoreLoginRequest;

class AuthController extends Controller
{
    public function login(StoreLoginRequest $request)
    {
        $loginData = $request->all();

        if(!auth()->attempt($loginData)) {
            return response(['message'=>'Invalid credentials'], 422);
        }

        $accessToken = auth()->user()->createToken('Token User')->accessToken;
        return response(['user' => auth()->user(), 'access_token' => $accessToken]);
    }
}


