<?php

namespace App\Models\Fragments\User;

use App\Mail\ResetPasswordCodeMail;
use Illuminate\Support\Facades\DB;

trait ResetPassword
{
    public function sendResetPasswordCode(): void
    {
        $token = $this->generateRandomCode();
        $this->sendMail(new ResetPasswordCodeMail($token));

        DB::table('password_resets')->insert([
            'email' => $this->email,
            'token' => $token,
            'created_at' => now()
        ]);
    }

    public function generateRandomCode(): string
    {
        $code = rand(1, 999999);

        return sprintf('%06d', $code);
    }

    public function resetPassword(string $password): void
    {
        $this->password = $password;
        $this->save();

        DB::table('password_resets')->where('email', $this->email)->delete();
    }
}
