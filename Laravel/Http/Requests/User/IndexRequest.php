<?php

namespace App\Http\Requests\User;

use Fifth\Generator\Http\Requests\BaseRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class IndexRequest extends BaseRequest
{
    public function authorizationRules()
    {
        return [
            'default' => Auth::user()->can('viewAny', User::class)
        ];
    }

    public function rules(): array
    {
        return [
            //
        ];
    }
}
