<?php
class Emarsys_Suite2_Block_Adminhtml_System_Config_Button_Export extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @inheritdoc
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
//        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * @inheritdoc
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return parent::_getElementHtml($element) . <<<HTML
<button class="scalable save button_export" title="{$this->__($element->getOriginalData('button_label'))}" style="margin-left: 20px" type="button" class="scalable" onclick="exportEmarsysData(this, '{$element->getOriginalData('target')}')" style="">
    <span><span><span>{$this->__($element->getOriginalData('button_label'))}</span></span></span>
</button>
<br/>
<span id="ping_{$element->getOriginalData('target')}_result" style="display:none; padding-top: 3px"></span>
{$this->_getJavascript()}
HTML;
    }
    
    /**
     * @inheritdoc
     */
    protected function _getJavascript()
    {
        return <<<JS
<script type="text/javascript">
$(document).observe('dom:loaded', function() {
    $$('.button_export').each(function(element) {
        try {
            element.up('tr').select('td').last().insert(element);
        } catch (e) {}
    });
});
        function exportEmarsysData(src, target)
        {
            var url = "{$this->getUrl('*/suite2/export')}?isAjax=true";
            var params = { target: target};
            return ;
            var resultText = $('ping_' + target + '_result');
            resultText.hide().update('');
            if (target == 'suite') {
                params.username = $('emarsys_suite2_settings_api_username').value;
                params.password = $('emarsys_suite2_settings_api_password').value;
            } else {
                params.username = $('emarsys_suite2_webdav_settings_webdav_username').value;
                params.password = $('emarsys_suite2_webdav_settings_webdav_password').value;
            }
            src.disable().addClassName('disabled');
            new Ajax.Request(url, {
                parameters: params,
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