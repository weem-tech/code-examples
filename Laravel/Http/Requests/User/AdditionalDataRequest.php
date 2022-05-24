<?php

namespace App\Http\Requests\User;

use App\Models\Connection;
use Fifth\Generator\Http\Requests\BaseRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AdditionalDataRequest extends BaseRequest
{
    public function authorizationRules()
    {
        return [
            'default' => true
        ];
    }

    public function rules(): array
    {
        return [
            //
        ];
    }

    public function getConnectionsCount(): int
    {
        return $this->getConnectionsBuilder()->count();
    }

    private function getConnectionsBuilder(): Builder
    {
        return Connection::whereHas('users', function (Builder $builder) {
            $builder->where('users.id', Auth::id());
        })->where('status', Connection::STATUS['accept']);
    }
}
