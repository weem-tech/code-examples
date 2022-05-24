<?php

namespace App\Http\Requests\User;

use Fifth\Generator\Http\Requests\DataPersistRequest;
use Illuminate\Support\Facades\Auth;

class AddToBlockedRequest extends DataPersistRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists_on_model:User,id'
        ];
    }

    public function persist(): self
    {
        Auth::user()->blockedUsers()->syncWithoutDetaching($this->user_id);

        return $this;
    }

    protected function getMessage(): string
    {
        return 'User added to your blocked users list.';
    }
}
