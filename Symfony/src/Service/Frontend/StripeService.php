<?php

namespace App\Service\Frontend;

use App\Entity\Payment\Payment;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Invoice;
use Stripe\InvoiceItem;
use Stripe\Source;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class StripeService
 * @package App\Service\Frontend
 */
class StripeService
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
     * StripeService constructor.
     * @param EntityManagerInterface $em
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage
    )
    {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return array
     * @throws ApiErrorException
     */
    public function getCustomerSources()
    {
        $cards = [];
        $user = $this->tokenStorage->getToken()->getUser();
        $userPayment = $user->getPayment();

        if ($userPayment) {
            $stripeCustomer = Customer::retrieve($userPayment->getStripeId());

            /** @var Source $stripeCards */
            $stripeCards = $stripeCustomer->allSources($stripeCustomer->id);

            foreach ($stripeCards->data as $stripeCard) {
                $cards[] = [
                    'id' => $stripeCard->id,
                    'brand' => strtolower(str_replace(' ', '', $stripeCard->brand)),
                    'last4' => $stripeCard->last4,
                    'exp_month' => str_pad($stripeCard->exp_month, 2, "0", STR_PAD_LEFT),
                    'exp_year' => substr($stripeCard->exp_year, -2),
                    'default' => $stripeCard->id == $stripeCustomer->default_source ? true : false
                ];
            }
        }

        return $cards;
    }


    /**
     * @param $stripeToken
     * @return array
     */
    public function createCustomer($stripeToken)
    {
        try {
            $user = $this->tokenStorage->getToken()->getUser();

            $customer = Customer::create([
                'email' => $user->getEmail(),
                'source' => $stripeToken,
            ]);

            $payment = new Payment();
            $payment->fromArray([
                'user' => $user,
                'stripe_id' => $customer->id
            ]);

            $this->em->persist($payment);
            $this->em->flush();

            return [
                'success' => true
            ];

        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage()
            ];
        }
    }


    /**
     * @param $stripeToken
     * @return array
     */
    public function addCustomerSource($stripeToken)
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $userPayment = $user->getPayment();

        if ($userPayment) {
            try {
                Customer::createSource(
                    $userPayment->getStripeId(),
                    [
                        'source' => $stripeToken,
                    ]
                );

                return [
                    'success' => true
                ];

            } catch (Exception $exception) {
                return [
                    'success' => false,
                    'message' => $exception->getMessage()
                ];
            }
        }
    }

    /**
     * @param $sourceId
     * @param $sourceData
     * @return array
     */
    public function updateCustomerSource($sourceId, $sourceData)
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $userPayment = $user->getPayment();

        if ($userPayment) {
            try {
                Customer::updateSource(
                    $userPayment->getStripeId(),
                    $sourceId,
                    [
                        'exp_month' => $sourceData['exp_month'],
                        'exp_year' => $sourceData['exp_year'],
                    ]
                );

                return [
                    'success' => true
                ];

            } catch (Exception $exception) {
                return [
                    'success' => false,
                    'message' => $exception->getMessage()
                ];
            }
        }
    }

    /**
     * @param $sourceId
     * @return array
     */
    public function getSource($sourceId)
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $userPayment = $user->getPayment();

        if ($userPayment) {
            $stripeCustomer = Customer::retrieve($userPayment->getStripeId());

            try {
                $card = Customer::retrieveSource(
                    $userPayment->getStripeId(),
                    $sourceId
                );
            } catch (Exception $exception) {
                return [
                    'success' => false,
                    'message' => $exception->getMessage()
                ];
            }
            $sourceData = [
                'id' => $card->id,
                'brand' => strtolower(str_replace(' ', '', $card->brand)),
                'last4' => $card->last4,
                'exp_month' => str_pad($card->exp_month, 2, "0", STR_PAD_LEFT),
                'exp_year' => $card->exp_year,
                'default' => $card->id == $stripeCustomer->default_source ? true : false
            ];

            return [
                'success' => true,
                'sourceData' => $sourceData
            ];
        }
    }

    /**
     * @param $sourceId
     * @return array
     */
    public function deleteCustomerSource($sourceId)
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $userPayment = $user->getPayment();

        if ($userPayment) {
            try {
                Customer::deleteSource(
                    $userPayment->getStripeId(),
                    $sourceId
                );

                return [
                    'success' => true
                ];
            } catch (Exception $exception) {
                return [
                    'success' => false,
                    'message' => $exception->getMessage()
                ];
            }
        }
    }

    /**
     * @param $sourceId
     */
    public function changeCustomerDefaultSource($sourceId)
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $userPayment = $user->getPayment();

        if ($userPayment) {
            $stripeCustomer = Customer::retrieve($userPayment->getStripeId());
            $stripeCustomer->default_source = $sourceId;
            $stripeCustomer->save();
        }
    }


    /**
     * @param $stripeCustomer
     * @param $items
     * @param string $description
     * @return array
     */
    public function generateInvoiceAndPay($stripeCustomer, $items, $description = 'Subscription Invoice')
    {
        try {
            // create invoice items
            foreach ($items as $item) {
                InvoiceItem::create(
                    [
                        "customer" => $stripeCustomer,
                        "amount" => $item['billingAmount'] * 100,
                        "currency" => "EUR",
                        "description" => $item['description']
                    ]
                );
            }

            // pulls in invoice items and pay
            $invoice = Invoice::create(
                [
                    "customer" => $stripeCustomer,
                    "billing" => "charge_automatically",
                    "description" => $description
                ]
            );
            $invoice->pay();

            return [
                'success' => true
            ];

        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage()
            ];
        }

    }
}
