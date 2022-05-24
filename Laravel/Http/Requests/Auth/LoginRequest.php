<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Carbon\Carbon;
use Fifth\Generator\Http\Requests\DataPersistRequest;

class LoginRequest extends DataPersistRequest
{
    private $user;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'     => 'required|email',
            'password'  => 'required',
        ];
    }

    public function persist(): self
    {
        $this->user = User::loginOrFail($this);

        if (!$this->user->active) {
            if ($this->user->canSendCode()) {
                $this->user->sendValidationEmail();
            }
            return $this;
        }

        $this->user->last_login_at = Carbon::now();
        $this->user->save();

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
