<?php

namespace App\Http\Requests\Auth;

use App\Mail\EmailValidationMail;
use App\Models\User;
use Carbon\Carbon;
use Fifth\Generator\Http\Requests\DataPersistRequest;
use Illuminate\Support\Facades\DB;

class RegisterRequest extends DataPersistRequest
{
    public $user;

    public function authorizationRules(): array
    {
        return [
            'default' => true,
        ];
    }

    public function rules(): array
    {
        return [
            'first_name'    => 'required|min:2|max:100',
            'last_name'     => 'required|min:2|max:100',
            'email'         => 'required|email|unique:users',
            'password'      => 'required|confirmed|min:6|max:180',
        ];
    }

    public function persist(): self
    {
        $this->user = User::create($this->getProcessedData());

        $this->sendValidationEmail();

        return $this;
    }

    protected function getMergingData(): array
    {
        return [
            'domain'    => getEmailDomain($this->email),
            'active'    => false,
        ];
    }

    private function sendValidationEmail(): void
    {
        $code = $this->user->generateRandomCode();

        $this->user->sendMail(new EmailValidationMail($code));

        DB::table('email_verifications')->insert([
            'email'         => $this->user->email,
            'token'         => $code,
            'active'        => true,
            'created_at'    => Carbon::now()
        ]);
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
