<?php

namespace App\Component\Entity;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use InvalidArgumentException;

/**
 * Class EntityManager
 * @package App\Component\Entity
 */
class EntityManager extends EntityManagerDecorator
{
    /**
     * Debug mode flag for the query builders.
     *
     * @var bool
     */
    protected $debugMode = false;

    /**
     * Factory method to create EntityManager instances.
     *
     * @param array|Connection $connection An array with the connection parameters or an existing Connection instance.
     * @param Configuration $config The Configuration instance to use.
     * @param EventManager $eventManager The EventManager instance to use.
     *
     * @return EntityManager The created EntityManager.
     *
     * @throws InvalidArgumentException
     * @throws ORMException
     */
    public static function create($connection, Configuration $config, EventManager $eventManager = null)
    {
        if (!$config->getMetadataDriverImpl()) {
            throw ORMException::missingMappingDriverImpl();
        }

        $connection = static::createConnection($connection, $config, $eventManager);

        return new self($connection, $config, $connection->getEventManager());
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder|QueryBuilder
     */
    public function createQueryBuilder()
    {
        return new QueryBuilder($this);
    }

    /**
     * Returns the total count of the passed query builder.
     *
     * @param Query $query
     *
     * @return int|null
     */
    public function getQueryCount(Query $query)
    {
        $pagination = $this->createPaginator($query);

        return $pagination->count();
    }

    /**
     * Returns new instance of Paginator
     *
     * This method should be used instead of
     * new \Doctrine\ORM\Tools\Pagination\Paginator($query).
     *
     * As of SW 4.2 $paginator->setUseOutputWalkers(false) will be set here.
     *
     * @since 4.1.4
     *
     * @param Query $query
     *
     * @return Paginator
     */
    public function createPaginator(Query $query)
    {
        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers(false);

        return $paginator;
    }

    /**
     * Checks if the debug mode for doctrine orm queries is enabled.
     *
     * @return bool
     */
    public function isDebugModeEnabled()
    {
        return $this->debugMode;
    }

    /**
     * Disables the query builder debug mode.
     */
    public function disableDebugMode()
    {
        $this->debugMode = false;
    }

    /**
     * Enables or disables the debug mode of the query builders.
     */
    public function enableDebugMode()
    {
        $this->debugMode = true;
    }
}
