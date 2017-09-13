<?php

class Emarsys_Suite2_Block_Adminhtml_System_Config_Form_Mapping extends Emarsys_Suite2_Block_Adminhtml_System_Config_Form_Abstract
implements Varien_Data_Form_Element_Renderer_Interface
{
    protected function _getCSS()
    {
        return parent::_getCSS() . <<<STYLE
    #row_emarsys_suite2_contacts_sync_field_mapping_mapping .label { display: none; }
    #row_emarsys_suite2_contacts_sync_field_mapping_mapping .value { display: none; }
    #fields_mapping { width: 600px; }
    #emarsys_suite2_contacts_sync_field_mapping { min-width: 1400px; white-space:nowrap; }
    #emarsys_suite2_contacts_sync_field_mapping table.form-list { clear:both }
STYLE;
    }
    
    protected function _getJS()
    {
        return <<<JS
<script type="text/javascript">
var fieldsMagento = {$this->_getMagentoFields()};
var fieldsEmarsys = {$this->_getEmarsysFields()};

$(document).observe('dom:loaded', mappingInit);

function mappingInit()
{
    for (attrName in fieldsMagento) {
        $('fields_magento').insert(new Element('option', {value:attrName}).update(fieldsMagento[attrName]));
    }
    for (attrName in fieldsEmarsys) {
        $('fields_emarsys').insert(new Element('option', {value:attrName}).update(fieldsEmarsys[attrName]));
    }

    if ($('fields_emarsys').childElements().length == 0) {
        $('emarsys_suite2_contacts_sync_field_mapping').childElements().each(Element.hide);
        $('emarsys_suite2_contacts_sync_field_mapping').insert('<span><b>No fields available in Emarsys Suite for this website (please check API configuration)</b></span>');
        return;
    }

    $('emarsys_suite2_contacts_sync_field_mapping_mapping').value.split('\\n').each(function(line) {
        if (line) {
            map = line.split(':');
            if (map.length == 2) {
                mappingPush(map[0], map[1]);
            }
        }
    });
}

function mappingPush(sm, se)
{
    if (!sm || !se) return;    
    var labelStr = fieldsMagento[sm] + " - " + fieldsEmarsys[se];
    var valueStr = sm + ":" + se;
    $$('#fields_magento option[value='+sm+']').first().remove();
    $$('#fields_emarsys option[value='+se+']').first().remove();
    if ($$('#fields_mapping option[value='+valueStr+']').length) return;
    $('fields_mapping').insert(new Element('option', {value:valueStr}).update(labelStr));
    $('emarsys_suite2_contacts_sync_field_mapping_mapping').update(mappingGet());
}

function mappingGet()
{
    var mappingStr = '';
    $('fields_mapping').select('option').each(function(el) {
        mappingStr += el.value + '\\n';
    });
    return mappingStr;
}

function mappingAdd()
{
    var selectionMagento = $('fields_magento').value;
    var selectionEmarsys = $('fields_emarsys').value;
    mappingPush(selectionMagento, selectionEmarsys);
}

function mappingDel()
{
    var selectionMapping = $('fields_mapping').value;
    if (!selectionMapping) return;
    try {
        $$('#fields_mapping option[value=' + selectionMapping + ']').first().remove();
        $('emarsys_suite2_contacts_sync_field_mapping_mapping').update(mappingGet());
        $('fields_magento').update('');
        $('fields_emarsys').update('');
        mappingInit();
    } catch (e) {}
}
</script>
JS;
    }
    
    protected function _getMagentoFields()
    {
        if ($fields = Mage::helper('emarsys_suite2/adminhtml')->getMappingMagentoFields()) {
            return Mage::helper('core')->jsonEncode($fields);
        } else {
            return '{}';
        }
    }
    
    protected function _getEmarsysFields()
    {
        if ($fields = Mage::helper('emarsys_suite2/adminhtml')->getMappingEmarsysFields()) {
            return Mage::helper('core')->jsonEncode($fields);
        } else {
            return '{}';
        }
    }
    
    protected function _prepareForm()
    {
        try {
            if ($keyFields = Mage::helper('emarsys_suite2/adminhtml')->updateCoreSettings()) {
                Mage::getSingleton('adminhtml/session')->addError('Some of the keyfields are missing or renamed:<br>' . implode('<br>', $keyFields));
            } else {
                Mage::app()->cleanCache(array('config'));
            }

            $this->_addSelector('fields_magento', 'Magento fields');
            $this->_addSelector('fields_emarsys', 'Suite fields');
            $this->_addSelector('fields_mapping', 'Field mapping (Magento > Suite)');
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError('API error encountered: ' . $e->getMessage());
        }
    }
    
    protected function _getAfterElementHtml($element)
    {
        $result = '';
        if ($element->getId() == 'fields_emarsys') {
            $result .= '<span class="emarsys-span buttons"><button type="button" onclick="mappingAdd()"> > </button><br/><br/><button type="button" onclick="mappingDel()"> < </button>' . '</span>';
        }

        return $result;
    }
}