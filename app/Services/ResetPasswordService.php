<?php

namespace App\Services;

use App\Models\PasswordReset;
use App\Models\User;
use Hash;

class ResetPasswordService
{
    private $user;
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function forgotPassword($requestInfo)
    {
        $user = $this->user->where('email', $requestInfo['email'])->first(['status', 'name']);

        if (is_null($user) || $user->status != 0) {
            return response()->json([
                'error' => 'O campo email selecionado é inválido.'
            ], 422);
        }

        PasswordReset::where('email', $requestInfo['email'])->delete();

        $requestInfo['token'] = mt_rand(100000, 999999);

        $token = PasswordReset::create($requestInfo);

        NotificationService::generateDataSendRedisResetPassword([$requestInfo['email']], 'Esqueci a minha senha', 'forgot-password', $user->name, $token->token);

        return response(['message' => trans('passwords.sent')], 200);
    }

    public function checkReset($requestInfo)
    {
        $codeCheck = PasswordReset::firstWhere([
            'email' => $requestInfo['email'],
            'token' => $requestInfo['token']
        ]);

        if ($codeCheck->created_at->addMinutes(30) < now()) {
            PasswordReset::where([
                'email' => $requestInfo['email'],
                'token' => $requestInfo['token']
            ])->delete();
            return response(['error' => 'O código expirou'], 422);
        }

        return response([
            'token' => $codeCheck->token,
            'message' => 'O código é valido!'
        ], 200);
    }

    public function resetPassword($requestInfo)
    {
        $user = User::firstWhere('email', $requestInfo['email']);

        if (is_null($user) || $user->status != 0) {
            return response()->json([
                'error' => 'O campo email selecionado é inválido.'
            ], 422);
        }

        $passwordReset = PasswordReset::firstWhere([
            'email' => $requestInfo['email'],
            'token' => $requestInfo['token']
        ]);

        if ($passwordReset->created_at->addMinutes(30) < now()) {
            PasswordReset::where([
                'email' => $requestInfo['email'],
                'token' => $requestInfo['token']
            ])->delete();
            return response(['message' => 'O código expirou'], 422);
        }

        $user->update(['password' => Hash::make($requestInfo['password'])]);

        PasswordReset::where([
            'email' => $requestInfo['email'],
            'token' => $requestInfo['token']
        ])->delete();

        return response(['message' => 'Senha alterada com sucesso!'], 200);
    }
}
