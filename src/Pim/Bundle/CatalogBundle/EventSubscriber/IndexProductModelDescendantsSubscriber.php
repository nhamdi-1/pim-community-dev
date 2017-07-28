<?php

namespace Pim\Bundle\CatalogBundle\EventSubscriber;

use Akeneo\Component\StorageUtils\Event\RemoveEvent;
use Akeneo\Component\StorageUtils\StorageEvents;
use Pim\Bundle\CatalogBundle\Elasticsearch\Indexer\ProductIndexer;
use Pim\Component\Catalog\Model\ProductModelInterface;
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
class IndexProductModelDescendantsSubscriber implements EventSubscriberInterface
{
    /** @var ProductIndexer */
    protected $productModelDescendantsIndexer;

    /**
     * @param  $productIndexer
     */
    public function __construct(ProductIndexer $productIndexer)
    {
        $this->productModelDescendantsIndexer = $productIndexer;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            StorageEvents::POST_SAVE     => 'indexProductModelDescendants',
            StorageEvents::POST_SAVE_ALL => 'bulkIndexProductModelsDescendants',
            // TODO: Delete ?
        ];
    }

    /**
     * Index one product model descendants.
     *
     * @param GenericEvent $event
     */
    public function indexProductModelDescendants(GenericEvent $event)
    {
        $product = $event->getSubject();
        if (!$product instanceof ProductModelInterface) {
            return;
        }

        if (!$event->hasArgument('unitary') || false === $event->getArgument('unitary')) {
            return;
        }

        $this->productModelDescendantsIndexer->index($product);
    }

    /**
     * Index several product models descendants.
     *
     * @param GenericEvent $event
     */
    public function bulkIndexProductModelsDescendants(GenericEvent $event)
    {
        $products = $event->getSubject();
        if (!is_array($products)) {
            return;
        }

        if (!current($products) instanceof ProductModelInterface) {
            return;
        }

        $this->productModelDescendantsIndexer->indexAll($products);
    }
}
