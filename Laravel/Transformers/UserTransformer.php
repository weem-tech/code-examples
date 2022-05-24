<?php

namespace App\Transformers;

use App\Models\Connection;
use App\Models\User;
use Fifth\Generator\Common\Transformer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UserTransformer extends Transformer
{
    /**
     * @OA\Schema(
     *   schema="UserListing",
     *   type="object",
     *   @OA\Property(property="id",  type="integer"),
     *   @OA\Property(property="first_name",  type="string"),
     *   @OA\Property(property="last_name",  type="string"),
     *   @OA\Property(property="email",  type="string"),
     * )
     * @param User $user
     * @return array
     */
    public function listingTransform(User $user): array
    {
        if ($user->id !== Auth::id()) {
            $connection = $user->connections->load('users')->filter(function (Connection $connection) use ($user) {
                return $connection->users->where('id', Auth::id())->isNotEmpty();
            })->first();
        }

        return array_merge($this->simpleTransform($user), [
            'is_favourite'  => $user->isFavourite(),
            'connection'    => !empty($connection) ? ConnectionTransformer::simple($connection) : null,
        ]);
    }


    /**
     * @OA\Schema(
     *   schema="UserLogin",
     *   type="object",
     *   @OA\Property(property="id",  type="integer"),
     *   @OA\Property(property="first_name",  type="string"),
     *   @OA\Property(property="last_name",  type="string"),
     *   @OA\Property(property="email",  type="string"),
     *   @OA\Property(property="api_token",  type="string"),
     * )
     * @param User $user
     * @return array
     */
    public function loginTransform(User $user): array
    {
        return array_merge($this->simpleTransform($user) , [
            'api_token'                 => $user->authToken,
            'subscription'              =>  $user->subscription ? UserSubscriptionTransformer::detailed($user->subscription) : null,
            'has_active_subscription'   => $user->hasActiveSubscription(),
        ]);
    }

    /**
     * @OA\Schema(
     *   schema="UserMe",
     *   type="object",
     *   @OA\Property(property="id",  type="integer"),
     *   @OA\Property(property="first_name",  type="string"),
     *   @OA\Property(property="last_name",  type="string"),
     *   @OA\Property(property="email",  type="string"),
     * )
     * @param User $user
     * @return array
     */
    public function meTransform(User $user): array
    {
        return array_merge($this->simpleTransform($user) , [
            'subscription'              => $user->subscription ? UserSubscriptionTransformer::detailed($user->subscription) : null,
            'billing_address'           => $user->billingAddress ? BillingAddressTransformer::simple($user->billingAddress) : null,
            'has_active_subscription'   => $user->hasActiveSubscription(),
            'credit_card'               => $user->creditCard ? CreditCardTransformer::simple($user->creditCard): null,
        ]);
    }

    /**
     * @OA\Schema(
     *   schema="UserSimple",
     *   type="object",
     *   @OA\Property(property="id",  type="integer"),
     *   @OA\Property(property="first_name",  type="string"),
     *   @OA\Property(property="last_name",  type="string"),
     *   @OA\Property(property="email",  type="string"),
     * )
     * @param Model $model
     * @return array
     */
    public function simpleTransform(Model $model): array
    {
        return [
            'id'            => $model->id,
            'full_name'     => $model->full_name,
            'first_name'    => $model->first_name,
            'last_name'     => $model->last_name,
            'email'         => $model->email,
            'active'        => $model->active,
            'bio'           => $model->bio,
            'phone'         => $model->phone,
            'avatar'        => $model->avatar ? AttachmentTransformer::detailed($model->profilePicture) : null,
            'last_login_at' => $model->last_login_at,
            'is_private'    => $model->is_private ? true : false,
            'is_pro'        => $model->isPro()
        ];
    }
}
