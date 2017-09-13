<?php

/**
 *
 * @category   Suite2email
 * @package    Emarsys_Suite2email
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Suite2email_Block_Adminhtml_Emarsysevents extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Constructor
     */
    public function __construct()
    {
        try {
            $this->_controller = 'adminhtml_emarsysevents';
            $this->_blockGroup = 'suite2email';
            $this->_headerText = Mage::helper('suite2email')->__('Emarsys Events');
            $this->_addButtonLabel = Mage::helper('suite2email')->__('Add Item');
            parent::__construct();
            $this->_removeButton('add');
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }
}

