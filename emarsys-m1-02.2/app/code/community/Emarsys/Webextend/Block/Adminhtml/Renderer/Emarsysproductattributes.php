<?php

/**
 *
 * @category   Webextend
 * @package    Emarsys_Webextend
 * @copyright  Copyright (c) 2017 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Webextend_Block_Adminhtml_Renderer_Emarsysproductattributes extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Rendering dropdown options for event mapping screen
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        try {
            $staticExportArray = Mage::helper('webextend')->getstaticExportArray();
            $staticExportMagentoArray = Mage::helper('webextend')->getstaticMagentoAttributeArray();

            $storeId = $this->getRequest()->getParam('store');
            $url = $this->getUrl('*/*/changeValue');
            $collection = Mage::getModel('webextend/emarsysproductattributes')->getCollection()->addFieldToFilter('store_id', array('eq' => $storeId));

            $ronly = '';
            $disabledSelectbox = '';
            if (in_array($row->getData('magento_attribute_code'), $staticExportMagentoArray)) {
                $disabledSelectbox = "disabled";
            }

            $html = '<select ' . $ronly . ' ' . $disabledSelectbox . ' name="directions"  style="width:200px;" onchange="changeAttributeValue(\'' . $url . '\',this.value, \'' . addslashes($row->getData('magento_attribute_code')) . '\',\'' . addslashes($row->getData('magento_attribute_code_label')) . '\', \'' . $row->getData('id') . '\')";>
			<option value="0">Please Select</option>';

            foreach ($collection as $obj) {
                $sel = '';
                if ($row->getData("emarsys_attribute_code_id") == $obj->getData('id')) {
                    $sel .= 'selected = selected';
                }
                $disabled = '';
                if (in_array($obj->getData('attribute_code'), $staticExportArray)) {
                    $disabled = "disabled";
                }
                $html .= '<option ' . $sel . ' value="' . $obj->getData('id') . '" ' . $disabled . '>' . $obj->getData('attribute_code') . '</option>';
            }
            $html .= '</select>';
            return $html;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }
}
