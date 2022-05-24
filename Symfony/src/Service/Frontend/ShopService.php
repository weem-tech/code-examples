<?php

namespace App\Service\Frontend;

use App\Entity\Shop\Shop;
use App\Entity\Subscription\Plan;
use App\Entity\Subscription\Subscription;
use App\Entity\User\User;
use App\Entity\Voucher\Voucher;
use App\Entity\Voucher\VoucherCode;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class ShopService
 * @package App\Service\Frontend
 */
class ShopService
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
     * @var ParameterBagInterface
     */
    private $params;

    /**
     * ShopService constructor.
     * @param EntityManagerInterface $em
     * @param TokenStorageInterface $tokenStorage
     * @param ParameterBagInterface $params
     */
    public function __construct(
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage,
        ParameterBagInterface $params
    )
    {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->params = $params;
    }


    /**
     * @param $user
     * @param null $plan
     * @param string $type
     * @return bool
     */
    public function checkNewFeaturesAvailable($user, $plan = null, $type = 'shop')
    {
        if (!$user instanceof User) {
            return false;
        }

        if (!$plan) {
            $currentSubscription = $this->em->getRepository(Subscription::class)->getUserCurrentSubscription($user->getId());
            $plan = $currentSubscription->getPlan();
        }

        if (!$plan instanceof Plan) {
            return false;
        }

        if ($type == 'shop') {
            if ($user->getShops()->count() < $plan->getDomain()) {
                return true;
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Voucher $voucher
     * @return array
     */
    public function findShopsForAssignByVoucher(Voucher $voucher)
    {
        $shopIds = $voucher->getAssignShops()->map(function ($obj) {
            return $obj->getId();
        })->getValues();
        $shopIds[] = $voucher->getSHopId();

        return $this->em->getRepository(Shop::class)->findShopsNotInList(implode(',', $shopIds));
    }

    /**
     * @param $shop
     * @param $voucherId
     * @param $offset
     * @param $limit
     * @param string $type
     * @return array|mixed
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getVoucherCodesByVoucherId($shop, $voucherId, $offset, $limit, $type = 'all')
    {
        $env = $this->params->get('kernel.environment');
        $protocol = $env == 'dev' ? 'http://' : 'https://';

        $params['apiKey'] = $shop->getApiKey();
        $params['voucherId'] = $voucherId;
        $params['offset'] = $offset;
        $params['limit'] = $limit;
        $params['type'] = $type;
        $domain = $shop->getUrl();
        $voucher_action = '/frontend/getVoucherCodesByVoucherId';

        $url = $protocol . $domain . $voucher_action . '?' . http_build_query($params);

        return $this->requestData('GET', $url);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $data
     * @return array|mixed
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function requestData($method = "POST", $url = '', $data = [])
    {
        $httpClient = HttpClient::create();
        try {
            $response = $httpClient->request($method, $url, [
                'body' => json_encode($data),
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode == 200) {
                $content = json_decode($response->getContent(false), true);
            }
        } catch (Exception $exception) {
            $content = [
                'success' => false,
                'error' => $exception->getMessage()
            ];
        }

        return $content;
    }

    /**
     * @param $shop
     * @return array|mixed
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getShopIndividualVouchers($shop)
    {
        $env = $this->params->get('kernel.environment');
        $protocol = $env == 'dev' ? 'http://' : 'https://';

        $params['apiKey'] = $shop->getApiKey();
        $domain = $shop->getUrl();
        $voucher_action = '/frontend/getIndividualVouchers';

        $url = $protocol . $domain . $voucher_action . '?' . http_build_query($params);

        return $this->requestData('GET', $url);
    }

    /**
     * @param $voucher
     * @return array
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function checkVoucherCodes($voucher)
    {
        $getShopAvailableCodes = $this->getShopVouchers($voucher->getShop(), 'available');
        $shopCodes = [];
        if ($getShopAvailableCodes['success']) {
            $shopCodes = $getShopAvailableCodes['data'];
        }
        $voucherCodes = array_unique($voucher->getCodes());
        $usedCodesData = $this->em->getRepository(VoucherCode::class)->getShownVoucherCodes($voucher->getId());
        $usedCodes = array_column($usedCodesData, 'code');
        $newCodes = array_diff($voucherCodes, $usedCodes);
        $invalidCodes = array_diff($newCodes, $shopCodes);

        $checkedCodes = [];
        foreach ($voucherCodes as $code) {
            if (in_array($code, $usedCodes)) {
                $checkedCodes[$code] = 'used';
            } elseif (in_array($code, $invalidCodes)) {
                $checkedCodes[$code] = 'invalid';
            } else {
                $checkedCodes[$code] = 'valid';
            }
        }

        return [
            'valid' => empty($invalidCodes),
            'checkedCodes' => $checkedCodes,
            'voucherCodes' => $voucherCodes
        ];
    }

    /**
     * @param $shop
     * @param string $type
     * @return array|mixed
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getShopVouchers($shop, $type = 'all')
    {
        $env = $this->params->get('kernel.environment');
        $protocol = $env == 'dev' ? 'http://' : 'https://';

        $params['apiKey'] = $shop->getApiKey();
        $params['type'] = $type;
        $domain = $shop->getUrl();
        $voucher_action = '/frontend/getVouchers';

        $url = $protocol . $domain . $voucher_action . '?' . http_build_query($params);

        return $this->requestData('GET', $url);
    }

    /**
     * @param $user
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function updateUsedVoucherCodes($user)
    {
        $activeShops = $this->em->getRepository(Shop::class)->activeShopsWithVouchers($user->getId());
        foreach ($activeShops as $activeShop) {
            $usedCodesData = $this->getShopVouchers($activeShop, $type = 'used');
            if ($usedCodesData['success'] && $usedCodesData['data']) {
                $usedCodes = $this->em->getRepository(VoucherCode::class)->getVoucherCodesInList($activeShop->getId(), $usedCodesData['data']);
                if ($usedCodes) {
                    foreach ($usedCodes as $usedCode) {
                        $usedCode->setUsed(true);
                        $this->em->persist($usedCode);
                    }
                }
            }
        }

        $this->em->flush();
    }

    /**
     * @param $shop
     * @param $articleID
     * @return array|mixed
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function checkInStock($shop, $articleID)
    {
        $env = $this->params->get('kernel.environment');
        $protocol = $env == 'dev' ? 'http://' : 'https://';

        $params['apiKey'] = $shop->getApiKey();
        $params['articleID'] = $articleID;
        $domain = $shop->getUrl();
        $voucher_action = '/frontend/checkInStock';

        $url = $protocol . $domain . $voucher_action . '?' . http_build_query($params);

        return $this->requestData('GET', $url);
    }


    /**
     * @param $shop
     * @param $items
     * @param int $count
     * @return array|mixed
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function checkInStockItems($shop, $items, $count = 1)
    {
        $result = [];
        $env = $this->params->get('kernel.environment');
        $protocol = $env == 'dev' ? 'http://' : 'https://';

        $params['apiKey'] = $shop->getApiKey();
        $domain = $shop->getUrl();
        $voucher_action = '/frontend/checkInStock';
        $url = $protocol . $domain . $voucher_action;

        foreach ($items as $ur_item) {
            $params['articleID'] = $ur_item['item'];
            $url .= '?' . http_build_query($params);

            $productInStock = $this->requestData('GET', $url);
            if ($productInStock && !$productInStock['inStock']) {
                continue;
            } else {
                $result[] = $productInStock['product'];
                if (count($result) == $count) {
                    break;
                }
            }
        }

        return $result;
    }
}
