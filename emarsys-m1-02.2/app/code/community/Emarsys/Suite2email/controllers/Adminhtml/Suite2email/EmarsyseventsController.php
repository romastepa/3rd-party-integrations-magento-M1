<?php

/**
 *
 * @category   Suite2email
 * @package    Emarsys_Suite2email
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Suite2email_Adminhtml_Suite2email_EmarsyseventsController extends Mage_Adminhtml_Controller_Action
{
    /**
     * @return $this
     */
    protected function _initAction()
    {
        try {
            $this->loadLayout()
                ->_setActiveMenu('suite2email/emarsysevents')
                ->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));

            return $this;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Verify the permissions
     */
    protected function _isAllowed()
    {
        try {
            return Mage::getSingleton('admin/session')->isAllowed('suite2email/emarsysevents');
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
            $storeId = $this->getRequest()->getParam('store');
            if(!$storeId){
                $storeId = Mage::getSingleton('suite2email/emarsysevents')->getFirstStoreId();
                Mage::app()->getResponse()->setRedirect($this->getUrl("adminhtml/suite2email_emarsysevents/index",array('store'=>$storeId)));
            }
            $this->_initAction()
                ->renderLayout();
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * UpdateSchema Action
     */
    public function updateSchemaAction()
    {
        try {
            //Function will delete events which are not exist in Emarsys and if new record found then will insert it
            $storeId = $this->getRequest()->getParam('store');
            $website = Mage::app()->getStore($storeId)->getWebsite();
            Mage::getSingleton('emarsys_suite2/config')->setWebsite($website);
            Mage::getSingleton('suite2email/emarsysevents')->importEvents($storeId);
            Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Schema Updated Successfully!"));
            Mage::app()->getResponse()->setRedirect(Mage::helper("adminhtml")->getUrl("adminhtml/suite2email_emarsysevents/index/",array('store'=>$storeId)));
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }
}
