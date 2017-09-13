<?php

/**
 *
 * @category   Suite2email
 * @package    Emarsys_Suite2email
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Suite2email_Block_Adminhtml_Renderer_Emarsyseventmapping extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Rendering select options for event mapping
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        try {
            $magentoEventsModel = Mage::getModel("suite2email/emarsysmagentoevents");
            $magentoEventsCollection = $magentoEventsModel->getCollection();
            $magentoEventsCollection->addFieldToFilter("id", $row->getData("magento_event_id"));
            $magentoEventDetail = $magentoEventsCollection->getFirstItem();
            $magentoEventname = $magentoEventDetail->getData('magento_event');
            $emarsysEventname = trim(str_replace(" ", "_", strtolower($magentoEventname)));

            $session = Mage::getSingleton("core/session")->getData();
            $storeId = $session['storeId'];
            $gridSessionData = $session['gridData'];
            $storeId = $this->getRequest()->getParam('store');
            $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();
            $url = $this->getUrl('*/*/changeValue');
            $params = array('mapping_id' => $row->getData('id'), 'store_id' => $storeId);
            $placeHolderUrl = Mage::helper("adminhtml")->getUrl("adminhtml/suite2email_placeholders/index/", $params);
            $jsonRequestUrl = Mage::helper("adminhtml")->getUrl("adminhtml/suite2email_placeholders/jsonrequest/", $params);
            $collection = Mage::getModel('suite2email/emarsysevents')->getCollection()->addFieldToFilter('website_id',array('eq'=>$websiteId));
            $dbEvents = array();
            foreach ($collection as $_event) {
                $dbEvents[] = $_event->getId();
            }

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
                $id = $row->getData("id");
                $magento_event_id = $row->getData('magento_event_id');
                $gridSessionData[$id]['magento_event_id'] = $magento_event_id;


                if ($row->getData("emarsys_event_id") == $obj->getData('id')) {
                    $sel .= 'selected = selected';
                    $gridSessionData[$id]['emarsys_event_id'] = $obj->getData('id');
                } else if (($emarsysEventname == $obj->getData('emarsys_event')) && ($row->getData("emarsys_event_id") == 0)) {
                    $sel .= 'selected = selected';
                    $gridSessionData[$id]['emarsys_event_id'] = $obj->getData('id');
                } else if (($emarsysEventname == $obj->getData('emarsys_event')) && ($row->getData("emarsys_event_id") != 0) && !in_array($row->getData("emarsys_event_id"), $dbEvents)) {
                    $sel .= 'selected = selected';
                    $gridSessionData[$id]['emarsys_event_id'] = $obj->getData('id');
                }

                $html .= '<option ' . $sel . ' value="' . $obj->getData('id') . '">' . $obj->getData('emarsys_event') . '</option>';
            }

            $html .= '</select>';
            $html .= '&nbsp;&nbsp;&nbsp;<button ' . $buttonClass . ' class="scalable task form-button ' . $buttonClass . '" name="json" id="json" onclick="openMyPopup(\'' . $jsonRequestUrl . '\');" >JSON Request</button>';
            //$html .='&nbsp;&nbsp;<button class="scalable task form-button" name="placeholders" id="placeholders" onclick="placeholderRedirect(\''.$placeHolderUrl.'\');">Placeholders</button>';
            Mage::getSingleton('core/session')->setData('gridData', $gridSessionData);
            return $html;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }
}



