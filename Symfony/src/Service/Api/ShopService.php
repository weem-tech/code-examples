<?php

namespace App\Service\Api;

use App\Entity\Shop\Shop;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;

/**
 * Class ShopService
 * @package App\Service\Api
 */
class ShopService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * ShopService constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(
        EntityManagerInterface $em
    )
    {
        $this->em = $em;
    }

    /**
     * @param string $url
     * @param string $apiKey
     * @return bool|null|object
     * @throws NonUniqueResultException
     */
    public function validateShop($url = '', $apiKey = '')
    {
        $shop = $this->em->getRepository(Shop::class)->findByUrlAndKey($url, $apiKey);
        if ($shop instanceof Shop) {
            return $shop;
        }

        return false;
    }

    /**
     * @param string $url
     * @return bool|null|object
     */
    public function getShopByUrl($url = '')
    {
        $data = [
            'url' => $url,
            'active' => 1
        ];
        $shop = $this->em->getRepository(Shop::class)->findOneBy($data);
        if ($shop instanceof Shop) {
            return $shop;
        }

        return false;
    }
}
