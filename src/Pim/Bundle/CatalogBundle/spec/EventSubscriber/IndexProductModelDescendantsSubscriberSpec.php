<?php

namespace spec\Pim\Bundle\CatalogBundle\EventSubscriber;

use Pim\Bundle\CatalogBundle\EventSubscriber\IndexProductModelDescendantsSubscriber;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class IndexProductModelDescendantsSubscriberSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(IndexProductModelDescendantsSubscriber::class);
    }
}
