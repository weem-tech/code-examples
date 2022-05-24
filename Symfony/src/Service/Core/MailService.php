<?php


namespace App\Service\Core;


use Doctrine\ORM\EntityManagerInterface;
use Swift_Mailer;
use Swift_Message;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class MailService
 * @package App\Service\Core
 */
class MailService
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
     * @var Swift_Mailer
     */
    private $mailer;

    /**
     * @var Environment
     */
    private $templating;

    /**
     * @var ParameterBagInterface
     */
    private $params;


    /**
     * MailService constructor.
     * @param EntityManagerInterface $em
     * @param TokenStorageInterface $tokenStorage
     * @param Swift_Mailer $mailer
     * @param Environment $templating
     * @param ParameterBagInterface $params
     */
    public function __construct(
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage,
        Swift_Mailer $mailer,
        Environment $templating,
        ParameterBagInterface $params
    )
    {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->params = $params;
    }

    /**
     * @param $data
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function sendMail($data)
    {
        $subject = $data['subject'];
        $senderEmail = !empty($data['senderEmail']) ?: $this->params->get('base_info.email');
        $senderName = !empty($data['senderName']) ?: $this->params->get('base_info.name');
        $recipientEmail = $data['recipientEmail'];
        $recipientName = $data['recipientName'];
        $template = $data['template'];
        $params = $data['params'];

        $message = (new Swift_Message($subject))
            ->setFrom($senderEmail, $senderName)
            ->setTo($recipientEmail, $recipientName)
            ->setBody(
                $this->templating->render($template, $params),
                'text/html'
            );
        $this->mailer->send($message);
    }

}