<?php

namespace App\Controller\Frontend;

use App\Component\Controller\AbstractController;
use App\Form\Type\ContactUsType;
use App\Service\Core\MailService;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * @Route("/contact-us", name="contactUs_")
 */
class ContactUs extends AbstractController
{
    /**
     * @var SessionInterface
     */
    protected $session;
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * ContactUs constructor.
     * @param ContainerInterface $container
     * @param EntityManagerInterface $em
     * @param SessionInterface $session
     * @param MailService $mailService
     */
    public function __construct(
        ContainerInterface $container,
        EntityManagerInterface $em,
        SessionInterface $session,
        MailService $mailService
    )
    {
        if (class_exists(get_parent_class($this)) && method_exists(get_parent_class($this), __FUNCTION__)) {
            call_user_func_array([get_parent_class($this), __FUNCTION__], func_get_args());
        }
        $this->em = $em;
        $this->session = $session;
        $this->mailService = $mailService;
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
     * @throws LogicException
     */
    public function indexAction()
    {
        /** @var Form $form */
        $form = $this->createForm(ContactUsType::class, null, [
            'action' => $this->generateUrl('frontend_contactUs_handler')
        ]);

        $this->assign([
            'form' => $form->createView()
        ]);

        return $this->render();
    }

    /**
     * @Route("/handler", name="handler", methods={"POST"})
     *
     * @return RedirectResponse|Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function handlerAction()
    {
        /** @var Form $form */
        $form = $this->createForm(ContactUsType::class, null, [
            'action' => $this->generateUrl('frontend_contactUs_handler')
        ]);

        $form->handleRequest($this->request);
        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('frontend/authentication/register.twig', [
                'form' => $form->createView(),
            ]);
        }

        $this->mailService->sendMail([
            'recipientEmail' => $this->getParameter('base_info.email'),
            'recipientName' => $this->getParameter('base_info.name'),
            'subject' => 'Contact-us request',
            'template' => 'frontend/email/contact_us.twig',
            'params' => $form->getData(),
        ]);

        $this->addFlash(
            'success',
            'Contact request has been sent!'
        );

        return $this->redirectToRoute('frontend_contactUs_index');
    }

}