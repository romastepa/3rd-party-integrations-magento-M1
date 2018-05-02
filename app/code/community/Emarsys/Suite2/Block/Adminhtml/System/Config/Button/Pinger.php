<?php
class Emarsys_Suite2_Block_Adminhtml_System_Config_Button_Pinger extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @inheritdoc
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * @inheritdoc
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return <<<HTML
{$this->_getJavascript()}
<button title="{$this->__($element->getOriginalData('button_label'))}" type="button" class="scalable" onclick="testConnection(this, '{$element->getOriginalData('target')}')" style="">
    <span><span><span>{$this->__($element->getOriginalData('button_label'))}</span></span></span>
</button>
<br/>
<span id="ping_{$element->getOriginalData('target')}_result" style="display:none; padding-top: 3px"></span>
HTML;
    }
    
    /**
     * @inheritdoc
     */
    protected function _getJavascript()
    {
        return <<<JS
<script type="text/javascript">
        function testConnection(src, target)
        {
            src.disable().addClassName('disabled');
            var resultText = $('ping_' + target + '_result').hide().update('');
            var url = "{$this->getUrl('*/suite2/ping')}?isAjax=true";
            url = url + '&' + Form.serialize($(target).up('.section-config'));
            new Ajax.Request(url, {
                parameters: { target: target},
                onSuccess: function(response) {
                    src.removeClassName('disabled').removeClassName('save').removeClassName('success').removeClassName('delete').enable();
                    try {
                        response = response.responseText;
                        if (response == 1) {
                            src.addClassName('save').addClassName('success')
                        } else {
                            src.addClassName('delete');
                        }
                    } catch (e) {
                        src.addClassName('delete')
                    }
                    if (response != 1) {
                        resultText.update(response).show();
                    }
                }
            });
        }
</script>
JS;
    }
}