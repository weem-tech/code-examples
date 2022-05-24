<?php

namespace App\Security\Frontend;

use App\Entity\User\User;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user)
    {
        if (!$user instanceof User) {
            return;
        }
        if (!$user->getActive()) {
            throw new DisabledException("Your account is not active!");
        }
    }

    public function checkPostAuth(UserInterface $user)
    {
    }
}