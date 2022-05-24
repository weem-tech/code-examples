<?php

namespace App\Service\Api;

use Exception;

class Encryptor
{

    /**
     * @var array
     */
    private $methods;

    /**
     * Encryptor constructor.
     */
    public function __construct()
    {
        $this->methods = openssl_get_cipher_methods();
    }

    /**
     * Encrypts the data.
     *
     * @param $data
     * @param array $options
     * @return string
     */
    public function encrypt($data, $options = [])
    {
        if (in_array($options['cipher'], $this->methods)) {
            try {
                return openssl_encrypt($data, $options['cipher'], $options['key'], $options['options'], $options['iv'], $tag = null);
            } catch (Exception $exception) {
                return openssl_encrypt($data, $options['cipher'], $options['key'], $options['options'], $options['iv']);
            }
        }
        return false;
    }

    /**
     * Decrypts the data.
     *
     * @param $data
     * @param array $options
     * @return string
     */
    public function decrypt($data, $options = [])
    {
        if (in_array($options['cipher'], $this->methods)) {
            return openssl_decrypt($data, $options['cipher'], $options['key'], $options['options'], $options['iv'], null);
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function getRandomMethod()
    {
        return $this->methods[rand(0, count($this->methods) - 1)];
    }

    /**
     * @return string
     */
    public function generateKey()
    {
        return $this->generateRandomString();
    }

    /**
     * @param int $length
     * @return string
     */
    private function generateRandomString($length = 11)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * @return string
     */
    public function generateIv(string $method)
    {
        return openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
    }
}