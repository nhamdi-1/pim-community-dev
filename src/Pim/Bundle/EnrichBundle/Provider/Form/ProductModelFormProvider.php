<?php

namespace Pim\Bundle\EnrichBundle\Provider\Form;

use Pim\Component\Catalog\Model\ProductModelInterface;

/**
 * Form provider for product model
 *
 * @author    Adrien Pétremann <adrien.petremann@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class ProductModelFormProvider implements FormProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getForm($productModel)
    {
        return 'pim-product-model-edit-form';
    }

    /**
     * {@inheritdoc}
     */
    public function supports($element)
    {
        return $element instanceof ProductModelInterface;
    }
}
