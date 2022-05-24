<?php

namespace App\Component\Entity;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

trait EntityTrait
{
    /**
     * Example:
     *
     * $model->fromArray($data);
     *
     * @param array $array
     * @param array $fillable optional property whitelist for mass-assignment
     *
     * @return self
     */
    public function fromArray(array $array = [], array $fillable = [])
    {
        foreach ($array as $key => $value) {
            if (count($fillable) && !in_array($key, $fillable)) {
                continue;
            }
            $method = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }

        return $this;
    }

    public function toArray()
    {
        return get_object_vars($this);
    }
}