<?php

namespace Pim\Bundle\CatalogBundle\Elasticsearch;

use Akeneo\Component\StorageUtils\Indexer\BulkIndexerInterface;
use Akeneo\Component\StorageUtils\Indexer\IndexerInterface;
use Akeneo\Component\StorageUtils\Remover\BulkRemoverInterface;
use Akeneo\Component\StorageUtils\Remover\RemoverInterface;
use Doctrine\Common\Util\ClassUtils;
use Pim\Component\Catalog\Model\ProductInterface;

/**
 * Product model indexer, define custom logic and options for product model indexing in the search engine.
 *
 * @author    Julien Janvier <j.janvier@gmail.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductModelIndexer extends AbstractIndexer implements IndexerInterface, BulkIndexerInterface, RemoverInterface, BulkRemoverInterface
{
    /**
     * {@inheritdoc}
     */
    protected function validateObject($object)
    {
        if (!$object instanceof ProductInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Only product models "Pim\Component\Catalog\Model\ProductModelInterface" can be indexed in the search engine, "%s" provided.',
                    ClassUtils::getClass($object)
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function validateObjectNormalization(array $normalization)
    {
        if (!isset($normalization['id'])) {
            throw new \InvalidArgumentException('Only product models with an ID can be indexed in the search engine.');
        }
    }
}
