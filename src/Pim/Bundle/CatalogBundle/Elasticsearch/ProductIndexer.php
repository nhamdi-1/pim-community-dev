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
 * Indexer responsible for the indexing of products entities. Each product should be normalized in the right format
 * prior to be indexed in Elasticsearch.
 *
 * @author    Julien Janvier <j.janvier@gmail.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductIndexer implements IndexerInterface, BulkIndexerInterface, RemoverInterface, BulkRemoverInterface
{
    const INDEXING_FORMAT_PRODUCT_INDEX = 'indexing_product';
    const INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX = 'indexing_product_and_model';

    /** @var NormalizerInterface */
    protected $normalizer;

    /** @var Client */
    protected $productClient;

    /** @var Client */
    protected $productAndProductModelClient;

    /** @var string */
    protected $indexType;

    /**
     * @param NormalizerInterface $normalizer
     * @param Client              $productClient
     * @param Client              $productAndProductModelClient
     * @param string              $indexType
     */
    public function __construct(
        NormalizerInterface $normalizer,
        Client $productClient,
        Client $productAndProductModelClient,
        string $indexType
    ) {
        $this->normalizer = $normalizer;
        $this->productClient = $productClient;
        $this->productAndProductModelClient = $productAndProductModelClient;
        $this->indexType = $indexType;
    }

    /**
     * Indexes a product in both the product index and the product and product model index.
     *
     * {@inheritdoc}
     */
    public function index($object, array $options = [])
    {
        $normalizedObject = $this->normalizer->normalize($object, self::INDEXING_FORMAT_PRODUCT_INDEX);
        $this->validateObjectNormalization($normalizedObject);
        $this->productClient->index($this->indexType, $normalizedObject['id'], $normalizedObject);

        $normalizedObject = $this->normalizer->normalize($object, self::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX);
        $this->validateObjectNormalization($normalizedObject);
        $this->productAndProductModelClient->index($this->indexType, $normalizedObject['id'], $normalizedObject);
    }

    /**
     * Indexes a product in both the product index and the product and product model index.
     *
     * {@inheritdoc}
     */
    public function indexAll(array $objects, array $options = [])
    {
        if (empty($objects)) {
            return;
        }

        $normalizedObjects = [];
        foreach ($objects as $object) {
            $normalizedObject = $this->normalizer->normalize($object, self::INDEXING_FORMAT_PRODUCT_INDEX);
            $this->validateObjectNormalization($normalizedObject);
            $normalizedObjects[] = $normalizedObject;
        }
        $this->productClient->bulkIndexes($this->indexType, $normalizedObjects, 'id', Refresh::waitFor());

        foreach ($objects as $object) {
            $normalizedObject = $this->normalizer->normalize($object, self::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX);
            $this->validateObjectNormalization($normalizedObject);
            $normalizedObjects[] = $normalizedObject;
        }
        $this->productClient->bulkIndexes($this->indexType, $normalizedObjects, 'id', Refresh::waitFor());
    }

    /**
     * Removes the product from both the product index and the product and product model index.
     *
     * {@inheritdoc}
     */
    public function remove($objectId, array $options = [])
    {
        $this->productClient->delete($this->indexType, $objectId);
        $this->productAndProductModelClient->delete($this->indexType, $objectId);
    }

    /**
     * Removes the products from both the product index and the product and product model index.
     *
     * {@inheritdoc}
     */
    public function removeAll(array $objectIds, array $options = [])
    {
        $this->productClient->bulkDelete($this->indexType, $objectIds);
        $this->productAndProductModelClient->bulkDelete($this->indexType, $objectIds);
    }

    /**
     * {@inheritdoc}
     */
    protected function validateObjectNormalization(array $normalization)
    {
        if (!isset($normalization['id'])) {
            throw new \InvalidArgumentException('Only products with an "id" property can be indexed in the search engine.');
        }
    }
}
