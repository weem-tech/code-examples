<?php

namespace App\Http\Requests\User;

use Fifth\Generator\Http\Requests\DataPersistRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UpdateRequest extends DataPersistRequest
{
    public function authorizationRules()
    {
        return [
            'default' => Auth::user()->can('update', $this->user)
        ];
    }

    public function rules(): array
    {
        return [
            'first_name'    => 'sometimes|required|min:2|max:100',
            'last_name'     => 'sometimes|required|min:2|max:100',
            'bio'           => 'nullable',
            'email'         => 'sometimes|required|email|unique:users',
            'avatar'        => 'nullable|exists_on_model:Attachment,id',
            'phone'         => 'nullable',
            'is_private'    => 'nullable|boolean',
        ];
    }

    public function persist(): self
    {
        $this->user->safeUpdate($this->getProcessedData());

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
