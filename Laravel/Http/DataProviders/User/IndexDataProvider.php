<?php

namespace App\Http\DataProviders\User;

use App\Http\Filters\User\IndexFilter;
use App\Models\User;
use Fifth\Generator\Http\DataProviders\DataProvider;

class IndexDataProvider extends DataProvider
{
    public function __construct(IndexFilter $filter)
    {
        $this->init($filter);
    }

    public function setBuilder()
    {
        $this->builder = User::with([
            'profilePicture',
            'favouriteUsers',
            'connections'
        ])->filterUsing($this->filter);
    }
}
