<?php

class Emarsys_Suite2_Block_Adminhtml_System_Config_Button_Export_Orders extends Emarsys_Suite2_Block_Adminhtml_System_Config_Button
{
    protected function _getJavascript($element)
    {
        $js = parent::_getJavascript($element);
        $js .= <<<JS
<script type="text/javascript">
$(document).observe('dom:loaded', function() {
    $$('#emarsys_suite2_smartinsight_settings_bundle_include', '#emarsys_suite2_smartinsight_settings_bundle_price_calculated').each(function(el) {
        el.observe('change', function() { 
            $(emarsys_suite2_smartinsight_settings_export_orders_button).disable().addClassName('disabled');
            $('emarsys_suite2_smartinsight_settings_export_orders_button_result').show().update('<b>You need to save settings to use this functionality</b>');
        });
    });
});
</script>
JS;
        return $js;
    }
}