<?php

namespace App\Models\Fragments\User;


use App\Models\User;
use Fifth\Generator\Http\Requests\BaseRequest;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Token;

trait CustomAuth
{
    public $authToken;

    public static function loginOrFail(BaseRequest $request): User
    {
        self::loginUserByRequest($request);

        return Auth::user();
    }

    public function loginAfterVerification(User $user): User
    {
        self::abortDisabledUser($user);

        self::loginUser($user);

        return Auth::user();
    }

    private static function loginUser(User $user): void
    {
        Auth::login($user);

        Auth::user()->setTokensAndGetUser(...array_values(Auth::user()->createToken('')->toArray()));
    }

    private static function loginUserByRequest(BaseRequest $request): void
    {
        abort_unless(Auth::attempt($request->only(['email', 'password'])), 401, 'Invalid email address or password');

        Auth::user()->setTokensAndGetUser(...array_values(Auth::user()->createToken('')->toArray()));
    }

    private static function abortDisabledUser(User $user): void
    {
        abort_if(!$user->active, 403, 'Not Verified User');
    }

    private static function abortDisabledUserAndResendCode(User $user): void
    {
        if (!$user->active) {
            if ($user->canSendCode()) {
                $user->sendValidationEmail();
            }

            abort(403, 'Not Verified User');
        }
    }

    public function setTokensAndGetUser(string $authToken, Token $token): self
    {
        $this->authToken = $authToken;

        $this->withAccessToken($token);

        return $this;
    }

    public function logout(): void
    {
        Auth::user()->token()->revoke();
    }
}
