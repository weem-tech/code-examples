<?php

namespace App\Http\Requests\Auth;

use Fifth\Generator\Http\Requests\DataPersistRequest;
use Illuminate\Support\Facades\Auth;

class LogoutRequest extends DataPersistRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            //
        ];
    }

    public  function persist(): self
    {
        Auth::user()->logout();

        return $this;
    }

    protected function getMessage(): string
    {
        return 'User has been logged out';
    }
}
