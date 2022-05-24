<?php

namespace App\Http\Validators;

use Illuminate\Support\Facades\Auth;

class ExistsOnBlockedValidator
{
    public function existsOnBlocked($attribute, $value, $parameters, $validator): bool
    {
        return Auth::user()->blockedUsers->where('id', $value) || $value == Auth::id();
    }

    public function notExistsOnBlocked($attribute, $value, $parameters, $validator): bool
    {
        return Auth::user()->blockedUsers->where('id', $value)->isEmpty();
    }
}
