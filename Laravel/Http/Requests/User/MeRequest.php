<?php

namespace App\Http\Requests\User;

use Fifth\Generator\Http\Requests\BaseRequest;

class MeRequest extends BaseRequest
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
}
