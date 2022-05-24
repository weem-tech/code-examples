<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Fifth\Generator\Http\Requests\DataPersistRequest;
use Illuminate\Support\Facades\DB;

class ResetPasswordRequest extends DataPersistRequest
{
    public function authorizationRules()
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'     => 'required|exists:password_resets',
            'password'  => 'required|confirmed'
        ];
    }

    public function persist(): self
    {
        $email = DB::table('password_resets')->where('token', $this->token)->first()->email;
        User::findBy('email', $email)->resetPassword($this->password);

        return $this;
    }

    protected function getMessage(): string
    {
        return 'You password successfully updated.';
    }
}
