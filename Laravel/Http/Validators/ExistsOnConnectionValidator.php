<?php

namespace App\Http\Validators;

use App\Models\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ExistsOnConnectionValidator
{
    public function existsOnConnection($attribute, $value, $parameters, $validator): bool
    {
        return Auth::user()->connections()->whereHas('users', function (Builder $builder) use($value){
            $builder->where('user_id', $value);
        })->where('status', Connection::STATUS['accept'])->exists() || $value == Auth::id();
    }

    public function notExistsOnConnection($attribute, $value, $parameters, $validator): bool
    {
        return Auth::user()->connections()->whereHas('users', function (Builder $builder) use($value){
            $builder->where('user_id', $value);
        })->where(function (Builder $builder) {
            $builder->where('status', Connection::STATUS['accept'])
                ->orWhere('status', Connection::STATUS['pending']);
        })
        ->get()
        ->isEmpty();
    }
}
