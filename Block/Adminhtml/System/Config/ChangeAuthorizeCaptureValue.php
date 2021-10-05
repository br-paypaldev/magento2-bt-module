<?php

namespace Paypal\BraintreeBrasil\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ChangeAuthorizeCaptureValue extends Field
{

    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setData('after_element_html', $this->getJs($element));
        return parent::_getElementHtml($element);
    }

    private function getJs(AbstractElement $element)
    {
        $id = $element->getHtmlId();
        $path = $element->getOriginalData()['path'];
        $path = str_replace('/', '_', $path);
        $installmentSelector = "{$path}_payment_action";
        $installmentRowSelector = "row_{$path}_payment_action";
        $js = <<<script
            <script>
                require(['jquery'], function ($) {
                    showHidePaymentAction($("#{$id}").val());
                    $("#{$id}").change(function (){
                        showHidePaymentAction($(this).val())
                    });

                    function showHidePaymentAction(value){
                        if (value === '1'){
                            $("#{$installmentRowSelector}").hide();
                            $("#{$installmentSelector}").val('authorize_capture');
                        } else {
                            $("#{$installmentRowSelector}").show();
                        }
                    }
                });
            </script>
        script;

        return $js;
    }
}
