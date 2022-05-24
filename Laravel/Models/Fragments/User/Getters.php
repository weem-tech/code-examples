<?php

namespace App\Models\Fragments\User;

trait Getters
{
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getFullNameConnectedAttribute(): string
    {
        return implode('_', explode(' ', $this->getFullNameAttribute()));
    }
}
