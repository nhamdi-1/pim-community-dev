<?php

namespace Pim\Bundle\CatalogBundle\EventSubscriber;

use Akeneo\Component\StorageUtils\Event\RemoveEvent;
use Akeneo\Component\StorageUtils\StorageEvents;
use Pim\Bundle\CatalogBundle\Elasticsearch\Indexer\ProductIndexer;
use Pim\Component\Catalog\Model\ProductInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Index products in the search engine.
 *
 * This is not done directly in the product saver as it's only a technical
 * problem. The product saver only handles business stuff.
 *
 * @author    Julien Janvier <julien.janvier@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class IndexProductsSubscriber implements EventSubscriberInterface
{
    /** @var ProductIndexer */
    protected $productIndexer;

    /**
     * @param  $productIndexer
     */
    public function __construct(ProductIndexer $productIndexer)
    {
        $this->productIndexer = $productIndexer;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            StorageEvents::POST_SAVE     => 'indexProduct',
            StorageEvents::POST_SAVE_ALL => 'bulkIndexProduct',
            StorageEvents::POST_REMOVE   => 'deleteProduct',
        ];
    }

    /**
     * Index one single product.
     *
     * @param GenericEvent $event
     */
    public function indexProduct(GenericEvent $event)
    {
        $product = $event->getSubject();
        if (!$product instanceof ProductInterface) {
            return;
        }

        if (!$event->hasArgument('unitary') || false === $event->getArgument('unitary')) {
            return;
        }

        $this->productIndexer->index($product);
    }

    /**
     * Index several products at a time.
     *
     * @param GenericEvent $event
     */
    public function bulkIndexProduct(GenericEvent $event)
    {
        $products = $event->getSubject();
        if (!is_array($products)) {
            return;
        }

        if (!current($products) instanceof ProductInterface) {
            return;
        }

        $this->productIndexer->indexAll($products);
    }

    /**
     * Delete one single product from ES index
     *
     * @param RemoveEvent $event
     */
    public function deleteProduct(RemoveEvent $event)
    {
        $product = $event->getSubject();
        if (!$product instanceof ProductInterface) {
            return;
        }

        $this->productIndexer->remove($product);
    }
}
