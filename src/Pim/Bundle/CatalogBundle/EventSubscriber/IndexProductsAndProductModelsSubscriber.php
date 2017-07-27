<?php

namespace Pim\Bundle\CatalogBundle\EventSubscriber;

use Akeneo\Component\StorageUtils\Event\RemoveEvent;
use Akeneo\Component\StorageUtils\StorageEvents;
use Pim\Bundle\CatalogBundle\Elasticsearch\ObjectIndexer;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Model\ProductModelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Index products in the search engine.
 *
 * This is not done directly in the product saver as it's only a technical
 * problem. The product saver only handles business stuff.
 *
 * Idea: Refactor the IndexProductOrProductModels and IndexProducts for IndexProductsAndProductModels which takes a list of
 * indexer
 *
 * @author    Julien Janvier <j.janvier@gmail.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class IndexProductsAndProductModelsSubscriber implements EventSubscriberInterface
{
    /** @var ObjectIndexer */
    protected $indexers;

    /**
     * @param ObjectIndexer $indexers
     */
    public function __construct(array $indexers)
    {
        $this->indexers = $indexers;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            StorageEvents::POST_SAVE => 'indexProductOrProductModel',
            StorageEvents::POST_SAVE_ALL => 'bulkIndexProductOrProductModels',
            StorageEvents::POST_REMOVE => 'deleteProductOrProductModel',
        ];
    }

    /**
     * Index one single product.
     *
     * @param GenericEvent $event
     */
    public function indexProductOrProductModel(GenericEvent $event)
    {
        $product = $event->getSubject();
        if (!$product instanceof ProductModelInterface || !$product instanceof ProductInterface) {
            return;
        }

        if (!$event->hasArgument('unitary') || false === $event->getArgument('unitary')) {
            return;
        }

        foreach ($this->indexers as $indexer) {
            $indexer->index($product);
        }
    }

    /**
     * Index several products at a time.
     *
     * @param GenericEvent $event
     */
    public function bulkIndexProductOrProductModels(GenericEvent $event)
    {
        $products = $event->getSubject();
        if (!is_array($products)) {
            return;
        }

        if (!current($products) instanceof ProductModelInterface) {
            return;
        }

        foreach ($this->indexers as $indexer) {
            $indexer->indexAll($products);
        }
    }

    /**
     * Delete one single product from ES index
     *
     * @param RemoveEvent $event
     */
    public function deleteProductOrProductModel(RemoveEvent $event)
    {
        $product = $event->getSubject();
        if (!$product instanceof ProductModelInterface) {
            return;
        }

        foreach ($this->indexers as $indexer) {
            $indexer->remove($product);
        }
    }
}
