<?php

namespace Pim\Bundle\CatalogBundle\EventSubscriber;

use Akeneo\Component\StorageUtils\Event\RemoveEvent;
use Akeneo\Component\StorageUtils\StorageEvents;
use Pim\Bundle\CatalogBundle\Elasticsearch\Indexer\ProductIndexer;
use Pim\Bundle\CatalogBundle\Elasticsearch\Indexer\ProductModelIndexer;
use Pim\Component\Catalog\Model\ProductModelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Index product models in the search engine.
 *
 * @author    Julien Janvier <julien.janvier@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class IndexProductModelsSubscriber implements EventSubscriberInterface
{
    /** @var ProductModelIndexer */
    protected $productModelIndexer;

    /**
     * @param ProductModelIndexer $productModelIndexer
     */
    public function __construct(ProductModelIndexer $productModelIndexer)
    {
        $this->productModelIndexer = $productModelIndexer;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            StorageEvents::POST_SAVE     => 'indexProductModel',
            StorageEvents::POST_SAVE_ALL => 'bulkIndexProductModels',
            StorageEvents::POST_REMOVE   => 'deleteProductModel',
        ];
    }

    /**
     * Index one product model.
     *
     * @param GenericEvent $event
     */
    public function indexProductModel(GenericEvent $event)
    {
        $product = $event->getSubject();
        if (!$product instanceof ProductModelInterface) {
            return;
        }

        if (!$event->hasArgument('unitary') || false === $event->getArgument('unitary')) {
            return;
        }

        $this->productModelIndexer->index($product);
    }

    /**
     * Index several product models.
     *
     * @param GenericEvent $event
     */
    public function bulkIndexProductModels(GenericEvent $event)
    {
        $products = $event->getSubject();
        if (!is_array($products)) {
            return;
        }

        if (!current($products) instanceof ProductModelInterface) {
            return;
        }

        $this->productModelIndexer->indexAll($products);
    }

    /**
     * Delete one product model from ES index
     *
     * @param RemoveEvent $event
     */
    public function deleteProductModel(RemoveEvent $event)
    {
        $product = $event->getSubject();
        if (!$product instanceof ProductModelInterface) {
            return;
        }

        $this->productModelIndexer->remove($event->getSubjectId());
    }
}
