<?php

namespace App\Http\Requests\User;

use Fifth\Generator\Http\Requests\DataPersistRequest;
use Illuminate\Support\Facades\Auth;

class RemoveFromFavouritesRequest extends DataPersistRequest
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
        Auth::user()->favouriteUsers()->detach($this->user_id);

        return $this;
    }

    protected function getMessage(): string
    {
        return 'User removed from your favourite contacts.';
    }
}
