<?php

namespace Pim\Component\Catalog\Validator\Constraints;

use Pim\Component\Catalog\Model\ProductModelInterface;
use Pim\Component\Catalog\Repository\ProductModelRepositoryInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Product model validator, it checks that product model values are consistant regarding its family variant and
 * attribute set.
 *
 * @author    Adrien Pétremann <adrien.petremann@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class ProductModelValidator extends ConstraintValidator
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var ProductModelRepositoryInterface */
    private $productModelRepository;

    /**
     * @param TranslatorInterface             $translator
     * @param ProductModelRepositoryInterface $productModelRepository
     */
    public function __construct(
        TranslatorInterface $translator,
        ProductModelRepositoryInterface $productModelRepository
    ) {
        $this->productModelRepository = $productModelRepository;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     *
     * @param ProductModelInterface $productModel
     */
    public function validate($productModel, Constraint $constraint): void
    {
        if (!$productModel instanceof ProductModelInterface) {
            throw new UnexpectedTypeException($constraint, ProductModelInterface::class);
        }

        if (!$constraint instanceof ProductModel) {
            throw new UnexpectedTypeException($constraint, ProductModel::class);
        }

        if ($productModel->getVariationLevel() > 0) {
            $this->validateEmptyAxesValues($productModel);
            $this->validateDuplicateAxesValues($productModel);
        }

        $this->validateAttributes($productModel);
    }

    /**
     * Validate that each axis attribute of the $productModel has a value
     *
     * @param ProductModelInterface $productModel
     */
    private function validateEmptyAxesValues(ProductModelInterface $productModel)
    {
        $level = $productModel->getVariationLevel();
        $axesAttributes = $productModel
            ->getFamilyVariant()
            ->getVariantAttributeSet($level)
            ->getAxes();

        foreach ($axesAttributes as $axisAttribute) {
            if ($productModel->getValue($axisAttribute->getCode()) === null) {
                $message = $this->translator->trans('pim_catalog.constraint.product_model_empty_axes_value');
                $this->context->buildViolation($message, [
                    '%attribute%' => $axisAttribute->getCode()
                ])->addViolation();
            }
        }
    }

    /**
     * Validate that there is no duplicate for axes values
     *
     * @param ProductModelInterface $productModel
     */

    private function validateDuplicateAxesValues(ProductModelInterface $productModel)
    {
        $level = $productModel->getVariationLevel();
        $axesAttributes = $productModel
            ->getFamilyVariant()
            ->getVariantAttributeSet($level)
            ->getAxes();

        $otherProductModels = $this->productModelRepository
            ->findAllByFamilyVariantAndLevel($productModel->getFamilyVariant(), $level);

        $otherProductModels = array_filter(
            $otherProductModels,
            function ($otherProductModel) use ($productModel) {
                return $otherProductModel->getId() !== $productModel->getId();
            }
        );

        foreach ($otherProductModels as $otherProductModel) {
            $duplicate = [];

            foreach ($axesAttributes as $axisAttribute) {
                $modelValue = $productModel->getValue($axisAttribute->getCode());
                $otherModelValue = $otherProductModel->getValue($axisAttribute->getCode());

                if ($modelValue->isEqual($otherModelValue)) {
                    $duplicate[$axisAttribute->getCode()] = $modelValue->getData();
                }
            }

            if (count($duplicate) === count($axesAttributes)) {
                $duplicateValues = implode(', ', array_values($duplicate));
                $duplicateAttributes = implode(', ', array_keys($duplicate));

                $message = $this->translator->trans('pim_catalog.constraint.product_model_duplicate_axis_value');
                $this->context->buildViolation($message, [
                    '%values%' => $duplicateValues,
                    '%attributes%' => $duplicateAttributes,
                ])->addViolation();
            }
        }
    }

    /**
     * Validate attribute values for the given $productModel
     *
     * @param ProductModelInterface $productModel
     */
    private function validateAttributes(ProductModelInterface $productModel): void
    {
        if ($productModel->getVariationLevel() === 0) {
            $levelAttributes = $productModel
                ->getFamilyVariant()
                ->getCommonAttributes();
        } else {
            $level = $productModel->getVariationLevel();
            $levelAttributes = $productModel
                ->getFamilyVariant()
                ->getVariantAttributeSet($level)
                ->getAttributes();
        }

        foreach($productModel->getAttributes() as $modelAttribute) {
            if (!$levelAttributes->contains($modelAttribute)) {
                $message = $this->translator->trans('pim_catalog.constraint.product_model_invalid_attribute');
                $this->context->buildViolation($message, [
                    '%attribute%' => $modelAttribute->getCode()
                ])->addViolation();
            }
        }
    }
}
