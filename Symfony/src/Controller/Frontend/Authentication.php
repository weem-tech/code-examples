<?php

namespace App\Controller\Frontend;

use App\Component\Controller\AbstractController;
use App\Entity\Subscription\Plan;
use App\Entity\Subscription\Subscription;
use App\Entity\User\User;
use App\Form\Type\ForgotPasswordType;
use App\Form\Type\RegistrationFormType;
use App\Form\Type\ResetPasswordType;
use App\Service\Core\MailService;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Authentication extends AbstractController
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
     * @var MailService
     */
    private $mailService;

    /**
     * Authentication constructor.
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

    /**
     * @return array
     */
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            'security.password_encoder' => '?' . UserPasswordEncoderInterface::class,
        ]);
    }

    public function preDispatch()
    {
        if (class_exists(get_parent_class($this)) && method_exists(get_parent_class($this), __FUNCTION__)) {
            call_user_func_array([get_parent_class($this), __FUNCTION__], func_get_args());
        }
    }

    /**
     * @Route("/login", name="login", methods={"GET"})
     *
     * @return Response
     */
    public function loginAction(AuthenticationUtils $utils)
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('frontend_account_index');
        }
        $error = $utils->getLastAuthenticationError();
        $lastUsername = $utils->getLastUsername();
        $this->assign([
            'error' => $error,
            'lastUsername' => $lastUsername,
        ]);
        return $this->render();
    }

    /**
     * @Route("/login/handler", name="login_handler", methods={"POST"})
     */
    public function loginCheckAction()
    {
        throw new RuntimeException('This should never be called directly.');
    }

    /**
     * @Route("/logout", name="logout", methods={"GET"})
     */
    public function logoutAction()
    {
        throw new RuntimeException('This should never be called directly.');
    }

    /**
     * @Route("/register", name="register", methods={"GET"})
     *
     * @return Response
     * @throws LogicException
     */
    public function registerAction()
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('frontend_account_index');
        }
        $user = new User();

        $form = $this->createForm(RegistrationFormType::class, $user, [
            'action' => $this->generateUrl('frontend_register_handler')
        ]);

        $this->assign([
            'form' => $form->createView()
        ]);

        return $this->render();
    }

    /**
     * @Route("/register/handler", name="register_handler", methods={"POST"})
     *
     * @return RedirectResponse|Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function registerHandlerAction()
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('frontend_account_index');
        }
        $user = new User();

        $form = $this->createForm(RegistrationFormType::class, $user, [
            'action' => $this->generateUrl('frontend_register_handler')
        ]);

        $form->handleRequest($this->request);
        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('frontend/authentication/register.twig', [
                'form' => $form->createView(),
            ]);
        }

        $password = $this->get('security.password_encoder')->encodePassword($user, $user->getPlainPassword());
        $user->setPassword($password);

        $user->setActive(false);

        // default subscription plan with free trial
        $defaultPlan = $this->em->getRepository(Plan::class)->find(1);
        $freeTrialSubscription = new Subscription();
        $freeTrialSubscription->fromArray([
            'user' => $user,
            'plan' => $defaultPlan,
            'active' => true,
            'started_date' => new DateTime(),
            'expires_date' => new DateTime('+' . $defaultPlan->getDuration() . ' month'),
        ]);

        $user->setFreeTrial(true);

        $this->em->persist($user);
        $this->em->persist($freeTrialSubscription);
        $this->em->flush();

        $this->mailService->sendMail([
            'recipientEmail' => $user->getEmail(),
            'recipientName' => $user->getName(),
            'subject' => 'Thanks for registering',
            'template' => 'frontend/email/registration.twig',
            'params' => [
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'confirmationToken' => $user->getConfirmationToken()
            ]
        ]);

        $this->session->set('lastRegisteredEmail', $user->getEmail());

        return $this->redirectToRoute('frontend_register_success');
    }

    /**
     * @Route("/register/success", name="register_success", methods={"GET"})
     *
     * @return Response
     */
    public function registerSuccessAction()
    {
        $email = $this->session->get('lastRegisteredEmail');
        if (!$email) {
            return $this->redirectToRoute('frontend_register');
        }
        $this->session->remove('lastRegisteredEmail');
        $this->assign([
            'email' => $email
        ]);

        return $this->render();
    }

    /**
     * @Route("/register/activation", name="register_activation", methods={"GET"})
     *
     * @return Response
     */
    public function registerActivationAction()
    {
        $email = $this->request->get('email');
        $confirmationToken = $this->request->get('token');

        $userRepository = $this->em->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => $email, 'active' => false]);

        if ($user instanceof User && hash_equals($user->getConfirmationToken(), $confirmationToken)) {
            $user->setActive(true);
            $user->setConfirmationToken(null);

            $this->em->persist($user);
            $this->em->flush();
            $this->addFlash('success', 'Your account has been activated and you can sign in.');
            return $this->redirectToRoute('frontend_login');
        }
        $this->addFlash('error', 'There was a problem activating your account!');
        return $this->redirectToRoute('frontend_login');
    }

    /**
     * @Route("/forgot-password", name="forgot_password", methods={"GET"})
     *
     * @return Response
     */
    public function forgotPasswordAction()
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('frontend_account_index');
        }

        $form = $this->createForm(ForgotPasswordType::class, null, [
            'action' => $this->generateUrl('frontend_forgot_password_handler')
        ]);

        $this->assign([
            'form' => $form->createView(),
        ]);

        return $this->render('frontend/authentication/forgot_password.twig');
    }

    /**
     * @Route("/forgot-password/handler", name="forgot_password_handler", methods={"POST"})
     *
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function forgotPasswordHandlerAction()
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('frontend_account_index');
        }

        $form = $this->createForm(ForgotPasswordType::class, null, [
            'action' => $this->generateUrl('frontend_forgot_password_handler')
        ]);

        $form->handleRequest($this->request);
        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('frontend/authentication/forgot_password.twig', [
                'form' => $form->createView(),
            ]);
        }

        $email = $form->get('email')->getData();
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email, 'active' => true, 'confirmationToken' => null]);

        if (!$user instanceof User) {
            $this->addFlash('warning', 'Please check your email address and try again.');
            return $this->redirectToRoute('frontend_forgot_password');

        }

        $confirmationToken = hash('sha256', random_bytes(256) . microtime());
        $user->setConfirmationToken($confirmationToken);
        $requestedAt = new DateTime();
        $requestedAt->add(new DateInterval('PT1H'));
        $user->setPasswordRequestedAt($requestedAt);
        $this->em->persist($user);
        $this->em->flush();

        $this->mailService->sendMail([
            'recipientEmail' => $user->getEmail(),
            'recipientName' => $user->getName(),
            'subject' => 'Password recovery',
            'template' => 'frontend/email/forgot_password.twig',
            'params' => [
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'confirmationToken' => $user->getConfirmationToken()
            ]
        ]);

        $this->addFlash('success', 'Please check your email address for recovering your password.');
        return $this->redirectToRoute('frontend_login');

    }

    /**
     * @Route("/reset-password", name="reset_password", methods={"GET"})
     *
     * @return Response
     */
    public function resetPasswordAction()
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('frontend_account_index');
        }

        $email = $this->request->get('email');
        $confirmationToken = $this->request->get('token');

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email, 'active' => true]);

        if ($user instanceof User
            && $user->getConfirmationToken() && $confirmationToken
            && hash_equals($user->getConfirmationToken(), $confirmationToken)
            && $user->getPasswordRequestedAt() instanceof DateTime
            && $user->getPasswordRequestedAt()->getTimestamp() > time()) {

            $form = $this->createForm(ResetPasswordType::class, null, [
                    'action' => $this->generateUrl('frontend_reset_password_handler', [
                        'email' => $email,
                        'token' => $confirmationToken
                    ])
                ]
            );
            $this->assign([
                'form' => $form->createView()
            ]);
            return $this->render('frontend/authentication/reset_password.twig');
        }

        $this->addFlash('warning', 'There was a problem resetting your password! Please check your email.');
        return $this->redirectToRoute('frontend_login');
    }

    /**
     * @Route("/reset-password/handler", name="reset_password_handler", methods={"POST"})
     *
     * @return RedirectResponse|Response
     * @throws LogicException
     * @throws InvalidArgumentException
     */
    public function resetPasswordHandlerAction()
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('frontend_account_index');
        }

        $email = $this->request->get('email');
        $confirmationToken = $this->request->get('token');

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email, 'active' => true]);

        if ($user instanceof User && hash_equals($user->getConfirmationToken(), $confirmationToken) && $user->getPasswordRequestedAt() instanceof DateTime && $user->getPasswordRequestedAt()->getTimestamp() > time()) {
            $form = $this->createForm(ResetPasswordType::class, null, [
                    'action' => $this->generateUrl('frontend_reset_password_handler', [
                        'email' => $email,
                        'token' => $confirmationToken
                    ])
                ]
            );

            $form->handleRequest($this->request);

            if (!$form->isSubmitted() || !$form->isValid()) {
                return $this->render('frontend/authentication/reset_password.twig', [
                    'form' => $form->createView(),
                ]);
            }

            /** @var UserPasswordEncoderInterface $passwordEncoder */
            $passwordEncoder = $this->get('security.password_encoder');

            $plainPassword = $form->get('plainPassword')->getData();
            $password = $passwordEncoder->encodePassword($user, $plainPassword);
            $user->setPassword($password);
            $user->setConfirmationToken(null);
            $user->setPasswordRequestedAt(null);
            $this->em->persist($user);
            $this->em->flush();

            $this->addFlash('success', 'You have successfully reset your password.');
            return $this->redirectToRoute('frontend_login');
        }

        $this->addFlash('warning', 'There was a problem activating your account!');
        return $this->redirectToRoute('frontend_index_index');
    }

}