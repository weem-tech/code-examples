<?php

namespace App\Service\Core;

use App\Entity\Core\NumberRange;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;

class NumberRangeIncrementer
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $table;

    /**
     * NumberRangeIncrementer constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(
        EntityManagerInterface $em
    )
    {
        $this->connection = $em->getConnection();
        $this->table = $em->getClassMetadata(NumberRange::class)->getTableName();
    }

    /**
     * @param $name
     * @return bool|int|mixed
     * @throws Exception
     */
    public function increment($name)
    {
        $this->connection->beginTransaction();
        try {
            $number = $this->connection->fetchColumn('SELECT `number` FROM `' . $this->table . '` WHERE `name` = ? FOR UPDATE', [$name]);

            if ($number === false) {
                throw new RuntimeException(sprintf('Number range with name "%s" does not exist.', $name));
            }

            $this->connection->executeUpdate('UPDATE `' . $this->table . '` SET `number` = `number` + 1 WHERE `name` = ?', [$name]);
            $number += 1;
            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }

        return $number;
    }
}
