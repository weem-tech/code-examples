<?php

namespace App\Security\Api\SimplePreauth;

use App\Entity\Shop\Shop;
use App\Service\Api\ShopService;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class Provider implements UserProviderInterface
{

    /**
     * @var ShopService
     */
    protected $shopService;

    /**
     * Index constructor.
     *
     * @param ShopService $shopService
     */
    public function __construct(
        ShopService $shopService
    )
    {
        $this->shopService = $shopService;
    }

    public function getShopForApiKey($apiKey, $url)
    {
        $shop = $this->shopService->validateShop($url, $apiKey);

        if (!$shop instanceof Shop) {
            return false;
        }

        return $shop;
    }

    public function loadUserByUsername($username)
    {
        return new User(
            $username,
            null,
            // the roles for the user - you may choose to determine
            // these dynamically somehow based on the user
            ['ROLE_API']
        );
    }

    public function refreshUser(UserInterface $user)
    {
        // this is used for storing authentication in the session
        // but in this example, the token is sent in each request,
        // so authentication can be stateless. Throwing this exception
        // is proper to make things stateless
        throw new UnsupportedUserException();
    }

    public function supportsClass($class)
    {
        return User::class === $class;
    }
}