<?php

/**
 *
 * @category   Webextend
 * @package    Emarsys_Webextend
 * @copyright  Copyright (c) 2017 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Webextend_Model_System_Config_Options_Producttype
{
    /**
     * Different Types of Product Options
     * @return mixed
     */
    public function toOptionArray()
    {
        $productTypes = array();
        $productTypesArray = Mage::getConfig()->getNode('global/catalog/product/type')->asArray();
        try {
            foreach ($productTypesArray as $productKey => $productConfig) {
                $translatedLabel = Mage::helper('catalog')->__($productConfig['label']);
                $productTypes[] = array('value' => $productKey, 'label' => $translatedLabel);
            }
            return $productTypes;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }
}
