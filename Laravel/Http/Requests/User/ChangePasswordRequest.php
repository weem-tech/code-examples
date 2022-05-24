<?php

namespace App\Http\Requests\User;

use Fifth\Generator\Http\Requests\DataPersistRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ChangePasswordRequest extends DataPersistRequest
{
    public function authorizationRules()
    {
        return [
            'default' => Hash::check($this->old_password, Auth::user()->password),
        ];
    }

    public function rules(): array
    {
        return [
            'old_password'  => 'required',
            'password'      => 'confirmed|required',
        ];
    }

    public function persist(): self
    {
        Auth::user()->safeUpdate($this->getProcessedData());

        return $this;
    }

    protected function getMessage(): string
    {
        return 'Your password has been changed.';
    }
}
