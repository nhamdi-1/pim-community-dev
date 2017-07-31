<?php

namespace spec\Pim\Bundle\CatalogBundle\EventSubscriber;

use Akeneo\Component\StorageUtils\Event\RemoveEvent;
use Akeneo\Component\StorageUtils\StorageEvents;
use Pim\Bundle\CatalogBundle\Elasticsearch\Indexer\ProductModelIndexer;
use Pim\Bundle\CatalogBundle\EventSubscriber\IndexProductModelsSubscriber;
use PhpSpec\ObjectBehavior;
use Pim\Component\Catalog\Model\ProductModelInterface;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\GenericEvent;

class IndexProductModelsSubscriberSpec extends ObjectBehavior
{
    function let(ProductModelIndexer $indexer)
    {
        $this->beConstructedWith($indexer);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(IndexProductModelsSubscriber::class);
    }

    function it_subscribes_to_events()
    {
        $this->getSubscribedEvents()->shouldReturn([
            StorageEvents::POST_SAVE => 'indexProductModel',
            StorageEvents::POST_SAVE_ALL => 'bulkIndexProductModels',
            StorageEvents::POST_REMOVE => 'deleteProductModel',
        ]);
    }

    function it_does_not_delete_non_product_entity_from_elasticsearch($indexer, RemoveEvent $event, \stdClass $subject)
    {
        $event->getSubject()->willReturn($subject);

        $indexer->remove(40)->shouldNotBeCalled();

        $this->deleteProductModel($event)->shouldReturn(null);
    }

    function it_delete_products_from_elasticsearch_index($indexer, RemoveEvent $event, ProductModelInterface $product)
    {
        $event->getSubjectId()->willReturn(40);
        $event->getSubject()->willReturn($product);

        $indexer->remove(40)->shouldBeCalled();

        $this->deleteProductModel($event)->shouldReturn(null);
    }

    function it_does_not_index_a_non_product_entity($indexer, GenericEvent $event, \stdClass $subject)
    {
        $event->getSubject()->willReturn($subject);
        $indexer->index(Argument::cetera())->shouldNotBeCalled();

        $this->indexProductModel($event);
    }

    function it_does_not_index_a_non_unitary_save_of_a_product(
        $indexer,
        GenericEvent $event,
        ProductModelInterface $productModel
    ) {
        $event->getSubject()->willReturn($productModel);
        $event->hasArgument('unitary')->willReturn(true);
        $event->getArgument('unitary')->willReturn(false);

        $indexer->index(Argument::any())->shouldNotBeCalled();

        $this->indexProductModel($event);
    }

    function it_does_not_index_a_non_unitary_save_of_a_product_bis(
        $indexer,
        GenericEvent $event,
        ProductModelInterface $productModel
    ) {
        $event->getSubject()->willReturn($productModel);
        $event->hasArgument('unitary')->willReturn(false);

        $indexer->index(Argument::any())->shouldNotBeCalled();

        $this->indexProductModel($event);
    }

    function it_does_not_bulk_index_non_product_entities(
        $indexer,
        GenericEvent $event,
        \stdClass $subject1
    ) {
        $event->getSubject()->willReturn([$subject1]);

        $indexer->indexAll(Argument::any())->shouldNotBeCalled();

        $this->bulkIndexProductModels($event);
    }

    function it_does_not_bulk_index_non_collections($indexer, GenericEvent $event, \stdClass $subject1)
    {
        $event->getSubject()->willReturn($subject1);

        $indexer->indexAll(Argument::any())->shouldNotBeCalled();

        $this->bulkIndexProductModels($event);
    }

    function it_indexes_a_single_product($indexer, GenericEvent $event, ProductModelInterface $productModel)
    {
        $event->getSubject()->willReturn($productModel);
        $event->hasArgument('unitary')->willReturn(true);
        $event->getArgument('unitary')->willReturn(true);

        $productModel->getIdentifier()->willReturn('identifier');

        $indexer->index($productModel)->shouldBeCalled();

        $this->indexProductModel($event);
    }

    function it_bulk_indexes_products(
        $indexer,
        GenericEvent $event,
        ProductModelInterface $productModel1,
        ProductModelInterface $productModel2
    ) {
        $event->getSubject()->willReturn([$productModel1, $productModel2]);

        $productModel1->getIdentifier()->willReturn('identifier1');
        $productModel2->getIdentifier()->willReturn('identifier2');

        $indexer->indexAll([$productModel1, $productModel2])->shouldBeCalled();

        $this->bulkIndexProductModels($event);
    }
}
