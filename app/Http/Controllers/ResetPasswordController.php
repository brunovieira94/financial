<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\CheckResetRequest;
use App\Services\ResetPasswordService;
use Illuminate\Http\Request;

class ResetPasswordController extends Controller
{
    private $resetPasswordService;

    public function __construct(ResetPasswordService $resetPasswordService)
    {
        $this->resetPasswordService = $resetPasswordService;
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        return $this->resetPasswordService->forgotPassword($request->all());
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        return $this->resetPasswordService->resetPassword($request->all());
    }

    public function checkReset(CheckResetRequest $request)
    {
        return $this->resetPasswordService->checkReset($request->all());
    }
}
