<?php

/**
 *
 * @category   Suite2email
 * @package    Emarsys_Suite2email
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Suite2email_Block_Adminhtml_Renderer_TemplateCode extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        try {
            $eventStoreId = Mage::getSingleton('core/session')->getData('eventStoreId');
            $templateId = Mage::getStoreConfig($row['config_path'], $eventStoreId);
            if (is_numeric($templateId)) {
                $result = Mage::getModel('core/email_template')->load($templateId);
                printf ($result->getData("template_code"));
            } else {
                /* This code has to be separated as it is repeating the same many times */
                $emailTemplates = Mage_Core_Model_Email_Template::getDefaultTemplatesAsOptionsArray();
                $emailTemplateLabels = array();
                foreach ($emailTemplates as $emailTemplate) {
                    $emailTemplateLabels[$emailTemplate['value']] = $emailTemplate['label'];
                }
                /******/
                if (isset($emailTemplateLabels[Mage::getStoreConfig($row['config_path'], $eventStoreId)])) {
                    printf ($emailTemplateLabels[Mage::getStoreConfig($row['config_path'], $eventStoreId)] . " (Default Template from Locale)");
                } else {
                    printf (Mage::getStoreConfig($row['config_path'], $eventStoreId));
                }
            }
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }
}

