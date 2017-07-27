<?php

namespace Pim\Component\Connector\ArrayConverter\FlatToStandard\ProductModel;

use Pim\Component\Connector\ArrayConverter\FlatToStandard\ConvertedField;
use Pim\Component\Connector\ArrayConverter\FlatToStandard\Product\AssociationColumnsResolver;
use Pim\Component\Connector\ArrayConverter\FlatToStandard\Product\FieldSplitter;

/**
 *
 * @author    Arnaud Langlade <arnaud.langlade@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class FieldConverter
{
    /** @var FieldSplitter */
    private $fieldSplitter;

    /** @var AssociationColumnsResolver */
    private $assocFieldResolver;

    private const PRODUCT_MODEL_FIELDS = ['parent', 'identifier', 'family_variant', 'categories'];

    public function __construct(
        FieldSplitter $fieldSplitter,
        AssociationColumnsResolver $assocFieldResolver
    ) {
        $this->fieldSplitter = $fieldSplitter;
        $this->assocFieldResolver = $assocFieldResolver;
    }

    public function convert($fieldName, $value)
    {
        $associationFields = $this->assocFieldResolver->resolveAssociationColumns();

        if (in_array($fieldName, $associationFields)) {
            $value = $this->fieldSplitter->splitCollection($value);
            list($associationTypeCode, $associatedWith) = $this->fieldSplitter->splitFieldName($fieldName);

            return [new ConvertedField('associations', [$associationTypeCode => [$associatedWith => $value]])];
        }

        if ('categories' === $fieldName) {
            $categories = $this->fieldSplitter->splitCollection($value);

            return [new ConvertedField($fieldName, $categories)];
        }

        if ('parent' === $fieldName) {
            return [new ConvertedField($fieldName, (int) $value)];
        }

        return [new ConvertedField($fieldName, $value)];
    }

    public function supportsColumn($fieldName): bool
    {
        $associationFields = $this->assocFieldResolver->resolveAssociationColumns();
        $fields = array_merge(self::PRODUCT_MODEL_FIELDS, $associationFields);

        return in_array($fieldName, $fields);
    }
}
