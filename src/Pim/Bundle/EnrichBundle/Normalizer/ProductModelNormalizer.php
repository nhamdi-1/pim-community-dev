<?php

namespace Pim\Bundle\EnrichBundle\Normalizer;

use Pim\Bundle\EnrichBundle\Provider\Form\FormProviderInterface;
use Pim\Component\Catalog\Localization\Localizer\AttributeConverterInterface;
use Pim\Component\Catalog\Model\ProductModelInterface;
use Pim\Component\Enrich\Converter\ConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\scalar;

/**
 * @author    Adrien Pétremann <adrien.petremann@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class ProductModelNormalizer implements NormalizerInterface
{
    /** @var string[] */
    private $supportedFormat = ['internal_api'];

    /** @var NormalizerInterface */
    private $productModelNormalizer;

    /** @var AttributeConverterInterface */
    private $localizedConverter;

    /** @var ConverterInterface */
    private $productValueConverter;

    /** @var FormProviderInterface */
    private $formProvider;

    /**
     * @param NormalizerInterface         $productModelNormalizer
     * @param AttributeConverterInterface $localizedConverter
     * @param ConverterInterface          $productValueConverter
     * @param FormProviderInterface       $formProvider
     */
    public function __construct(
        NormalizerInterface $productModelNormalizer,
        AttributeConverterInterface $localizedConverter,
        ConverterInterface $productValueConverter,
        FormProviderInterface $formProvider
    ) {
        $this->productModelNormalizer = $productModelNormalizer;
        $this->localizedConverter = $localizedConverter;
        $this->productValueConverter = $productValueConverter;
        $this->formProvider = $formProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($productModel, $format = null, array $context = [])
    {
        $normalizedProductModel = $this->productModelNormalizer->normalize($productModel, 'standard', $context);
        $normalizedProductModel['values'] = $this->localizedConverter->convertToLocalizedFormats(
            $normalizedProductModel['values'],
            $context
        );

        $normalizedProductModel['values'] = $this->productValueConverter->convert($normalizedProductModel['values']);

//        $oldestLog = $this->versionManager->getOldestLogEntry($product);
//        $newestLog = $this->versionManager->getNewestLogEntry($product);

//        $created = null !== $oldestLog ? $this->versionNormalizer->normalize($oldestLog, 'internal_api') : null;
//        $updated = null !== $newestLog ? $this->versionNormalizer->normalize($newestLog, 'internal_api') : null;

        $normalizedProductModel['meta'] = [
                'form'              => $this->formProvider->getForm($productModel),
                'id'                => $productModel->getId(),
//                'created'           => $created,
//                'updated'           => $updated,
                'model_type'        => 'product',
//                'structure_version' => $this->structureVersionProvider->getStructureVersion(),
//                'completenesses'    => $this->getNormalizedCompletenesses($product),
//                'image'             => $this->normalizeImage($product->getImage(), $format, $context),
                'label' => ['en_US' => 'JAMBON']
            ];

        return $normalizedProductModel;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof ProductModelInterface && in_array($format, $this->supportedFormat);
    }
}
