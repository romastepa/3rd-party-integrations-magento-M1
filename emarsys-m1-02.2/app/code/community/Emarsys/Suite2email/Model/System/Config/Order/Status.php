<?php

/**
 *
 * @category   Suite2email
 * @package    Emarsys_Suite2email
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Suite2email_Model_System_Config_Order_Status
{
    /**
     * Order status options
     * @return mixed
     */
    public function toOptionArray()
    {
        try {
            return Mage::getModel('sales/order_status')->getCollection()->toOptionArray();
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }
}
