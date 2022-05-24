<?php

namespace App\Controller\Frontend;

use App\Component\Controller\AbstractController;
use App\Entity\User\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/", name="index_")
 */
class Index extends AbstractController
{

    /**
     * Index constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(
        ContainerInterface $container
    )
    {
        if (class_exists(get_parent_class($this)) && method_exists(get_parent_class($this), __FUNCTION__)) {
            call_user_func_array([get_parent_class($this), __FUNCTION__], func_get_args());
        }
    }

    public function preDispatch()
    {
        if (class_exists(get_parent_class($this)) && method_exists(get_parent_class($this), __FUNCTION__)) {
            call_user_func_array([get_parent_class($this), __FUNCTION__], func_get_args());
        }
    }

    /**
     * @Route("/", name="index", methods={"GET"})
     *
     * @return Response
     */
    public function indexAction()
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->hasRole('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_index');
        }

        return $this->redirectToRoute('frontend_account_shops_index');
    }

    /**
     * @Route("/integrations", name="integrations", methods={"GET"})
     *
     * @return Response
     */
    public function integrationsAction()
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->hasRole('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_index');
        }

        return $this->render('frontend/integrations/index.twig');
    }

    /**
     * @Route("/cookies", name="cookies", methods={"GET"})
     *
     * @return Response
     */
    public function cookiesAction()
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->hasRole('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_index');
        }

        return $this->render('frontend/cookies/index.twig');
    }

}