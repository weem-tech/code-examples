<?php

namespace App\Http\Requests\User;

use App\Mail\EmailValidationMail;
use App\Models\User;
use Carbon\Carbon;
use Fifth\Generator\Http\Requests\DataPersistRequest;
use Illuminate\Support\Facades\DB;

class ResendVerificationCodeRequest extends DataPersistRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'   => 'nullable|required_without:email|exists_on_model:User,id',
            'email'     => 'nullable|required_without:user_id|exists_on_model:User,email',
        ];
    }

    public function persist(): self
    {
        if ($this->user_id) {
            $this->user = User::findOrFail($this->user_id);
        } else {
            $this->user = User::findOrFailBy('email', $this->email);
        }

        if ($this->canSendCode()) {
            $this->sendValidationEmail();

            $this->message = 'Check your email.';
        } else {
            $blockedHour = $this->getBlockedHour();

            $this->message = 'Request blocked, please try ' . $blockedHour . ' hours later.';
        }

        return $this;
    }

    private function sendValidationEmail(): void
    {
        $code = $this->user->generateRandomCode();

        $this->user->sendMail(new EmailValidationMail($code));

        DB::table('email_verifications')->where('email', $this->user->email)
            ->update(['active' => false]);

        DB::table('email_verifications')->insert([
            'email' => $this->user->email,
            'token' => $code,
            'active' => true,
            'created_at' => Carbon::now()
        ]);
    }

    protected function getMessage(): string
    {
        return $this->message;
    }

    private function canSendCode(): bool
    {
        return DB::table('email_verifications')->where('email', $this->user->email)
                ->where('created_at', '<', Carbon::now())
                ->where('created_at', '>', Carbon::now()->subHours(24))->count() < 3;
    }

    private function getBlockedHour(): int
    {
        return Carbon::now()->subHours(24)->diffInHours(DB::table('email_verifications')
            ->where('email', $this->user->email)
            ->orderBy('created_at', 'desc')->first()->created_at);
    }
}
