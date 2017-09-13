<?php

/**
 *
 * @category   Webextend
 * @package    Emarsys_Webextend
 * @copyright  Copyright (c) 2017 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Webextend_Model_System_Config_Options_Itemoptions
{
    /**
     * Unique Identifier Options
     * @return mixed
     */
    public function toOptionArray()
    {
        try {
            $uniqueIdentifier = array(
                array('value' => 'product_id', 'label' => 'Product ID'),
                array('value' => 'sku', 'label' => 'SKU'),
            );

            return $uniqueIdentifier;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }
}
