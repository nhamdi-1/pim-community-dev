<?php

namespace Pim\Bundle\CatalogBundle\Elasticsearch;

use Akeneo\Component\StorageUtils\Indexer\BulkIndexerInterface;
use Akeneo\Component\StorageUtils\Indexer\IndexerInterface;
use Akeneo\Component\StorageUtils\Remover\BulkRemoverInterface;
use Akeneo\Component\StorageUtils\Remover\RemoverInterface;
use Akeneo\Bundle\ElasticsearchBundle\Client;
use Akeneo\Bundle\ElasticsearchBundle\Refresh;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

abstract class AbstractIndexer implements IndexerInterface, BulkIndexerInterface, RemoverInterface, BulkRemoverInterface
{
    /** @var NormalizerInterface */
    protected $normalizer;

    /** @var Client */
    protected $indexer;

    /** @var string */
    protected $indexType;

    /** @var string */
    protected $indexingFormat;

    /**
     * @param NormalizerInterface $normalizer
     * @param Client              $indexer
     * @param string              $indexType
     * @param string              $indexingFormat
     */
    public function __construct(NormalizerInterface $normalizer, Client $indexer, string $indexType, string $indexingFormat)
    {
        $this->normalizer = $normalizer;
        $this->indexer = $indexer;
        $this->indexType = $indexType;
        $this->indexingFormat = $indexingFormat;
    }

    /**
     * {@inheritdoc}
     */
    public function index($object, array $options = [])
    {
        $this->validateObject($object);
        $normalizedObject = $this->normalizer->normalize($object, $this->indexingFormat);
        $this->validateObjectNormalization($normalizedObject);
        $this->indexer->index($this->indexType, $normalizedObject['id'], $normalizedObject);
    }

    /**
     * {@inheritdoc}
     */
    public function indexAll(array $objects, array $options = [])
    {
        if (empty($objects)) {
            return;
        }

        $normalizedObjects = [];
        foreach ($objects as $object) {
            $this->validateObject($object);
            $normalizedObject = $this->normalizer->normalize($object, 'indexing');
            $this->validateObjectNormalization($normalizedObject);
            $normalizedObjects[] = $normalizedObject;
        }

        $this->indexer->bulkIndexes($this->indexType, $normalizedObjects, 'id', Refresh::waitFor());
    }

    /**
     * {@inheritdoc}
     */
    public function remove($objectId, array $options = [])
    {
        $this->indexer->delete($this->indexType, $objectId);
    }

    /**
     * {@inheritdoc}
     */
    public function removeAll(array $objectIds, array $options = [])
    {
        $this->indexer->bulkDelete($this->indexType, $objectIds);
    }

    /**
     * @param mixed $object
     *
     * @throws \InvalidArgumentException
     */
    abstract protected function validateObject($object);

    /**
     * @param array $normalization
     *
     * @throws \InvalidArgumentException
     */
    abstract protected function validateObjectNormalization(array $normalization);
}
