<?php

namespace App\Service\Frontend;

use App\Entity\Shop\Shop;
use App\Entity\Subscription\Feature;
use App\Entity\Subscription\FeatureSubscription;
use App\Entity\Subscription\Plan;
use App\Entity\Subscription\Subscription;
use App\Entity\User\User;
use App\Service\Api\AnalyticService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;


/**
 * Class SubscriptionService
 * @package App\Service\Frontend
 */
class SubscriptionService
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
     * @var StripeService
     */
    private $stripeService;

    /**
     * @var AnalyticService
     */
    private $analyticService;

    /**
     * SubscriptionService constructor.
     * @param EntityManagerInterface $em
     * @param TokenStorageInterface $tokenStorage
     * @param StripeService $stripeService
     * @param AnalyticService $analyticService
     */
    public function __construct(
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage,
        StripeService $stripeService,
        AnalyticService $analyticService
    )
    {
        $this->em = $em;
        $this->stripeService = $stripeService;
        $this->tokenStorage = $tokenStorage;
        $this->analyticService = $analyticService;
    }

    /**
     * @param $plan
     * @param null $user
     * @return array
     * @throws Exception
     */
    public function changeSubscription($plan, $user = null)
    {
        if (!$user) {
            $user = $this->tokenStorage->getToken()->getUser();
        }

        if (!$user instanceof User) {
            return [
                'success' => false,
                'message' => 'Invalid user'
            ];
        }

        $currentSubscription = $this->em->getRepository(Subscription::class)->getUserCurrentSubscription($user->getId());

        // downgrade or choose current
        if ($currentSubscription instanceof Subscription && $plan->getLevel() <= $currentSubscription->getPlan()->getLevel()) {
            $user->setSubscriptionPlan($plan);
            $this->em->persist($user);
            $this->em->flush();

            return [
                'success' => true,
            ];
        }

        //upgrade
        $upgrade = $this->upgradeSubscription($user, $plan, $currentSubscription);

        if ($upgrade['success']) {
            $this->reactivateUser($user);
        }

        return $upgrade;
    }

    /**
     * @param $user
     * @param $plan
     * @param null $currentSubscription
     * @return array
     * @throws Exception
     */
    public function upgradeSubscription($user, $plan, $currentSubscription = null)
    {

        $billingAmount = $plan->getPrice();

        if ($currentSubscription instanceof Subscription) {
            $subscriptionDays = date_diff($currentSubscription->getExpiresDate(), $currentSubscription->getStartedDate());
            if ($subscriptionDays->days) {
                $currentPlan = $currentSubscription->getPlan();
                $remainingDays = date_diff($currentSubscription->getExpiresDate(), new DateTime());
                $remainingAmount = round(($currentPlan->getPrice() / $subscriptionDays->days) * $remainingDays->days, 2);
                $billingAmount = $plan->getPrice() - $remainingAmount;
            }

            $currentSubscription->setActive(false);
            $this->em->persist($currentSubscription);
        }

        $stripeCustomer = Customer::retrieve($user->getPayment()->getStripeId());
        $charge = $this->stripeService->generateInvoiceAndPay($stripeCustomer->id, $billingAmount);

        if (!$charge['success']) {
            return [
                'success' => false,
                'message' => $charge['message']
            ];
        }

        $user->setSubscriptionPlan($plan);
        $user->setFreeTrial(false);

        $newSubscription = new Subscription();
        $newSubscription->fromArray([
            'user' => $user,
            'plan' => $plan,
            'active' => true,
            'started_date' => new DateTime(),
            'expires_date' => new DateTime('+' . $plan->getDuration() . ' month'),
        ]);

        $this->em->persist($newSubscription);

        $this->em->persist($user);
        $this->em->flush();

        return [
            'success' => true,
        ];
    }

    /**
     * @param $featureSubscription
     * @return array
     * @throws ApiErrorException
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function upgradeFeatureSubscription($featureSubscription)
    {
        /** @var FeatureSubscription $featureSubscription */
        //deactivate current subscription
        $featureSubscription->setActive(false);
        $this->em->persist($featureSubscription);
        $this->em->flush();

        /** @var User $user */
        $user = $featureSubscription->getUser();
        if (!$user->getPayment()) {
            return [
                'success' => false,
                'message' => 'Please add a payment method.'
            ];
        }

        /** @var Feature $feature */
        $feature = $featureSubscription->getFeature();
        if (!$featureSubscription->getFreeTrial()) {
            $billingAmount = $feature->getPrice();
            $items[] = [
                'billingAmount' => $billingAmount,
                'description' => $feature->getName() . ' feature subscription invoice',
            ];

            if ($feature->getId() == 3) {
                $data = $this->analyticService->getVouchersRevenueSum(
                    $featureSubscription->getShop()->getId(),
                    $featureSubscription->getStartedDate()->format('Y-m-d'),
                    $featureSubscription->getExpiredDate()->format('Y-m-d')
                );

                if ($data['success'] && !empty($data['data']['sumAmount'])) {
                    $revenue = $data['data']['sumAmount'];
                    $revenueAmount = round($revenue * 0.1, 2); // 10% of revenue generated

                    $items[] = [
                        'billingAmount' => $revenueAmount,
                        'description' => '10% of revenue generated by the voucher campaign',
                    ];
                }
            }

            $stripeCustomer = Customer::retrieve($user->getPayment()->getStripeId());
            $description = $feature->getName() . ' feature subscription invoice for ' .
                $featureSubscription->getStartedDate()->format('d/m/y') . ' - ' . $featureSubscription->getExpiredDate()->format('d/m/y');

            $charge = $this->stripeService->generateInvoiceAndPay($stripeCustomer->id, $items, $description);

            if (!$charge['success']) {
                return [
                    'success' => false,
                    'message' => $charge['message']
                ];
            }
        }

        $newSubscription = new FeatureSubscription();
        $newSubscription->fromArray([
            'user' => $user,
            'shop' => $featureSubscription->getShop(),
            'feature' => $feature,
            'active' => true,
            'free_trial' => false,
            'unsubscribe' => false,
            'started_date' => new DateTime('+1 day'),
            'expired_date' => new DateTime('+' . $feature->getDuration() . ' month +1 day'),
        ]);

        $this->em->persist($newSubscription);
        $this->em->flush();

        return [
            'success' => true,
        ];
    }

    /**
     * @param $user
     */
    public function reactivateUser($user)
    {
        $user->setFrozen(false);
        $this->em->getRepository(Shop::class)->activateShops($user->getId());

        $this->em->persist($user);
        $this->em->flush();
    }

    /**
     * @param $user
     * @param $plan
     * @param string $type
     * @return bool
     */
    public function checkPlanAvailability($user, $plan, $type = 'shop')
    {
        if (!$user instanceof User) {
            return false;
        }

        if (!$plan instanceof Plan) {
            return false;
        }

        if ($type == 'shop') {
            if ($user->getShops()->count() <= $plan->getDomain()) {
                return true;
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $user
     * @param $feature
     * @param $shop
     * @return array
     * @throws ApiErrorException
     * @throws Exception
     */
    public function featureSubscription($user, $feature, $shop)
    {
//        $billingAmount = $feature->getPrice();
//        $stripeCustomer = Customer::retrieve($user->getPayment()->getStripeId());
//        $charge = $this->stripeService->generateInvoiceAndPay($stripeCustomer->id, $billingAmount);
//
//        if (!$charge['success']) {
//            return [
//                'success' => false,
//                'message' => $charge['message']
//            ];
//        }

        $featureSubscription = $this->em->getRepository(FeatureSubscription::class)->findOneBy([
            'active' => 1,
            'user' => $user,
            'shop' => $shop,
            'feature' => $feature,
        ]);

        if ($featureSubscription) {
            $featureSubscription->setUnsubscribe(false);
        } else {
            $checkPastFeatureSubscription = $this->em->getRepository(FeatureSubscription::class)->findOneBy([
                'user' => $user,
                'shop' => $shop,
                'feature' => $feature,
            ]);

            $featureSubscription = new FeatureSubscription();
            $featureSubscription->fromArray([
                'user' => $user,
                'shop' => $shop,
                'feature' => $feature,
                'active' => true,
                'free_trial' => $checkPastFeatureSubscription ? false : true,
                'started_date' => new DateTime(),
                'expired_date' => new DateTime('+' . $feature->getDuration() . ' month'),
            ]);
        }

        $this->em->persist($featureSubscription);

        $this->em->persist($user);
        $this->em->flush();

        return [
            'success' => true,
        ];
    }

}
