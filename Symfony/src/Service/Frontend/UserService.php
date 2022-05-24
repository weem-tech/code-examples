<?php

namespace App\Service\Frontend;

use App\Entity\Shop\Shop;
use App\Entity\User\User;
use App\Entity\Voucher\Voucher;
use App\Service\Core\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class UserService
 * @package App\Service\Frontend
 */
class UserService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var object|string
     */
    private $tokenStorage;


    /**
     * @var MailService
     */
    private $mailService;


    /**
     * UserService constructor.
     * @param EntityManagerInterface $em
     * @param TokenStorageInterface $tokenStorage
     * @param MailService $mailService
     */
    public function __construct(
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage,
        MailService $mailService
    )
    {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->mailService = $mailService;
    }


    /**
     * @param $user
     * @return bool
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function freezeAccount($user)
    {
        if (!$user instanceof User) {
            return false;
        }

        $user->setFrozen(1);
        $this->em->persist($user);
        $this->em->flush();

        $this->em->getRepository(Shop::class)->inactivateShops($user->getId());
        $this->em->getRepository(Voucher::class)->inactivateVouchers($user->getId());

        $this->mailService->sendMail([
            'recipientEmail' => $user->getEmail(),
            'recipientName' => $user->getName(),
            'subject' => 'Subscription has expired',
            'template' => 'frontend/email/subscription_expired.twig',
            'params' => [
                'name' => $user->getName(),
            ]
        ]);

        return true;
    }

}
