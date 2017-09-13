<?php

/**
 *
 * @category   Suite2email
 * @package    Emarsys_Suite2email
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Suite2email_Block_Adminhtml_Renderer_Magentoevent extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Displaying magento event
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        try {
            $model = Mage::getModel('suite2email/emarsysmagentoevents')->getCollection()->addFilter("id", $row->getData("magento_event_id"))->getFirstItem();
            return $model->getData("magento_event");
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }
}
