<?php

namespace App\Security\Api\SimplePreauth;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;

class Authenticator implements SimplePreAuthenticatorInterface
{
    public function createToken(Request $request, $providerKey)
    {
        // look for an apiKey query parameter
        // or if you want to use an "apiKey" header, then do something like this:
        // $apiKey = $request->headers->get('apiKey');
        $apiKey = urldecode($request->get('apiKey'));
        $url = $this->getHost($request->server->get('HTTP_REFERER'));
        $params = explode('::', $request->attributes->get('_controller'));
        $actionName = substr($params[1], 0, -6);

        if (!$apiKey || !$url) {
            throw new BadCredentialsException();
            // or to just skip api key authentication
            // return null;
        }

        $credentials = [
            'apiKey' => $apiKey,
            'url' => $url,
            'action' => $actionName
        ];

        return new PreAuthenticatedToken(
            'anon.',
            $credentials,
            $providerKey
        );
    }

    protected function getHost($url)
    {
        $parseUrl = parse_url(trim($url));
        $host = trim($parseUrl['host'] ? $parseUrl['host'] : array_shift(explode('/', $parseUrl['path'], 2)));
        if ($this->isValidDomain($host)) {
            return $host;
        }
        return false;
    }

    protected function isValidDomain($domain)
    {
        if (filter_var($domain, FILTER_VALIDATE_DOMAIN)) {
            return true;
        }
        return false;
    }

    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        if (!$userProvider instanceof Provider) {
            throw new InvalidArgumentException(
                sprintf(
                    'The user provider must be an instance of Provider (%s was given).',
                    get_class($userProvider)
                )
            );
        }

        $credentials = $token->getCredentials();
        $apiKey = $credentials['apiKey'];
        $url = $credentials['url'];
        $action = $credentials['action'];
        $shop = $userProvider->getShopForApiKey($apiKey, $url);

        if (!$shop) {
            // CAUTION: this message will be returned to the client
            // (so don't put any un-trusted messages / error strings here)
            throw new CustomUserMessageAuthenticationException(
                sprintf('API Key "%s" does not exist.', $apiKey)
            );
        }

        if (!$shop->getActive() && !in_array($action, ['shopCheckKey', 'shopActivate'])) {
            throw new CustomUserMessageAuthenticationException(
                sprintf('API Key "%s" does not exist.', $apiKey)
            );
        }

        $username = $shop->getUrl();
        $user = $userProvider->loadUserByUsername($username);

        return new PreAuthenticatedToken(
            $user,
            $credentials,
            $providerKey,
            $user->getRoles()
        );
    }
}