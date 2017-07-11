

/**
 * Delete extension for variant groups
 *
 * @author    Clement Gautier <clement.gautier@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
import DeleteForm from 'pim/form/common/delete'
import VariantGroupRemover from 'pim/remover/variant-group'
export default DeleteForm.extend({
    remover: VariantGroupRemover
})

