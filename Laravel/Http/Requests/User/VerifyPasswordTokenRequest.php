<?php

namespace App\Http\Requests\User;

use Carbon\Carbon;
use Fifth\Generator\Http\Requests\BaseRequest;
use Illuminate\Support\Facades\DB;

class VerifyPasswordTokenRequest extends BaseRequest
{

    public function rules(): array
    {
        return [
            'token' => 'required',
            'email' => 'required',
        ];
    }

    public function persist()
    {
        return DB::table('password_resets')
            ->where('token', $this->token)
            ->where('email', $this->email)
            ->where('created_at', '>', Carbon::now()->subMinutes(15))
            ->first();
    }
}
