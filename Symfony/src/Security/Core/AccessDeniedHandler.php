<?php


namespace App\Security\Core;


use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

/**
 * Class AccessDeniedHandler
 * @package App\Security\Core
 */
class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    /**
     * @var object|string
     */
    private $tokenStorage;

    /**
     * @var RouterInterface
     */
    private $router;


    /**
     * AccessDeniedHandler constructor.
     * @param TokenStorageInterface $tokenStorage
     * @param RouterInterface $router
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        RouterInterface $router
    )
    {
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
    }

    /**
     * @param Request $request
     * @param AccessDeniedException $accessDeniedException
     * @return RedirectResponse|Response|null
     */
    public function handle(Request $request, AccessDeniedException $accessDeniedException)
    {
        $user = $this->tokenStorage->getToken()->getUser();

        $redirectUrl = $this->router->generate('frontend_account_index');

        if ($user->hasRole('ROLE_ADMIN')) {
            $redirectUrl = $this->router->generate('admin_index');
        }

        return new RedirectResponse($redirectUrl);
    }
}