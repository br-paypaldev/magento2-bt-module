<?php
declare(strict_types=1);

namespace Paypal\BraintreeBrasil\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Class InstallmentsConfiguration
 */
class InstallmentsConfiguration extends AbstractFieldArray
{

    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn('installment', ['label' => __('Installment'), 'class' => 'required-entry validate-number validate-greater-than-zero']);
        $this->addColumn('min_value', ['label' => __('Minimum value'), 'class' => 'required-entry validate-number']);
        $this->addColumn('interest_rate', ['label' => __('Interest rate %'), 'class' => 'required-entry validate-number']);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}
