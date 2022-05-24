<?php

namespace App\Http\Requests\Auth;

use App\Mail\UserInviteMail;
use App\Models\Fragments\User\Mailer;
use App\Models\User;
use Carbon\Carbon;
use Fifth\Generator\Http\Requests\DataPersistRequest;
use Illuminate\Support\Facades\DB;

class ForgotPasswordRequest extends DataPersistRequest
{
    use Mailer;

    const maxSendCodeCount = 3;

    private $user;
    private $message;
    private $responseCode;

    public function authorizationRules()
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email'
        ];
    }

    public function persist(): self
    {
        $this->user = User::findBy('email', $this->email);
        if ($this->user) {
            if ($this->canSendToken()) {
                $this->user->sendResetPasswordCode();

                $this->message = 'We send code to your email.';
                $this->responseCode = 200;
            } else {
                $blockedHour = $this->getBlockedHour();
                $this->message = 'Request blocked, please try ' . $blockedHour . ' hours later.';
                $this->responseCode = 429;
            }
        } else {
            $this->sendUserInviteEmail();
            $this->message = 'We send invitation to your email.';
            $this->responseCode = 200;
        }

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getResponseCode(): int
    {
        return $this->responseCode;
    }

    public function getUser()
    {
        return $this->user;
    }

    private function sendUserInviteEmail(): void
    {
        $this->sendMail(new UserInviteMail());
    }

    private function canSendToken(): bool
    {
        return DB::table('password_resets')->where('email', $this->email)
                ->where('created_at', '<', Carbon::now())
                ->where('created_at', '>', Carbon::now()->subHours(24))->count() < self::maxSendCodeCount;;
    }

    private function getBlockedHour(): int
    {
        return Carbon::now()->subHours(24)->diffInHours(DB::table('password_resets')
            ->where('email', $this->email)
            ->orderBy('created_at', 'desc')->first()->created_at);
    }
}
