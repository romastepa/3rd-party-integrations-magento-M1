<?php

/**
 *
 * @category   Webextend
 * @package    Emarsys_Webextend
 * @copyright  Copyright (c) 2017 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Webextend_Block_Adminhtml_Attributemapping extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Constructor
     */
    public function __construct()
    {
        try {
            $this->_controller = 'adminhtml_attributemapping';
            $this->_blockGroup = 'webextend';
            $this->_headerText = Mage::helper('suite2email')->__('Magento Emarsys Product Attribute Mapping');
            parent::__construct();
            $this->_removeButton('add');
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }
}
