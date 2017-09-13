<?php
class Emarsys_Suite2_Block_Adminhtml_System_Config_Button extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return <<<HTML
{$this->_getJavascript($element)}
<button id="{$element->getId()}" title="{$this->__($element->getOriginalData('button_label'))}" type="button" class="scalable" onclick="emarsys_{$element->getOriginalData('target_call')}('{$element->getOriginalData('confirm_message')}')" style="">
    <span><span><span>{$this->__($element->getOriginalData('button_label'))}</span></span></span>
</button>
<br/>
<span id="{$element->getId()}_result" style="display:none; padding-top: 3px"></span>
HTML;
    }

    protected function _getJavascript($element)
    {
        return <<<JS
<script type="text/javascript">
function emarsys_{$element->getOriginalData('target_call')}(confirmMessage)
{
    var url = "{$this->getUrl('*/suite2/' . $element->getOriginalData('target_call') . '/')}?isAjax=true";
    if (confirmMessage) {
        if (!confirm(confirmMessage)) {
            return false;
        }
    }
    $('{$element->getId()}').removeClassName('delete').removeClassName('success').removeClassName('save');
    $('{$element->getId()}_result').hide();
    new Ajax.Request(url, {
        onSuccess: function(response) {
            if (response.responseText.indexOf('<a') === 0) {
                $('{$element->getId()}').addClassName('success').addClassName('save');
                $('{$element->getId()}_result').show().update(response.responseText);
            }
            else if (response.responseText != 1) {
                $('{$element->getId()}_result').show().update(response.responseText);
                $('{$element->getId()}').addClassName('delete');
            } else {
                $('{$element->getId()}').addClassName('success').addClassName('save');
            }
        }
    });
}
</script>
JS;
    }
}