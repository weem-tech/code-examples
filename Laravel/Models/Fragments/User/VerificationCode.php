<?php

namespace App\Models\Fragments\User;

use App\Mail\EmailValidationMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait VerificationCode
{
    public function sendValidationEmail(): void
    {
        $code = $this->generateRandomCode();

        $this->sendMail(new EmailValidationMail($code));

        DB::table('email_verifications')->where('email', $this->email)
            ->update(['active' => false]);

        DB::table('email_verifications')->insert([
            'email' => $this->email,
            'token' => $code,
            'active' => true,
            'created_at' => Carbon::now()
        ]);
    }

    public function canSendCode(): bool
    {
        return DB::table('email_verifications')->where('email', $this->email)
                ->where('created_at', '<', Carbon::now())
                ->where('created_at', '>', Carbon::now()->subHours(24))->count() < 3;
    }

    public function getBlockedHour(): int
    {
        return Carbon::now()->subHours(24)->diffInHours(DB::table('email_verifications')
            ->where('email', $this->email)
            ->orderBy('created_at', 'desc')->first()->created_at);
    }
}
