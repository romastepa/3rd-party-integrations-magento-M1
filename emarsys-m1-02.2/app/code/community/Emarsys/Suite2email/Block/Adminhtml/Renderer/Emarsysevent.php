<?php

/**
 *
 * @category   Suite2email
 * @package    Emarsys_Suite2email
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Suite2email_Block_Adminhtml_Renderer_Emarsysevent extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Rendering dropdown options for event mapping screen
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        try {
            $storeId = $this->getRequest()->getParam('store');
            $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();
            $url = $this->getUrl('*/*/changeValue');
            $params = array('mapping_id' => $row->getData('id'), 'store_id' => $storeId);
            $placeHolderUrl = Mage::helper("adminhtml")->getUrl("adminhtml/suite2email_placeholders/index/", $params);
            $jsonRequestUrl = Mage::helper("adminhtml")->getUrl("adminhtml/suite2email_placeholders/jsonrequest/", $params);
            $collection = Mage::getModel('suite2email/emarsysevents')->getCollection()->addFieldToFilter('website_id',array('eq'=>$websiteId));

            $ronly = '';
            $buttonClass = '';
            if (Mage::helper("suite2email")->isReadonlyMagentoEventId($row->getData('magento_event_id'))) {
                $ronly .= ' disabled = disabled';
                $buttonClass = ' disabled';
            }

            $html = '<select ' . $ronly . ' name="directions"  style="width:200px;" onchange="changeValue(\'' . $url . '\',this.value, \'' . $row->getData('magento_event_id') . '\', \'' . $row->getData('id') . '\')";>
			<option value="0">Please Select</option>';

            foreach ($collection as $obj) {
                $sel = '';
                if ($row->getData("emarsys_event_id") == $obj->getData('id')) {
                    $sel .= 'selected = selected';
                }
                $html .= '<option ' . $sel . ' value="' . $obj->getData('id') . '">' . $obj->getData('emarsys_event') . '</option>';
            }

            $html .= '</select>';
            $html .= '&nbsp;&nbsp;&nbsp;<button ' . $buttonClass . ' type="button" class="scalable task form-button ' . $buttonClass . '" name="json" id="json"  onclick="openMyPopup(\'' . $jsonRequestUrl . '\');" >JSON Request</button>';
            $html .= '&nbsp;&nbsp;<button class="scalable task form-button" name="placeholders" id="placeholders"  onclick="placeholderRedirect(\'' . $placeHolderUrl . '\');" >Placeholders</button>';
            return $html;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }
}



