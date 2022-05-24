<?php

namespace App\Http\Filters\User;

use App\Models\Connection;
use Fifth\Generator\Http\Filters\AbstractFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class IndexFilter extends AbstractFilter
{
    protected $orderColumnMap = [

    ];

    public function handle(Builder $query): Builder
    {
        $this->setQuery($query)
            ->filterUsingRules()
            ->userFilter();

        return $this->query;
    }

    protected function userFilter(): self
    {
        $this->query->where('id', '!=', Auth::id());

        return $this;
    }

    public function rules(): array
    {
        return [
            'favourites'            => $this->action('filterListingWithFriendship'),
            'search'                => $this->action('searchByText'),
            'connections'           => $this->action('filterConnectedUsers'),
            'pending_connections'   => $this->action('filterRequestedConnectionUsers'),
            'without_connections'   => $this->action('filterWithoutPendingOrAcceptedConnectedUsers')
        ];
    }

    protected function filterListingWithFriendship(Builder $builder): void
    {
        if (isset($this->request->favourites)) {
            $this->request->favourites
            ? $builder->whereHas('favouriteUsersInverse', function (Builder $builder) {
                $builder->where('user_id', Auth::id());
            })
            : $builder->whereDoesntHave('favouriteUsersInverse', function (Builder $builder) {
                $builder->where('user_id', Auth::id());
            }) ;
        }
    }

    protected function filterConnectedUsers(Builder $builder): void
    {
        if ($this->request->connections) {
            $builder->whereHas('connections', function (Builder $builder) {
                $builder->whereHas('users', function (Builder $builder) {
                    $builder->where('user_id', Auth::id());
                })->where('status', Connection::STATUS['accept']);
            })->where('id', '!=', Auth::id());
        }
    }

    protected function filterRequestedConnectionUsers(Builder $builder): void
    {
        if ($this->request->pending_connections) {
            $builder->whereHas('connections', function (Builder $builder) {
                $builder->where('status', Connection::STATUS['pending'])
                    ->whereHas('users', function (Builder $builder) {
                    $builder->where('user_id', Auth::id());
                });
            })->where('id', '!=', Auth::id());
        }
    }

    protected function filterWithoutPendingOrAcceptedConnectedUsers(Builder $builder): void
    {
        if ($this->request->without_connections) {
            $builder->whereDoesntHave('connections', function (Builder $builder) {
                $builder->where('status', Connection::STATUS['pending'])
                    ->orWhere('status', Connection::STATUS['accept'])
                    ->whereHas('users', function (Builder $builder) {
                        $builder->where('user_id', Auth::id());
                    });
            })->where('id', '!=', Auth::id());
        }
    }

    protected function searchByText(Builder $builder): void
    {
        $builder->where('first_name', 'LIKE', "%{$this->request->search}%")
            ->orWhere('last_name', 'LIKE', "%{$this->request->search}%")
            ->orWhere('email', 'LIKE', "%{$this->request->search}%");
    }
}
