<?php

namespace spec\Pim\Bundle\CatalogBundle\Elasticsearch\Indexer;

use Pim\Bundle\CatalogBundle\Elasticsearch\Indexer\ProductModelDescendantsIndexer;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ProductModelDescendantsIndexerSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(ProductModelDescendantsIndexer::class);
    }
}
