<?php

namespace App\Service\Api;

use App\Entity\Analytics\Analytics;
use DateInterval;
use DatePeriod;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class AnalyticService
 * @package App\Service\Api
 */
class AnalyticService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var ParameterBagInterface
     */
    private $params;

    /**
     * AnalyticService constructor.
     * @param EntityManagerInterface $em
     * @param ParameterBagInterface $params
     */
    public function __construct(
        EntityManagerInterface $em,
        ParameterBagInterface $params
    )
    {
        $this->em = $em;
        $this->params = $params;
    }

    /**
     * @param array $data
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function setData($data = [])
    {
        return $this->requestData('POST', 'analytics/track-data/', $data);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $data
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function requestData($method = "POST", $url = '', $data = [])
    {
        $api_url = $this->params->get('api_base_url');
        $httpClient = HttpClient::create();
        try {
            $response = $httpClient->request($method, $api_url . $url, [
                'headers' => [
                    'content-type' => 'application/json',
                ],
                'body' => json_encode($data),
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode == 200) {
                $content = [
                    'success' => true,
                    'data' => $response->toArray()
                ];
            } else {
                $content = [
                    'success' => false,
                    'error' => json_decode($response->getContent(false), true)
                ];
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
     * @param $shopId
     * @param $type
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getAnalyticDataByType($shopId, $type)
    {
        $data = [
            'shop_id' => $shopId,
            'type' => Analytics::TYPE[$type],
        ];

        return $this->requestData('POST', 'analytics/get-data-by-type/', $data);
    }

    /**
     * @param $master
     * @param $slave
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function mergeCustomers($master, $slave)
    {
        $data = [
            'master_id' => $master->getId(),
            'slave_id' => $slave->getId()
        ];

        return $this->requestData('POST', 'analytics/merge-customers/', $data);
    }

    /**
     * @param $shopId
     * @param $customerId
     * @param $sessionId
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getSessionLastVoucher($shopId, $customerId, $sessionId)
    {
        $data = [
            'shop_id' => $shopId,
            'customer_id' => $customerId,
            'type' => Analytics::TYPE['voucher'],
            'session_id' => $sessionId
        ];

        return $this->requestData('POST', 'analytics/get-session-last-voucher/', $data);
    }

    /**
     * @param $shopId
     * @param $customerId
     * @param $type
     * @param $sessionId
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getLastActionByType($shopId, $customerId, $type, $sessionId = null)
    {
        $data = [
            'shop_id' => $shopId,
            'customer_id' => $customerId,
            'type' => $type
        ];

        return $this->requestData('POST', 'analytics/get-last-action-by-type/', $data);
    }

    /**
     * @param $shopId
     * @param $customerId
     * @param $sessionId
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getAnalyticsLastAddedToCartProduct($shopId, $customerId, $sessionId)
    {
        $data = [
            'shop_id' => $shopId,
            'customer_id' => $customerId,
            'type' => Analytics::TYPE['cart_product_add'],
            'alt_type' => Analytics::TYPE['cart_product_update'],
            'session_id' => $sessionId
        ];

        return $this->requestData('POST', 'analytics/get-last-action-by-type/', $data);
    }

    /**
     * @param $shopId
     * @param $customerId
     * @param $sessionId
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getAnalyticsLastViewedProduct($shopId, $customerId, $sessionId)
    {
        $data = [
            'shop_id' => $shopId,
            'customer_id' => $customerId,
            'type' => Analytics::TYPE['product'],
            'session_id' => $sessionId
        ];

        return $this->requestData('POST', 'analytics/get-last-action-by-type/', $data);
    }

    /**
     * @param $shopId
     * @param $customerId
     * @param $sessionId
     * @param null $excludeID
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getAnalyticsLastSessionPopular($shopId, $customerId, $sessionId, $excludeID = null)
    {
        $data = [
            'shop_id' => $shopId,
            'exclude_id' => $excludeID,
            'customer_id' => $customerId,
            'type' => Analytics::TYPE['product'],
            'session_id' => $sessionId
        ];

        return $this->requestData('POST', 'analytics/get-last-session-popular/', $data);
    }

    /**
     * @param $shopId
     * @param null $start_date
     * @param null $end_date
     * @param string $group_type
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getAutocompleteRegisteredUsersCount($shopId, $start_date = null, $end_date = null, $group_type = 'month')
    {
        $data = [
            'shop_id' => $shopId,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'group_type' => $group_type
        ];

        return $this->requestData('POST', 'analytics/autocomplete-registered-users-count/', $data);
    }

    /**
     * @param $shopId
     * @param null $start_date
     * @param null $end_date
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getAutocompleteRegisteredUsersTimeSpent($shopId, $start_date = null, $end_date = null)
    {
        $data = [
            'shop_id' => $shopId,
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        return $this->requestData('POST', 'analytics/autocomplete-registered-users-time-spent/', $data);
    }

    /**
     * @param $shopId
     * @param null $start_date
     * @param null $end_date
     * @param string $group_type
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getBasketPopUpShowVsClick($shopId, $start_date = null, $end_date = null, $group_type = 'month')
    {
        $data = [
            'shop_id' => $shopId,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'group_type' => $group_type
        ];

        return $this->requestData('POST', 'analytics/basket-pop-up-show-vs-click/', $data);
    }

    /**
     * @param $shopId
     * @param null $start_date
     * @param null $end_date
     * @param string $group_type
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getBasketPopUpClickVsPurchases($shopId, $start_date = null, $end_date = null, $group_type = 'month')
    {
        $data = [
            'shop_id' => $shopId,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'group_type' => $group_type
        ];

        return $this->requestData('POST', 'analytics/basket-pop-up-click-vs-purchase/', $data);
    }

    /**
     * @param $shopId
     * @param null $start_date
     * @param null $end_date
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getBasketPopPurchaseItems($shopId, $start_date = null, $end_date = null)
    {
        $data = [
            'shop_id' => $shopId,
            'start_date' => $start_date,
            'end_date' => $end_date
        ];

        return $this->requestData('POST', 'analytics/basket-pop-up-items/', $data);
    }

    /**
     * @param $shopId
     * @param null $start_date
     * @param null $end_date
     * @param string $group_type
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getBasketPopUp($shopId, $start_date = null, $end_date = null, $group_type = 'month')
    {
        $data = [
            'shop_id' => $shopId,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'group_type' => $group_type
        ];

        return $this->requestData('POST', 'analytics/basket-pop-up/', $data);
    }

    /**
     * @param $shopId
     * @param null $start_date
     * @param null $end_date
     * @param string $group_type
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getBasketPopUpClicks($shopId, $start_date = null, $end_date = null, $group_type = 'month')
    {
        $data = [
            'shop_id' => $shopId,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'group_type' => $group_type
        ];

        return $this->requestData('POST', 'analytics/basket-pop-up-clicks/', $data);
    }

    /**
     * @param $shopId
     * @param null $start_date
     * @param null $end_date
     * @param string $group_type
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getBasketPopUpPurchases($shopId, $start_date = null, $end_date = null, $group_type = 'month')
    {
        $data = [
            'shop_id' => $shopId,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'group_type' => $group_type
        ];

        return $this->requestData('POST', 'analytics/basket-pop-up-purchases/', $data);
    }

    /**
     * @param $shopId
     * @param null $start_date
     * @param null $end_date
     * @param string $group_type
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getBasketPopUpRevenue($shopId, $start_date = null, $end_date = null, $group_type = 'month')
    {
        $data = [
            'shop_id' => $shopId,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'group_type' => $group_type
        ];

        return $this->requestData('POST', 'analytics/basket-pop-up-revenue/', $data);
    }

    /**
     * @param $shopId
     * @param null $start_date
     * @param null $end_date
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getVouchersRevenueSum($shopId, $start_date = null, $end_date = null)
    {
        $data = [
            'shop_id' => $shopId,
            'start_date' => $start_date,
            'end_date' => $end_date
        ];

        return $this->requestData('POST', 'analytics/vouchers-revenue-sum/', $data);
    }

    /**
     * @param $shopId
     * @param null $start_date
     * @param null $end_date
     * @param string $group_type
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getVouchersShown($shopId, $start_date = null, $end_date = null, $group_type = 'month')
    {
        $data = [
            'shop_id' => $shopId,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'group_type' => $group_type
        ];

        return $this->requestData('POST', 'analytics/vouchers-shown/', $data);
    }

    /**
     * @param $shopId
     * @param null $start_date
     * @param null $end_date
     * @param string $group_type
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getVouchersRevenue($shopId, $start_date = null, $end_date = null, $group_type = 'month')
    {
        $data = [
            'shop_id' => $shopId,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'group_type' => $group_type
        ];

        return $this->requestData('POST', 'analytics/vouchers-revenue/', $data);
    }

    /**
     * @param $shopId
     * @param null $start_date
     * @param null $end_date
     * @param string $group_type
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getVouchersUsers($shopId, $start_date = null, $end_date = null, $group_type = 'month')
    {
        $data = [
            'shop_id' => $shopId,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'group_type' => $group_type
        ];

        return $this->requestData('POST', 'analytics/users-visit-with-vouchers/', $data);
    }

    /**
     * @param $shopId
     * @param null $start_date
     * @param null $end_date
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getVouchersDemography($shopId, $start_date = null, $end_date = null)
    {
        $data = [
            'shop_id' => $shopId,
            'start_date' => $start_date,
            'end_date' => $end_date
        ];

        return $this->requestData('POST', 'analytics/vouchers-demography/', $data);
    }

    /**
     * @param $shopId
     * @param null $start_date
     * @param null $end_date
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getCategoriesRedeemedVoucher($shopId, $start_date = null, $end_date = null)
    {
        $data = [
            'shop_id' => $shopId,
            'start_date' => $start_date,
            'end_date' => $end_date
        ];

        return $this->requestData('POST', 'analytics/categories-redeemed-vouchers/', $data);
    }

    /**
     * @param $date_start
     * @param $date_end
     * @param $type
     * @return DatePeriod
     * @throws Exception
     */
    public function getDatePeriods($date_start, $date_end, $type)
    {
        switch ($type) {
            case 'year':
                $date_interval = DateInterval::createFromDateString('1 year');
                $date_start->modify('first day of this year');
                $date_end->modify('first day of this year');
                break;

            case 'month':
                $date_interval = DateInterval::createFromDateString('1 month');
                $date_start->modify('first day of this month');
                $date_end->modify('first day of next month');
                break;

            case 'hour':
                $date_interval = DateInterval::createFromDateString('1 hour');
                $date_start->modify("06:00");
                $date_end->modify("23:59");
                break;

            default:
                $date_end->modify('+1 day');
                $date_interval = new DateInterval('P1D');
                break;
        }

        $periods = new DatePeriod(
            $date_start,
            $date_interval,
            $date_end
        );

        return $periods;
    }

    /**
     * @param $shopId
     * @param null $start_date
     * @param null $end_date
     * @param string $group_type
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getRecommendationClickVsPurchases($shopId, $start_date = null, $end_date = null, $group_type = 'month')
    {
        $data = [
            'shop_id' => $shopId,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'group_type' => $group_type
        ];

        return $this->requestData('POST', 'analytics/recommendation-click-vs-purchase/', $data);
    }

    /**
     * @param $shopId
     * @param null $start_date
     * @param null $end_date
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getRecommendationPurchaseItems($shopId, $start_date = null, $end_date = null)
    {
        $data = [
            'shop_id' => $shopId,
            'start_date' => $start_date,
            'end_date' => $end_date
        ];

        return $this->requestData('POST', 'analytics/recommendation-items/', $data);
    }

    /**
     * @param $shopId
     * @param null $start_date
     * @param null $end_date
     * @param string $group_type
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getRecommendation($shopId, $start_date = null, $end_date = null, $group_type = 'month')
    {
        $data = [
            'shop_id' => $shopId,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'group_type' => $group_type
        ];

        return $this->requestData('POST', 'analytics/recommendation/', $data);
    }

    /**
     * @param $shopId
     * @param null $start_date
     * @param null $end_date
     * @param string $group_type
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getRecommendationClicks($shopId, $start_date = null, $end_date = null, $group_type = 'month')
    {
        $data = [
            'shop_id' => $shopId,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'group_type' => $group_type
        ];

        return $this->requestData('POST', 'analytics/recommendation-clicks/', $data);
    }

    /**
     * @param $shopId
     * @param null $start_date
     * @param null $end_date
     * @param string $group_type
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getRecommendationPurchases($shopId, $start_date = null, $end_date = null, $group_type = 'month')
    {
        $data = [
            'shop_id' => $shopId,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'group_type' => $group_type
        ];

        return $this->requestData('POST', 'analytics/recommendation-purchases/', $data);
    }

    /**
     * @param $shopId
     * @param null $start_date
     * @param null $end_date
     * @param string $group_type
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getRecommendationRevenue($shopId, $start_date = null, $end_date = null, $group_type = 'month')
    {
        $data = [
            'shop_id' => $shopId,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'group_type' => $group_type
        ];

        return $this->requestData('POST', 'analytics/recommendation-revenue/', $data);
    }
}
