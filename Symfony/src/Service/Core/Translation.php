<?php

namespace App\Service\Core;

use App\Entity\Translation\Translation as TranslationEntity;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use PDO;
use Zend_Db_Adapter_Exception;

class Translation
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

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
        $this->em = $em;
        $this->connection = $em->getConnection();
        $this->table = $em->getClassMetadata(TranslationEntity::class)->getTableName();
    }

    /**
     * Reads a single translation data from the storage.
     * Also loads fallback (has less priority)
     *
     * @param int $locale
     * @param string $fallback
     * @param string $type
     * @param int $uuid
     *
     * @return array
     */
    public function readWithFallback($locale, $fallback, $type, $uuid = 1)
    {
        $translation = $this->read($locale, $type, $uuid);
        if ($fallback) {
            $translationFallback = $this->read($fallback, $type, $uuid);
        } else {
            $translationFallback = [];
        }

        return $translation + $translationFallback;
    }

    /**
     * Reads a single translation data from the storage.
     *
     * @param int $locale
     * @param string $type
     * @param int $uuid
     *
     * @return array
     */
    public function read($locale, $type, $uuid = 1)
    {
        $builder = $this->connection->createQueryBuilder();
        $expr = $this->connection->getExpressionBuilder();
        $builder->select('t.data')
            ->from($this->table, 't')
            ->where($expr->eq('t.type', ':type'))
            ->andWhere($expr->eq('t.uuid', ':uuid'))
            ->andWhere($expr->eq('t.locale_id', ':locale'))
            ->setParameters([
                'type' => $type,
                'uuid' => $uuid,
                'locale' => $locale,
            ]);

        $data = $builder->execute()
            ->fetch(PDO::FETCH_COLUMN);

        return $this->unFilterData($data);
    }

    /**
     * Un filter translation data for output.
     *
     * @param mixed $data
     *
     * @return array
     */
    public function unFilterData($data)
    {
        $data = unserialize($data);
        if ($data === false) {
            $data = unserialize(utf8_decode($data));
        }
        if ($data === false) {
            return [];
        }

        return $data;
    }

    /**
     * Reads multiple translations including their fallbacks
     * Merges the two (fallback has less priority) and returns the results
     *
     * @param int $locale
     * @param int $fallback
     * @param string $type
     * @param int|array $uuid
     *
     * @return array|mixed
     */
    public function readBatchWithFallback($locale, $fallback, $type, $uuid = 1)
    {
        $translationData = $this->readBatch($locale, $type, $uuid);

        // Look for a fallback and correspondent translations
        if (!empty($fallback)) {
            $translationFallback = $this->readBatch($fallback, $type, $uuid);

            if (!empty($translationFallback)) {
                // We need something like array_merge_recursive, but that also
                // recursively merges elements with int keys.
                foreach ($translationFallback as $translationUuid => $data) {
                    if (array_key_exists($translationUuid, $translationData)) {
                        $translationData[$translationUuid] += $data;
                    } else {
                        $translationData[$translationUuid] = $data;
                    }
                }
            }
        }

        return $translationData;
    }

    /**
     * Reads multiple translation data from storage.
     *
     * @param int $locale
     * @param string $type
     * @param int $uuid
     *
     * @return array
     */
    public function readBatch($locale, $type, $uuid = 1)
    {
        $builder = $this->connection->createQueryBuilder();
        $expr = $this->connection->getExpressionBuilder();
        $builder->select('data, locale_id, type, uuid')
            ->from($this->table, 't');

        if ($locale) {
            $builder
                ->andWhere($expr->eq('t.locale_id', ':locale'))
                ->setParameter('locale', $locale);
        }
        if ($type) {
            $builder
                ->andWhere($expr->eq('t.type', ':type'))
                ->setParameter('type', $type);
        }
        if ($uuid) {
            if (is_array($uuid)) {
                $builder
                    ->andWhere($expr->in('t.uuid', ':uuid'))
                    ->setParameter('uuid', $uuid, Connection::PARAM_INT_ARRAY);
            } else {
                $builder
                    ->andWhere($expr->eq('t.uuid', ':uuid'))
                    ->setParameter('uuid', $uuid);
            }
        }

        $data = $builder->execute()->fetchAll();

        foreach ($data as &$translation) {
            $translation['data'] = $this->unFilterData($translation['data']);
        }

        return $data;
    }

    /**
     * Writes multiple translation data to storage.
     *
     * @param mixed $data
     *
     * @throws Zend_Db_Adapter_Exception
     */
    public function writeBatch($data)
    {
        $requiredKeys = ['data', 'locale_id', 'type', 'uuid'];

        foreach ($data as $translation) {
            if (count(array_intersect_key(array_flip($requiredKeys), $translation)) !== count($requiredKeys)) {
                continue;
            }

            $this->write(
                $translation['locale'],
                $translation['type'],
                $translation['uuid'] ?: 1,
                $translation['data']
            );
        }
    }

    /**
     * Saves translation data to the storage.
     *
     * @param int $locale
     * @param string $type
     * @param int $uuid
     * @param mixed $data
     *
     * @return
     * @throws Zend_Db_Adapter_Exception
     *
     */
    public function write($locale, $type, $uuid = 1, $data = null)
    {
        $data = $this->filterData($data);

        if (!empty($data)) {
            $tmp = $this->read($locale, $type, $uuid);
            if (!empty($tmp)) {
                $builder = $this->connection->createQueryBuilder();
                $expr = $this->connection->getExpressionBuilder();
                $builder->update($this->table, 't')
                    ->where($expr->andX(
                        $expr->eq('t.type', ':type'),
                        $expr->eq('t.uuid', ':uuid'),
                        $expr->eq('t.locale_id', ':locale')
                    ))
                    ->set('t.data', ':data')
                    ->setParameters([
                        'type' => $type,
                        'locale' => $locale,
                        'uuid' => $uuid,
                        'data' => $data,
                    ], [
                        'type' => Type::STRING,
                        'locale' => Type::INTEGER,
                        'uuid' => Type::INTEGER,
                        'data' => Type::TEXT,
                    ])
                    ->execute();
                return;
            }

            $builder = $this->connection->createQueryBuilder();
            $builder->insert($this->table)
                ->values([
                    'type' => ':type',
                    'locale_id' => ':locale',
                    'uuid' => ':uuid',
                    'data' => ':data',
                ])
                ->setParameters([
                    'type' => $type,
                    'locale' => $locale,
                    'uuid' => $uuid,
                    'data' => $data,
                ], [
                    'type' => Type::STRING,
                    'locale' => Type::INTEGER,
                    'uuid' => Type::INTEGER,
                    'data' => Type::TEXT,
                ])
                ->execute();
            return;
        }

        $this->delete($locale, $type, $uuid);
    }

    /**
     * Filter translation data for saving.
     *
     * @param array $data
     *
     * @return string
     */
    public function filterData(array $data)
    {
        foreach ($data as $key => $value) {
            if (!is_string($value)) {
                continue;
            }
            if (trim($value) === '') {
                unset($data[$key]);
            }
        }

        return serialize($data);
    }

    /**
     * Deletes translations from storage.
     *
     * @param int $locale
     * @param string $type
     * @param int $uuid
     *
     * @return array
     */
    public function delete($locale, $type, $uuid = 1)
    {
        $builder = $this->connection->createQueryBuilder();
        $expr = $this->connection->getExpressionBuilder();
        $builder->delete($this->table, 't');

        if ($locale) {
            $builder
                ->andWhere($expr->eq('t.locale_id', ':locale'))
                ->setParameter('locale', $locale);
        }
        if ($type) {
            $builder
                ->andWhere($expr->eq('t.type', ':type'))
                ->setParameter('type', $type);
        }
        if ($uuid) {
            $builder
                ->andWhere($expr->eq('t.uuid', ':uuid'))
                ->setParameter('uuid', $uuid);
        }

        $builder->execute();
    }

    /**
     * Filter translation text method
     *
     * @param string $text
     *
     * @return string
     */
    protected function filterText($text)
    {
        $text = html_entity_decode($text);
        $text = preg_replace('!<[^>]*?>!', ' ', $text);
        $text = str_replace(chr(0xa0), ' ', $text);
        $text = preg_replace('/\s\s+/', ' ', $text);
        $text = htmlspecialchars($text);
        $text = trim($text);

        return $text;
    }
}
