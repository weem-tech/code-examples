<?php

namespace App\Models\Fragments\User;


use App\Models\Attachment;
use App\Models\BillingAddress;
use App\Models\Connection;
use App\Models\CreditCard;
use App\Models\Meeting;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait Relations
{
    public function subscription(): HasOne
    {
        return $this->hasOne(UserSubscription::class);
    }

    public function billingAddress(): HasOne
    {
        return $this->hasOne(BillingAddress::class);
    }

    public function creditCard(): HasOne
    {
        return $this->hasOne(CreditCard::class);
    }



    public function favouriteUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favourite_users', 'user_id', 'favourite_user_id');
    }

    public function favouriteUsersInverse(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favourite_users', 'favourite_user_id', 'user_id');
    }

    public function connections(): BelongsToMany
    {
        return $this->belongsToMany(Connection::class);
    }

    public function profilePicture(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'avatar');
    }

    public function meeting(): HasOne
    {
        return $this->hasOne(Meeting::class, 'owner_id');
    }

    public function blockedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'blocked_users', 'user_id', 'blocked_user_id');
    }
}
