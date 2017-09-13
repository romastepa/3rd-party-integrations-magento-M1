<?php

/**
 *
 * @category   Suite2email
 * @package    Emarsys_Suite2email
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Suite2email_Block_Adminhtml_Placeholders extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Constructor
     */
    public function __construct()
    {
        try {
            $mappingId = $this->getRequest()->getParam("mapping_id");
            $storeId = $this->getRequest()->getParam("store_id");

            /* Start::Script to get the Magento Event */
            $meventId = 0;
            $eventMepping = Mage::getModel('suite2email/emarsyseventsmapping')->getCollection()
                ->addFieldToFilter("id", $mappingId)
                ->getFirstItem();

            if ($eventMepping->getId()) {
                $meventId = $eventMepping->getMagentoEventId();
                $emarsysEventId = $eventMepping->getEmarsysEventId();
            }
            $result = Mage::getModel('suite2email/emarsysmagentoevents')->getCollection()
                ->addFieldToFilter("id", $meventId)
                ->getFirstItem();
            if ($result && $result->getId()) {
                $magentoEvent = $result->getData('magento_event');
            }
            $emarsysEvent = Mage::getModel('suite2email/emarsysevents')->getCollection()
                ->addFieldToFilter("id", $emarsysEventId)
                ->getFirstItem();
            /* End::Script to get the Magento Event */


            $templateId = Mage::getStoreConfig($result->getData("config_path"), $storeId);
            $magTemplateName = '';
            if (is_numeric($templateId)) {
                $result = Mage::getModel('core/email_template')->load($templateId);
                $magTemplateName = $result->getData("template_code");
            } else {
                /* This code has to be separated as it is repeating the same many times */
                $emailTemplates = Mage_Core_Model_Email_Template::getDefaultTemplatesAsOptionsArray();
                $emailTemplateLabels = array();
                foreach ($emailTemplates as $emailTemplate) {
                    $emailTemplateLabels[$emailTemplate['value']] = $emailTemplate['label'];
                }
                /******/
                if (isset($emailTemplateLabels[Mage::getStoreConfig($result->getData("config_path"), $storeId)])) {
                    $magTemplateName = $emailTemplateLabels[Mage::getStoreConfig($result->getData("config_path"), $storeId)] . " (Default Template from Locale)";
                } else {
                    $magTemplateName = Mage::getStoreConfig($result->getData("config_path"), $storeId);
                }
            }


            $this->_controller = 'adminhtml_placeholders';
            $this->_blockGroup = 'suite2email';
            $this->_headerText = Mage::helper('suite2email')->__($magentoEvent . ' - Placeholders Mapping' . '<div style="color: #6a91d1; font-size: 0.95em; padding-top: 7px; padding-bottom: 7px;">Magento Event: <span style="color: black; font-weight: normal; font-size: 0.9em;"> ' . $magentoEvent . '</span><br/> Emarsys Event: <span style="color: black; font-weight: normal; font-size: 0.9em;">' . $emarsysEvent->getData('emarsys_event') . '</span><br/>Magento Template: <span style="color: black; font-weight: normal; font-size: 0.9em;">' . $magTemplateName . '</span></div>');
            $this->_addButtonLabel = Mage::helper('suite2email')->__('Add Item');
            parent::__construct();
            $this->_removeButton('add');
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }
}
