<?php

/**
 *
 * @category   Webextend
 * @package    Emarsys_Webextend
 * @copyright  Copyright (c) 2017 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Webextend_Model_System_Config_Options_Webextend
{
    /**
     * WebExtend Customer By Options
     * @return mixed
     */
    public function toOptionArray()
    {
        try {
            $customerBy = array(
                array('value' => 'customer_id', 'label' => 'Customer Id'),
                array('value' => 'email_address', 'label' => 'Email Address'),
            );

            return $customerBy;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }
}
