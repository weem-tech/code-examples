<?php

namespace App\Controller\Frontend;

use App\Component\Controller\AbstractController;
use App\Entity\User\User;
use App\Form\Type\EditEmailType;
use App\Form\Type\EditPasswordType;
use App\Form\Type\EditPaymentType;
use App\Form\Type\EditPersonalType;
use App\Form\Type\FeatureConfiguration;
use App\Service\Frontend\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/account", name="account_")
 */
class Account extends AbstractController
{
    /**
     * @var SessionInterface
     */
    protected $session;
    /**
     * @var StripeService
     */
    protected $stripeService;
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Account constructor.
     * @param ContainerInterface $container
     * @param EntityManagerInterface $em
     * @param SessionInterface $session
     * @param StripeService $stripeService
     */
    public function __construct(
        ContainerInterface $container,
        EntityManagerInterface $em,
        SessionInterface $session,
        StripeService $stripeService
    )
    {
        if (class_exists(get_parent_class($this)) && method_exists(get_parent_class($this), __FUNCTION__)) {
            call_user_func_array([get_parent_class($this), __FUNCTION__], func_get_args());
        }
        $this->em = $em;
        $this->session = $session;
        $this->stripeService = $stripeService;
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
     * @Route("/", name="index", methods={"GET"})
     *
     * @return Response
     */
    public function indexAction()
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getFrozen()) {
            return $this->redirectToRoute('frontend_account_subscriptions_index');
        }

        return $this->redirectToRoute('frontend_account_shops_index');
    }

    /**
     * @Route("/settings", name="settings", methods={"GET"})
     *
     * @return Response
     */
    public function settingsAction()
    {
        $this->assign([
            'cards' => $this->stripeService->getCustomerSources() //customers all Cards
        ]);

        return $this->render('frontend/account/settings/index.twig');
    }

    /**
     * @Route("/settings/edit-personal", name="settings_edit_personal", methods={"GET"})
     *
     * @return Response
     */
    public function editPersonalAction()
    {
        $user = $this->getUser();
        $form = $this->createForm(EditPersonalType::class, $user, [
            'action' => $this->generateUrl('frontend_account_settings_edit_personal_handler')
        ]);
        $this->assign([
            'form' => $form->createView()
        ]);
        return $this->render('frontend/account/settings/edit_personal.twig');
    }

    /**
     * @Route("/settings/edit-personal-handler", name="settings_edit_personal_handler", methods={"POST"})
     *
     * @return RedirectResponse|Response
     * @throws LogicException
     * @throws InvalidArgumentException
     */
    public function editPersonalHandlerAction()
    {
        $user = $this->getUser();

        $form = $this->createForm(EditPersonalType::class, $user, [
            'action' => $this->generateUrl('frontend_account_settings_edit_personal_handler')
        ]);

        $form->handleRequest($this->request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('frontend/account/settings/edit_personal.twig', [
                'form' => $form->createView(),
            ]);
        }

        $this->em->persist($user);
        $this->em->flush();

        $this->addFlash('success', 'You have successfully updated your billing address.');
        return $this->redirectToRoute('frontend_account_settings');
    }

    /**
     * @Route("/settings/edit-email", name="settings_edit_email", methods={"GET"})
     *
     * @return Response
     */
    public function editEmailAction()
    {
        $user = $this->getUser();
        $form = $this->createForm(EditEmailType::class, [], [
            'action' => $this->generateUrl('frontend_account_settings_edit_email_handler')
        ]);
        $this->assign([
            'form' => $form->createView()
        ]);
        return $this->render('frontend/account/settings/edit_email.twig');
    }

    /**
     * @Route("/settings/edit-email-handler", name="settings_edit_email_handler", methods={"POST"})
     *
     * @return RedirectResponse|Response
     * @throws LogicException
     * @throws InvalidArgumentException
     */
    public function editEmailHandlerAction()
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(EditEmailType::class, [], [
            'action' => $this->generateUrl('frontend_account_settings_edit_email_handler')
        ]);

        $form->handleRequest($this->request);

        /** @var UserPasswordEncoderInterface $passwordEncoder */
        $passwordEncoder = $this->get('security.password_encoder');
        $plainPasswordField = $form->get('plainPassword');
        $plainPassword = $plainPasswordField->getData();
        if (!$passwordEncoder->isPasswordValid($user, $plainPassword)) {
            $plainPasswordField->addError(new FormError('Password is incorrect.'));
        }

        $existEmailField = $form->get('exist_email');

        if ($existEmailField->getData() !== $user->getEmail()) {
            $existEmailField->addError(new FormError('Existing email is incorrect.'));
        }

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('frontend/account/settings/edit_email.twig', [
                'form' => $form->createView(),
            ]);
        }

        $user->setEmail($form->get('new_email')->getData());
        $this->em->persist($user);
        $this->em->flush();

        $this->addFlash('success', 'You have successfully updated your email address.');
        return $this->redirectToRoute('frontend_account_settings');
    }

    /**
     * @Route("/settings/edit-password", name="settings_edit_password", methods={"GET"})
     *
     * @return Response
     */
    public function editPasswordAction()
    {
        $form = $this->createForm(EditPasswordType::class, null, [
            'action' => $this->generateUrl('frontend_account_settings_edit_password_handler')
        ]);
        $this->assign([
            'form' => $form->createView()
        ]);
        return $this->render('frontend/account/settings/edit_password.twig');
    }

    /**
     * @Route("/settings/edit-password-handler", name="settings_edit_password_handler", methods={"POST"})
     *
     * @return RedirectResponse|Response
     * @throws LogicException
     * @throws InvalidArgumentException
     */
    public function editPasswordHandlerAction()
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(EditPasswordType::class, null, [
            'action' => $this->generateUrl('frontend_account_settings_edit_password_handler')
        ]);

        $form->handleRequest($this->request);

        /** @var UserPasswordEncoderInterface $passwordEncoder */
        $passwordEncoder = $this->get('security.password_encoder');
        $oldPlainPasswordField = $form->get('oldPlainPassword');
        $oldPlainPassword = $oldPlainPasswordField->getData();
        if (!$passwordEncoder->isPasswordValid($user, $oldPlainPassword)) {
            $oldPlainPasswordField->addError(new FormError('Password is incorrect.'));
        }

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('frontend/account/settings/edit_password.twig', [
                'form' => $form->createView(),
            ]);
        }
        $plainPassword = $form->get('plainPassword')->getData();
        $password = $passwordEncoder->encodePassword($user, $plainPassword);
        $user->setPassword($password);
        $this->em->persist($user);
        $this->em->flush();

        $this->addFlash('success', 'You have successfully updated your password.');
        return $this->redirectToRoute('frontend_account_settings');
    }

    /**
     * @Route("/settings/add-payment", name="settings_add_payment", methods={"GET"})
     *
     * @return Response
     */
    public function addPaymentAction()
    {
        return $this->render('frontend/account/settings/add_payment.twig');
    }

    /**
     * @Route("/settings/add-payment-handler", name="settings_add_payment_handler", methods={"POST"})
     *
     * @return RedirectResponse|Response
     * @throws LogicException
     * @throws InvalidArgumentException
     */
    public function addPaymentHandlerAction()
    {
        /** @var User $user */
        $user = $this->getUser();
        $userPayment = $user->getPayment();

        if ($userPayment) {
            $addPayment = $this->stripeService->addCustomerSource($this->request->get('stripeToken'));
        } else {
            $addPayment = $this->stripeService->createCustomer($this->request->get('stripeToken'));
        }

        if ($addPayment['success']) {
            $this->addFlash('success', 'You have successfully added your payment card.');
        } else {
            $this->addFlash('error', $addPayment['message']);
        }
        return $this->redirectToRoute('frontend_account_settings');
    }

    /**
     * @Route("/settings/edit-payment/{id}", name="settings_edit_payment", methods={"GET"})
     *
     * @return Response
     */
    public function editPaymentAction($id)
    {
        $sourceData = $this->stripeService->getSource($id);

        if ($sourceData['success']) {
            $form = $this->createForm(EditPaymentType::class, $sourceData['sourceData'], [
                'action' => $this->generateUrl('frontend_account_settings_edit_payment_handler', [
                    'id' => $id,
                ])
            ]);
            $this->assign([
                'form' => $form->createView(),
                'sourceData' => $sourceData['sourceData']
            ]);
        } else {
            $this->addFlash('error', $sourceData['message']);
        }


        return $this->render('frontend/account/settings/edit_payment.twig');
    }

    /**
     * @Route("/settings/edit-payment-handler/{id}", name="settings_edit_payment_handler", methods={"POST"})
     *
     * @return RedirectResponse|Response
     * @throws LogicException
     * @throws InvalidArgumentException
     */
    public function editPaymentHandlerAction($id)
    {

        $sourceData = $this->stripeService->getSource($id);

        if ($sourceData['success']) {
            $form = $this->createForm(EditPaymentType::class, $sourceData['sourceData'], [
                'action' => $this->generateUrl('frontend_account_settings_edit_payment_handler', [
                    'id' => $id,
                ])
            ]);

            if (!$form->isSubmitted() || !$form->isValid()) {
                $form->handleRequest($this->request);
                $requestData = $form->getData();

                $updateSource = $this->stripeService->updateCustomerSource($sourceData['sourceData']['id'], $requestData);


                if ($updateSource['success']) {
                    if ($requestData['default']) {
                        $this->stripeService->changeCustomerDefaultSource($sourceData['sourceData']['id']);
                    }
                    $this->addFlash('success', 'You have successfully updated your payment card.');

                } else {
                    $this->addFlash('error', $updateSource['message']);
                }
            }
        } else {
            $this->addFlash('error', $sourceData['message']);
        }

        return $this->redirectToRoute('frontend_account_settings');
    }

    /**
     * @Route("/settings/delete-payment/{id}", name="settings_delete_payment", methods={"GET"})
     *
     * @return Response
     */
    public function deletePaymentAction($id)
    {
        $deleteSource = $this->stripeService->deleteCustomerSource($id);
        if ($deleteSource['success']) {
            $this->addFlash('success', 'You have successfully deleted your payment card.');
        } else {
            $this->addFlash('error', $deleteSource['message']);
        }
        return $this->redirectToRoute('frontend_account_settings');
    }
}