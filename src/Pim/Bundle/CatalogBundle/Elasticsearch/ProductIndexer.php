<?php

namespace Pim\Bundle\CatalogBundle\Elasticsearch;

use Akeneo\Bundle\ElasticsearchBundle\Client;
use Akeneo\Bundle\ElasticsearchBundle\Refresh;
use Akeneo\Component\StorageUtils\Indexer\BulkIndexerInterface;
use Akeneo\Component\StorageUtils\Indexer\IndexerInterface;
use Akeneo\Component\StorageUtils\Remover\BulkRemoverInterface;
use Akeneo\Component\StorageUtils\Remover\RemoverInterface;
use Doctrine\Common\Util\ClassUtils;
use Pim\Component\Catalog\Model\ProductInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Indexer responsible for the normalization and indexing of objects.
 *
 * TODO: validate object with supportedObjects property injected ? or written in raw ?
 *
 * @author    Julien Janvier <j.janvier@gmail.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductIndexer implements IndexerInterface, BulkIndexerInterface, RemoverInterface, BulkRemoverInterface
{
    /** @var NormalizerInterface */
    protected $normalizer;

    /** @var Client */
    protected $productClient;

    /** @var string */
    protected $indexType;

    /**
     * @param NormalizerInterface $normalizer
     * @param Client              $indexer
     * @param string              $indexType
     * @param string              $indexingFormat
     */
    public function __construct(
        NormalizerInterface $normalizer,
        Client $productClient,
        Client $productAndProductModelClient
    ) {
        $this->normalizer = $normalizer;
        $this->productClient = $productClient;
        $this->productAndProductModelClient = $productAndProductModelClient;
    }

    /**
     * {@inheritdoc}
     */
    public function index($object, array $options = [])
    {
        $normalizedObject = $this->normalizer->normalize($object, $this->indexingFormat);
        $this->validateObjectNormalization($normalizedObject);
        $this->productClient->index($this->indexType, $normalizedObject['id'], $normalizedObject);
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
            $normalizedObject = $this->normalizer->normalize($object, 'indexing');
            $this->validateObjectNormalization($normalizedObject);
            $normalizedObjects[] = $normalizedObject;
        }

        $this->productClient->bulkIndexes($this->indexType, $normalizedObjects, 'id', Refresh::waitFor());
    }

    /**
     * {@inheritdoc}
     */
    public function remove($objectId, array $options = [])
    {
        $this->productClient->delete($this->indexType, $objectId);
    }

    /**
     * {@inheritdoc}
     */
    public function removeAll(array $objectIds, array $options = [])
    {
        $this->productClient->bulkDelete($this->indexType, $objectIds);
    }

    /**
     * {@inheritdoc}
     */
    protected function validateObjectNormalization(array $normalization)
    {
        if (!isset($normalization['id'])) {
            throw new \InvalidArgumentException('Only object with an "id" property can be indexed in the search engine.');
        }
    }
}
