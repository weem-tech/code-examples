<?php

namespace App\Models;

use A6digital\Image\DefaultProfileImage;
use App\Events\UserCreated;
use App\Events\UserCreating;
use App\Events\UserSaving;
use App\Events\UserUpdated;
use App\Events\UserUpdating;
use App\Managers\Payment\PaymentManager;
use App\Models\Fragments\User\CustomAuth;
use App\Models\Fragments\User\Getters;
use App\Models\Fragments\User\Mailer;
use App\Models\Fragments\User\Relations;
use App\Models\Fragments\User\ResetPassword;
use App\Models\Fragments\User\Scopes;
use App\Models\Fragments\User\VerificationCode;
use Carbon\Carbon;
use Fifth\Generator\Common\BaseModel;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\HasApiTokens;
use Stripe\Customer;

class User extends BaseModel implements AuthenticatableContract, AuthorizableContract
{
    use Relations, Scopes, HasApiTokens, Notifiable, Authorizable, Authenticatable,
        CustomAuth, ResetPassword, VerificationCode, Getters, Mailer;

    protected $fillable = [
        'customer_id',
        'first_name',
        'last_name',
        'bio',
        'avatar',
        'email',
        'active',
        'domain',
        'password',
        'phone',
        'is_private'
    ];

    protected $dispatchesEvents = [
        'saving'    => UserSaving::class,
        'creating'  => UserCreating::class,
        'created'   => UserCreated::class,
        'updating'  => UserUpdating::class,
        'updated'   => UserUpdated::class
    ];

    public function createStripeCustomer(): Customer
    {
        return PaymentManager::getInstance()->createCustomer($this->email);
    }

    public function createDefaultAvatar()
    {
        $img = DefaultProfileImage::create($this->first_name . ' ' . $this->last_name, 256, '#1d4f44', '#FFF');
        $uploadFileName = getFileStoreName('png');
        Storage::put(Attachment::ATTACHMENTS_PATH . '/' . $uploadFileName, $img->encode('png'), 'public');

        return Attachment::create([
            'type' => Attachment::TYPES['image'],
            'path' => $uploadFileName,
            'name' => $this->first_name . '_' . $this->last_name . '_default_avatar.png'
        ]);
    }

    public function hasActiveSubscription(): bool
    {
        if ($this->subscription) {
            return $this->subscription->status == UserSubscription::STATUS['active']
                || ($this->subscription->status == UserSubscription::STATUS['canceling']
                    && $this->subscription->end_date > Carbon::now());
        }

        return false;
    }

    public function isPro(): bool
    {
        return UserSubscription::withoutGlobalScopes()
            ->where('user_id', $this->id)
            ->where(function (Builder $builder) {
                $builder->where('status', UserSubscription::STATUS['active'])
                    ->orWhere(function (Builder $builder) {
                        $builder->where('status', UserSubscription::STATUS['canceling'])
                            ->where('end_date', '>', Carbon::now());
                    });
            })->whereHas('subscriptionPlan', function (Builder $builder) {
                $builder->where('price', '!=', 0);
            })->exists();
    }

    public function isFavourite(): bool
    {
        return $this->favouriteUsersInverse()->where('user_id', Auth::id())->exists();
    }
}
