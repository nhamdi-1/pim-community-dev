<?php

namespace Pim\Component\Catalog\Model;

/**
 * @author    Adrien Pétremann <adrien.petremann@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
interface CanHaveProductModelInterface
{
    /**
     * Get the variation level of this entity, on a zero-based value.
     * For example, if this entity has 2 parents, it's on level 2.
     * If it has 0 parent, it's on level 0.
     *
     * @return int
     */
    public function getVariationLevel(): int;
}
