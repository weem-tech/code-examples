<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\LogoutRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\User\ResendVerificationCodeRequest;
use App\Http\Requests\User\VerifyPasswordTokenRequest;
use App\Http\Requests\User\VerifyRequest;
use App\Transformers\UserTransformer;
use Fifth\Generator\Http\ApiController;

class AuthController extends ApiController
{
    public function login(LoginRequest $request)
    {
        $user = $request->persist()->getUser();
        if (!$user->active) {
            return response()->json([
                'errorMessage' => 'Not Verified User.',
                'user' => UserTransformer::simple($user)
            ], 403);
        }

        return UserTransformer::login($user);
    }

    public function register(RegisterRequest $request): array
    {
        return UserTransformer::login(
            $request->persist()->getUser()
        );
    }

    public function verify(VerifyRequest $request): array
    {
        return UserTransformer::login(
            $request->manage()->getUser()
        );
    }

    public function resendVerificationCode(ResendVerificationCodeRequest $request): array
    {
        return $request->persist()->getResponseMessage();
    }

    public function logout(LogoutRequest $request): array
    {
        return $request->persist()->getResponseMessage();
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $user = $request->persist()->getUser();
        return response()->json([
            'isNewUser' => !$user,
            'message' => $request->getMessage()
        ], $request->getResponseCode());

        return $request->persist()->getResponseMessage();
    }

    public function verifyPasswordToken(VerifyPasswordTokenRequest $request)
    {
        $validToken = $request->persist() ? true : false;
        return response()->json([
            'validToken' => $validToken
        ], $validToken ? 200 : 403);
    }

    public function resetPassword(ResetPasswordRequest $request): array
    {
        return $request->persist()->getResponseMessage();
    }
}
