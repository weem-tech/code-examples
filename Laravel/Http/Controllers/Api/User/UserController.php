<?php

namespace App\Http\Controllers\Api\User;

use App\Http\DataProviders\User\IndexDataProvider;
use App\Http\Requests\User\AddToBlockedRequest;
use App\Http\Requests\User\AddToFavouritesRequest;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\IndexRequest;
use App\Http\Requests\User\MeRequest;
use App\Http\Requests\User\RemoveFromBlockedRequest;
use App\Http\Requests\User\RemoveFromFavouritesRequest;
use App\Http\Requests\User\UpdateRequest;
use App\Models\User;
use App\Transformers\UserTransformer;
use Fifth\Generator\Http\ApiController;
use Illuminate\Support\Facades\Auth;

class UserController extends ApiController
{
    public function index(IndexRequest $request, IndexDataProvider $provider): array
    {
        return UserTransformer::pagination(
            $provider->getData(),
            'listingTransform'
        );
    }

    public function update(UpdateRequest $request, User $user): array
    {
        return UserTransformer::me(
            $request->persist()->getUser()
        );
    }

    public function me(MeRequest $request): array
    {
        return UserTransformer::me(Auth::user());
    }

    public function changePassword(ChangePasswordRequest $request): array
    {
        return $request->persist()->getResponseMessage();
    }

    public function addToFavourites(AddToFavouritesRequest $request): array
    {
        return $request->persist()->getResponseMessage();
    }

    public function removeFromFavourites(RemoveFromFavouritesRequest $request): array
    {
        return $request->persist()->getResponseMessage();
    }

    public function addToBlocked(AddToBlockedRequest $request): array
    {
        return $request->persist()->getResponseMessage();
    }

    public function removeFromBlocked(RemoveFromBlockedRequest $request): array
    {
        return $request->persist()->getResponseMessage();
    }

    public function blockedList(): array
    {
        return UserTransformer::pagination(
            Auth::user()->blockedUsers,
            'listingTransform'
        );
    }
}
