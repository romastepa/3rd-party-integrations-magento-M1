<?php

/**
 *
 * @category   Suite2email
 * @package    Emarsys_Suite2email
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Suite2email_Adminhtml_Suite2email_MagentoeventsController extends Mage_Adminhtml_Controller_Action
{
    /**
     * @return $this
     */
    protected function _initAction()
    {
        try {
            $this->loadLayout()
                ->_setActiveMenu('suite2email/magentoevents')
                ->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));

            return $this;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Verify the permissions
     * @return mixed
     */
    protected function _isAllowed()
    {
        try {
            return Mage::getSingleton('admin/session')->isAllowed('suite2email/magentoevents');
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Index function
     */
    public function indexAction()
    {
        try {
            $this->_initAction()
                ->renderLayout();
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

}
