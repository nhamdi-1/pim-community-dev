<?php

namespace Pim\Bundle\CatalogBundle\Elasticsearch\Indexer;

use Akeneo\Component\StorageUtils\Indexer\BulkIndexerInterface;
use Akeneo\Component\StorageUtils\Indexer\IndexerInterface;
use Akeneo\Component\StorageUtils\Remover\BulkRemoverInterface;
use Akeneo\Component\StorageUtils\Remover\RemoverInterface;
use Doctrine\Common\Util\ClassUtils;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Model\ProductModelInterface;

/**
 * Indexer responsible for the indexing of all product model children (the subtree of product variants and product
 * models).
 *
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductModelDescendantsIndexer implements
    IndexerInterface,
    BulkIndexerInterface,
    RemoverInterface,
    BulkRemoverInterface
{
    /** @var BulkIndexerInterface|BulkRemoverInterface */
    protected $productIndexer;

    /** @var BulkIndexerInterface|BulkRemoverInterface */
    protected $productModelIndexer;

    /**
     * @param BulkIndexerInterface|BulkRemoverInterface $productIndexer
     * @param BulkIndexerInterface|BulkRemoverInterface $productModelIndexer
     */
    public function __construct(
        BulkIndexerInterface $productIndexer,
        BulkIndexerInterface $productModelIndexer
    ) {
        $this->productIndexer = $productIndexer;
        $this->productModelIndexer = $productModelIndexer;
    }

    /**
     * Recursively triggers the indexing of all the given product model children which can be product model or products.
     *
     * {@inheritdoc}
     */
    public function index($object, array $options = [])
    {
        if (!$object instanceof ProductModelInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Only product models "%s" can be indexed in the search engine, "%s" provided.',
                    ClassUtils::getClass(ProductModelInterface::class),
                    ClassUtils::getClass($object)
                )
            );
        }

        $this->indexProductModelChilren($object->getChildren());
    }

    /**
     * {@inheritdoc}
     */
    public function indexAll(array $objects, array $options = [])
    {
        if (empty($objects)) {
            return;
        }

        foreach ($objects as $object) {
            $this->index($object);
        }
    }

    /**
     * Recursively triggers the removing of all the given product model children from the indexes.
     *
     * {@inheritdoc}
     */
    public function remove($object, array $options = [])
    {
        if (!$object instanceof ProductModelInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Only product models "%s" can be indexed in the search engine, "%s" provided.',
                    ClassUtils::getClass(ProductModelInterface::class),
                    ClassUtils::getClass($object)
                )
            );
        }

        $this->removeProductModelChilren($object);
    }

    /**
     * Removes the products from both the product index and the product and product model index.
     *
     * {@inheritdoc}
     */
    public function removeAll(array $objects, array $options = [])
    {
        if (empty($objects)) {
            return;
        }

        foreach ($objects as $object) {
            $this->remove($object);
        }
    }

    /**
     * Indexes the a list of product model children (products or product models).
     *
     * @param array $productModelChildren
     */
    private function indexProductModelChilren(array $productModelChildren)
    {
        if (empty($productModelChildren)) {
            return;
        }

        if (current($productModelChildren) instanceof ProductInterface) {
            $this->productIndexer->indexAll($productModelChildren);

            return;
        }

        $this->productModelIndexer->indexAll($productModelChildren);

        foreach ($productModelChildren as $productModelChild) {
            if ($productModelChild->hasChildren()) {
                $this->indexProductModelChilren($productModelChild->getChildren());
            }
        }
    }

    /**
     * Removes from the indexes the given list of product model children (product variants or product models).
     *
     * @param array $productModelChildren
     */
    private function removeProductModelChilren(array $productModelChildren)
    {
        if (empty($productModelChildren)) {
            return;
        }

        if (current($productModelChildren) instanceof ProductInterface) {
            $this->productIndexer->removeAll($productModelChildren);

            return;
        }

        $this->productModelIndexer->removeAll($productModelChildren);

        foreach ($productModelChildren as $productModelChild) {
            if ($productModelChild->hasChildren()) {
                $this->removeProductModelChilren($productModelChild->getChildren());
            }
        }
    }
}

